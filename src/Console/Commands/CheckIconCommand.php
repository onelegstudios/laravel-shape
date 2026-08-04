<?php

declare(strict_types=1);

namespace Onelegstudios\Shape\Console\Commands;

use BladeUI\Icons\Exceptions\SvgNotFound;
use BladeUI\Icons\Factory as IconFactory;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Onelegstudios\Shape\Console\Commands\Concerns\InteractsWithPublishedIcons;
use Onelegstudios\Shape\Console\Commands\Concerns\ResolvesIconNames;
use Onelegstudios\Shape\Console\Commands\Concerns\WritesIconComponents;

/**
 * Report what `shape:icon:update` would do, without doing any of it.
 *
 * The fourth verb, and the only one that answers a question instead of taking
 * an action. A published icon stops tracking the set it came from, so the
 * directory drifts in two directions at once: the set moves under it when a
 * package is upgraded, and you move it yourself when you tune a glyph by hand.
 * Until this command there was one way to find out which had happened -- run
 * `shape:icon:update` and read what it rewrote -- and that answer costs the hand
 * edits it is reporting on.
 *
 * Telling the two apart needs evidence, because from the outside they look
 * identical: both end as bytes on disk that differ from what the set renders
 * now. The evidence is the stamp each published file carries (see
 * WritesIconComponents), taken of the file at the moment it was written. A file
 * that no longer matches its own stamp was edited here; a stamp that no longer
 * matches a fresh render means the set moved. The two are independent, which is
 * what makes "edited, update available" a state this can name rather than guess
 * at.
 *
 * `shape:icon:update` still does not read the stamp, and should not: it resolves
 * names through config as they stand and compares bytes, which is what makes it
 * the verb that brings a directory in line with configuration. The stamp exists
 * so that a command forbidden from fixing anything can explain what it found.
 *
 * It sweeps every published set rather than asking for one. The other three
 * verbs act, so narrowing them to a single set is a safety property; this one
 * only looks, and a status report covering one of three published directories is
 * half an answer. For the same reason it never prompts: the run that needs this
 * most is the one in CI with nobody at the terminal.
 */
class CheckIconCommand extends Command
{
    use InteractsWithPublishedIcons;
    use ResolvesIconNames;
    use WritesIconComponents;

    /**
     * The command signature.
     *
     * No `--all`, because checking everything is what it does; no `--force`,
     * because it changes nothing and so has nothing to confirm; no `--no-clear`,
     * because it invalidates nothing.
     */
    protected $signature = 'shape:icon:check
        {name?* : Published icon names to check; omit them to check everything published}
        {--set= : Limit the report to one published set}
        {--strict : Exit non-zero when anything is not up to date}';

    /**
     * The command description.
     */
    protected $description = 'Report which published icons are out of date or edited by hand.';

    /**
     * Execute the console command.
     */
    public function handle(IconFactory $icons, Filesystem $files): int
    {
        /** @var array<string, mixed> $config */
        $config = (array) config('shape.icons');

        $sets = array_filter((array) ($config['sets'] ?? []), 'is_string');

        $default = is_string($config['set'] ?? null) ? $config['set'] : 'lucide';

        $path = $this->iconPath($config);

        /** @var array<int, string> $names */
        $names = (array) $this->argument('name');

        $published = $this->publishedSets($files, $path);

        if ($published === []) {
            $this->components->info('No icons are published.');

            return self::SUCCESS;
        }

        $option = $this->option('set');

        // Passed through as given rather than checked against the listing, the
        // same bargain the acting verbs make: a directory left behind by a
        // library you have already uninstalled is still reachable by name.
        if (is_string($option) && $option !== '') {
            $published = [$option];
        }

        $tally = [
            'checked' => 0,
            'current' => 0,
            'outdated' => 0,
            'edited' => 0,
            'forwards' => 0,
            'missing' => 0,
            'unstamped' => 0,
            'skipped' => 0,
        ];

        $seen = [];

        foreach ($published as $set) {
            $available = $this->publishedIconsIn($files, $path, $set);

            if ($available === []) {
                $this->components->warn("No icons are published in set [{$set}].");

                continue;
            }

            // Intersecting against the listing is what lets a name become a
            // path, the same guard `remove` and `update` pass a name through.
            // Order comes from the listing rather than the arguments, so two
            // runs over one directory read the same way.
            $wanted = $names === []
                ? $available
                : array_values(array_intersect($available, $names));

            if ($wanted === []) {
                continue;
            }

            // Only worth a heading when there is more than one set to tell
            // apart; every row names its own set anyway.
            if (count($published) > 1) {
                $this->newLine();

                $this->components->twoColumnDetail(
                    "<fg=gray>{$set}</>",
                    '<fg=gray>'.count($wanted).' icon(s)</>',
                );
            }

            // Both inside the sweep, because both are per set: two directories
            // published from two libraries spell the same semantic name
            // differently, and one table for the pair would report the second
            // set against the first one's artwork.
            $prefix = $this->prefixFor($sets, $set);
            $aliases = $this->aliasesFor($config, $set);

            foreach ($wanted as $name) {
                $seen[$name] = true;

                $tally['checked']++;

                // Resolved through config as it stands now, for the reason
                // `shape:icon:update` does it that way: this has to report what
                // that verb would write, and a name resolved differently here
                // would report on a different file's worth of artwork.
                $resolved = $aliases[$name] ?? $name;
                $icon = $prefix === '' ? $resolved : $prefix.'-'.$resolved;

                // The resolved name is worth the width on a row that needs
                // acting on -- upstream is where you go next, and that is the
                // name to search for -- and noise on one that does not.
                $label = $set.'/'.$name.' <fg=gray>('.$icon.')</>';

                try {
                    $contents = $icons->svg($icon)->contents();
                } catch (SvgNotFound) {
                    $this->components->twoColumnDetail($label, '<fg=yellow>missing from set</>');

                    $tally['missing']++;

                    continue;
                }

                $blade = $files->get($path.'/'.$set.'/'.$name.'.blade.php');

                ['edited' => $edited, 'outdated' => $outdated, 'stamped' => $stamped] = $this->compare(
                    $blade,
                    $this->component($contents, $icon, $set),
                );

                if (! $stamped) {
                    $tally['unstamped']++;
                }

                // Checked because updating writes it: `shape:icon:update`
                // reports an icon as `updated` when only its forward moved, so a
                // report that ignored forwards would call a file current that
                // update is about to touch.
                $forward = $set === $default && ! $this->forwardIsCurrent($files, $path, $set, $name);

                $status = match (true) {
                    $edited && $outdated => '<fg=yellow>edited, update available</>',
                    $edited => '<fg=cyan>edited</>',
                    $outdated => '<fg=yellow>update available</>',
                    $forward => '<fg=yellow>forward out of date</>',
                    default => '<fg=gray>up to date</>',
                };

                $this->components->twoColumnDetail(
                    $edited || $outdated || $forward ? $label : $set.'/'.$name,
                    $status.($stamped ? '' : ' <fg=gray>(unstamped)</>'),
                );

                if ($outdated) {
                    $tally['outdated']++;
                }

                if ($edited) {
                    $tally['edited']++;
                }

                if ($forward) {
                    $tally['forwards']++;
                }

                if (! $edited && ! $outdated && ! $forward) {
                    $tally['current']++;
                }
            }
        }

        // Reported after the sweep rather than inside it: a name is only
        // unpublished once every set has failed to turn it up.
        foreach ($names as $name) {
            if (isset($seen[$name])) {
                continue;
            }

            $this->components->twoColumnDetail($name, '<fg=yellow>not published</>');

            $tally['skipped']++;
        }

        return $this->summarise($tally, $path);
    }

    /**
     * Whether a published file has been edited here, has been overtaken by its
     * set, or both.
     *
     * An unstamped file is one published before the stamp existed. Its header
     * cannot be compared -- the format of the header is the thing that changed --
     * but the artwork below it still answers the half of the question that
     * matters most, and answering half is better than reporting a whole
     * directory as unknown. A hand edit that only touched the header goes
     * unnoticed there, which is the honest limit of the fallback and the reason
     * the row says so.
     *
     * @return array{edited: bool, outdated: bool, stamped: bool}
     */
    private function compare(string $blade, string $rendered): array
    {
        $recorded = $this->stampIn($blade);

        if ($recorded === null) {
            return [
                'edited' => false,
                'outdated' => $this->artwork($blade) !== $this->artwork($rendered),
                'stamped' => false,
            ];
        }

        return [
            // What the file's own bytes come to now, against what they came to
            // when they were written.
            'edited' => $recorded !== $this->stampOf($blade),
            // What was written, against what writing it today would produce.
            'outdated' => $recorded !== $this->stampIn($rendered),
            'stamped' => true,
        ];
    }

    /**
     * Whether an icon's default-set forward already says what updating would
     * write.
     *
     * Deliberately the condition `UpdateIconCommand::refreshForward()` decides
     * on, because the two have to agree: a forward this called current and that
     * one rewrote would make the report one you cannot trust.
     */
    private function forwardIsCurrent(Filesystem $files, string $path, string $set, string $name): bool
    {
        $target = $path.'/default/'.$name.'.blade.php';

        return $files->exists($target) && $files->get($target) === $this->forward($set, $name);
    }

    /**
     * Print the counts and decide the exit code.
     *
     * Success unless `--strict` was asked for. A hand-edited icon is a choice
     * somebody made rather than a fault, and a report that failed the build over
     * one would teach people to stop running it. The build that wants a gate
     * says so.
     *
     * @param  array<string, int>  $tally
     */
    private function summarise(array $tally, string $path): int
    {
        $this->newLine();

        $this->components->info("Checked {$tally['checked']} icon(s) in {$path}.");

        if ($tally['current'] > 0) {
            $this->components->info("{$tally['current']} up to date.");
        }

        if ($tally['outdated'] > 0) {
            $this->components->warn("{$tally['outdated']} out of date. Run `php artisan shape:icon:update`.");
        }

        if ($tally['edited'] > 0) {
            $this->components->warn("{$tally['edited']} edited by hand. Updating overwrites the edit.");
        }

        if ($tally['forwards'] > 0) {
            $this->components->warn("{$tally['forwards']} default forward(s) missing or pointing elsewhere. Updating writes them.");
        }

        if ($tally['missing'] > 0) {
            $this->components->warn("{$tally['missing']} icon(s) are no longer in their set. Remove them, or add them under their new names.");
        }

        if ($tally['unstamped'] > 0) {
            // Not a warning: nothing is wrong with these files. It is the reason
            // they were reported without saying whether anyone had edited them.
            $this->components->info("{$tally['unstamped']} icon(s) predate Shape's stamp, so a hand edit cannot be told apart from an upgrade. The next update records one.");
        }

        if ($tally['skipped'] > 0) {
            $this->components->warn("Left {$tally['skipped']} name(s) alone that were not published.");
        }

        $drifted = $tally['outdated']
            + $tally['edited']
            + $tally['forwards']
            + $tally['missing']
            + $tally['skipped'];

        if ($this->option('strict') && $drifted > 0) {
            $this->components->error('Published icons are not up to date.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
