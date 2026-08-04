<?php

declare(strict_types=1);

namespace Onelegstudios\Shape\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Composer;
use Onelegstudios\Shape\Console\Commands\Concerns\AsksWhenAnswerable;
use Onelegstudios\Shape\Console\Commands\Concerns\ResolvesIconNames;
use Onelegstudios\Shape\Icons\Libraries;
use RuntimeException;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Process\Process;
use Throwable;

use function Illuminate\Support\artisan_binary;
use function Illuminate\Support\php_binary;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\select;
use function Laravel\Prompts\suggest;

/**
 * Do the setup Shape's README describes, once, in one command.
 *
 * Installing Shape by hand is four steps, and two of them fail silently. A
 * missing theme import renders every component unstyled with no error anywhere,
 * and an unpublished icon renders as nothing at all -- both look like the
 * package is broken rather than unfinished. Neither step involves a decision,
 * which makes them the wrong thing to ask a person to remember.
 *
 * Nothing here is destructive and nothing here is new behavior. Every step is
 * something the README already tells you to do, and every step reports what it
 * found rather than overwriting it: a stylesheet that already imports the theme
 * is left alone, a published config is left alone, and the icons go through
 * `shape:icon:add`, which has refused to overwrite since before this command
 * existed. Running it twice is how you check the first run worked.
 */
class InstallCommand extends Command
{
    use AsksWhenAnswerable;
    use ResolvesIconNames;

    /**
     * The theme stylesheet, as it sits in the consumer's vendor directory.
     *
     * Doubles as the marker for "already imported": matched as a substring, so
     * an import written by hand with different quoting or a different number of
     * `../` segments still counts as done.
     */
    private const string THEME = 'vendor/onelegstudios/laravel-shape/resources/css/shape.css';

    /**
     * The oldest Tailwind that can build the theme.
     *
     * `shape.css` safelists with `@source inline()` and brace expansion, which
     * is 4.1. Older versions parse the file and quietly generate none of the
     * component classes.
     */
    private const string TAILWIND = '4.1';

    /**
     * The command signature.
     */
    protected $signature = 'shape:install
        {--css= : The application stylesheet the theme import is added to}
        {--no-css : Leave the application stylesheet alone}
        {--icons : Install the configured icon set and publish the semantic names without asking}
        {--set=* : Which icon sets to install instead of asking, e.g. --set=lucide --set=solid}
        {--default= : Which of those sets Shape\'s own components render, when more than one is named}
        {--no-icons : Skip the icon set and the icons that come with it}
        {--config : Publish config/shape.php without asking}';

    /**
     * The command description.
     */
    protected $description = 'Install Shape: import the theme, and publish the icons the components render.';

    /**
     * Whether `config/shape.php` is a file this run put there.
     *
     * Which is what decides, later, whether the icon choice may be written into
     * it. A file this command published is a pristine copy of the one in the
     * package; a file that was already there is somebody's work, and editing it
     * from under them is the one thing every other step of this command refuses
     * to do.
     */
    private bool $publishedConfig = false;

    /**
     * Whether the config step has already run.
     *
     * It is reached from two places -- the icon step, as soon as the chosen sets
     * settle what the step has to do, and the end of `handle()`, for the runs
     * where the icon step never got that far. Whichever arrives first decides;
     * the other is a no-op.
     */
    private bool $offeredConfig = false;

    /**
     * Execute the console command.
     */
    public function handle(Composer $composer, Filesystem $files): int
    {
        // The container builds this with no working path, and every method on it
        // reads one. Not setting it looks for composer.json in the current
        // directory, which is only the application by coincidence.
        $composer->setWorkingPath(base_path());

        $this->components->info('Installing Shape.');

        // The stylesheet goes first because it is the only step that can fail in
        // a way worth stopping for: a run that cannot find somewhere to put the
        // import has not installed anything, and saying so before spending a
        // Composer download on it is the difference between a retry and a mess.
        if (! $this->option('no-css') && ! $this->theme($files)) {
            return self::FAILURE;
        }

        $this->tailwind($files);

        // False only when the flags contradict each other, which is the same
        // kind of failure a `--css` pointing nowhere is: a script said something
        // it did not mean, and finishing quietly would hide it.
        if (! $this->option('no-icons') && ! $this->icons($composer, $files)) {
            return self::FAILURE;
        }

        // A no-op once the icon step has settled this, which it does as soon as
        // the chosen sets exist. This is for the runs that never reach that
        // point -- `--no-icons`, or a run where nothing was picked -- where the
        // file is still worth offering for the component defaults it holds.
        $this->config($files);

        $this->newLine();

        $this->components->info('Shape is installed.');

        $this->line('  Build your assets, then use the components: <fg=gray>npm run dev</>');

        // Escaped rather than written as entities: the console formatter reads
        // `<shape:button>` as a style tag it does not know, and prints nothing.
        $this->line('  <fg=gray>'.OutputFormatter::escape('<shape:button variant="solid" color="primary">Save</shape:button>').'</>');

        return self::SUCCESS;
    }

    /**
     * Import the theme into the application's stylesheet.
     *
     * Returns false only when there is nowhere to write it, which is the one
     * outcome that makes the rest of the install pointless.
     */
    private function theme(Filesystem $files): bool
    {
        $stylesheet = $this->stylesheet($files);

        if ($stylesheet === null) {
            return false;
        }

        $contents = $files->get($stylesheet);
        $label = $this->relative($stylesheet);

        if (str_contains($contents, self::THEME)) {
            $this->components->twoColumnDetail($label, '<fg=yellow>theme already imported</>');

            return true;
        }

        $files->put($stylesheet, $this->imported($contents, $this->import($stylesheet)));

        $this->components->twoColumnDetail($label, '<fg=green>theme imported</>');

        // Written anyway, because the import is still the line the consumer
        // needs and refusing to add it would leave nothing to fix by hand. It
        // just will not do anything until Tailwind is imported above it.
        if (preg_match('/@import\s+["\']tailwindcss/', $contents) !== 1) {
            $this->components->warn("{$label} does not import Tailwind. Add `@import \"tailwindcss\";` above Shape's import.");
        }

        return true;
    }

    /**
     * Which stylesheet the theme import belongs in.
     *
     * A path given on the command line is taken as final -- a script that names
     * the wrong file wants to hear about it, not to have a different file
     * edited underneath it.
     */
    private function stylesheet(Filesystem $files): ?string
    {
        $option = $this->option('css');

        if (is_string($option) && $option !== '') {
            $path = $this->absolute($option);

            if ($files->exists($path)) {
                return $path;
            }

            $this->components->error("No stylesheet at [{$path}].");

            return null;
        }

        $default = resource_path('css/app.css');

        if ($files->exists($default)) {
            return $default;
        }

        $candidates = $this->stylesheets($files);

        if (! $this->canAsk()) {
            $this->components->error('No stylesheet at ['.$this->relative($default).']. Pass --css with the path to yours, or --no-css and add this line by hand:');
            $this->line('  <fg=gray>@import "'.$this->import($default).'";</>');

            return null;
        }

        $answer = trim(suggest(
            label: 'Which stylesheet should import the theme?',
            options: $candidates,
            placeholder: $this->relative($default),
            // Checked here rather than after the prompt so a typo costs one
            // answer instead of the run: there is no second chance further down.
            validate: fn (string $value): ?string => $this->missing($files, $value),
            hint: 'The one that imports Tailwind.',
        ));

        return $this->absolute($answer);
    }

    /**
     * The stylesheets in the application worth offering as an answer.
     *
     * Only `resources/css`, and only one level of it. A recursive search would
     * turn up `node_modules` and every published vendor stylesheet, which are
     * confidently wrong answers -- and the prompt takes a typed path anyway, so
     * a stylesheet somewhere else is still reachable.
     *
     * @return array<int, string>
     */
    private function stylesheets(Filesystem $files): array
    {
        $found = [];

        foreach ($files->glob(resource_path('css/*.css')) ?: [] as $path) {
            $found[] = $this->relative($path);
        }

        sort($found);

        return $found;
    }

    /**
     * The reason an answered path cannot be used, or null if it can.
     */
    private function missing(Filesystem $files, string $value): ?string
    {
        $value = trim($value);

        if ($value === '') {
            return 'Name a stylesheet, or re-run with --no-css.';
        }

        return $files->exists($this->absolute($value)) ? null : "No stylesheet at [{$value}].";
    }

    /**
     * The import line as it should read from the given stylesheet.
     *
     * The `../` segments are counted rather than assumed, because the file being
     * edited is only conventionally `resources/css/app.css`. A stylesheet the
     * consumer keeps somewhere else gets a path that resolves from where it
     * actually is, and one outside the application gets an absolute path --
     * which is ugly, and still correct, which is the order those matter in.
     */
    private function import(string $stylesheet): string
    {
        $from = rtrim(str_replace('\\', '/', dirname($stylesheet)), '/');
        $base = rtrim(str_replace('\\', '/', base_path()), '/');

        if ($from !== $base && ! str_starts_with($from, $base.'/')) {
            return $base.'/'.self::THEME;
        }

        $depth = $from === $base
            ? 0
            : substr_count(substr($from, strlen($base) + 1), '/') + 1;

        return $depth === 0
            ? './'.self::THEME
            : str_repeat('../', $depth).self::THEME;
    }

    /**
     * The stylesheet with the theme import in a place CSS will honour.
     *
     * `@import` has to precede every other rule in a file, so the line goes
     * after the last leading import rather than at the end. Appending would put
     * it after `@theme` blocks and custom rules, where the browser and the
     * Tailwind parser both drop it -- and the failure looks identical to not
     * having run this command at all.
     */
    private function imported(string $contents, string $import): string
    {
        $lines = preg_split('/\R/', $contents) ?: [];

        // Joined back with whatever the file already used. Rewriting a checked-in
        // stylesheet from CRLF to LF would put every line of it in the diff, and
        // the one line this command is responsible for would be lost in it.
        $break = str_contains($contents, "\r\n") ? "\r\n" : "\n";

        $after = -1;

        foreach ($lines as $index => $line) {
            if (preg_match('/^\s*@(charset|import)\b/', $line) === 1) {
                $after = $index;

                continue;
            }

            // Comments and blank lines sit between imports without ending the
            // run of them; anything else does.
            if (trim($line) !== '' && preg_match('/^\s*(\/\*|\*|\/\/)/', $line) !== 1) {
                break;
            }
        }

        array_splice($lines, $after + 1, 0, ['@import "'.$import.'";']);

        return implode($break, $lines);
    }

    /**
     * Say something when the installed Tailwind cannot build the theme.
     *
     * Never fatal, and silent without a package.json: an application that builds
     * its CSS somewhere else is not misconfigured, it is just not answerable
     * from here.
     */
    private function tailwind(Filesystem $files): void
    {
        $manifest = base_path('package.json');

        if (! $files->exists($manifest)) {
            return;
        }

        $decoded = json_decode($files->get($manifest), true);

        if (! is_array($decoded)) {
            return;
        }

        $dependencies = [
            ...(array) ($decoded['dependencies'] ?? []),
            ...(array) ($decoded['devDependencies'] ?? []),
        ];

        $constraint = $dependencies['tailwindcss'] ?? null;

        if (! is_string($constraint)) {
            $this->components->warn('package.json does not list tailwindcss. Shape\'s theme needs Tailwind CSS '.self::TAILWIND.' or newer.');

            return;
        }

        // A constraint no version can be read out of -- `latest`, `*`, a git
        // URL -- is not evidence of anything, so it goes unremarked.
        if (preg_match('/(\d+(?:\.\d+)*)/', $constraint, $matches) !== 1) {
            return;
        }

        if (version_compare($matches[1], self::TAILWIND, '<')) {
            $this->components->warn("package.json asks for tailwindcss {$constraint}. Shape's theme needs ".self::TAILWIND.' or newer.');
        }
    }

    /**
     * Settle the config file: publish it where it is needed, offer it where it
     * is not.
     *
     * Only the config. Publishing views means forking `button.blade.php` and
     * giving up package updates to it, which is a real decision and not one to
     * make on someone's behalf at install time.
     *
     * `$records` is the set the run settled on as its default, and it is passed
     * only when config does not already say so -- which is what turns this from
     * a question into a step. The choice is made in this process and read back
     * in another one, and this file is the only thing between them: `shape:icon`
     * takes its default set from config and from nowhere else, so a run that
     * asked and was told no would publish the icons under whatever the shipped
     * config names, having just been told otherwise. That is not a preference
     * anyone can act on, so it is not offered as one.
     *
     * With nothing to record the file is genuinely optional -- component
     * defaults, and a set table that would only repeat itself -- so it stays a
     * question, and the answer it offers is still no.
     */
    private function config(Filesystem $files, ?string $records = null): void
    {
        if ($this->offeredConfig) {
            return;
        }

        $this->offeredConfig = true;

        if ($files->exists(config_path('shape.php'))) {
            $this->components->twoColumnDetail('config/shape.php', '<fg=yellow>already published</>');

            return;
        }

        if ($records === null && ! $this->option('config')) {
            if (! $this->canAsk()) {
                return;
            }

            $publish = confirm(
                label: 'Publish the config file?',
                default: false,
                hint: 'Component defaults and the icon set. Shape works without it.',
            );

            if (! $publish) {
                return;
            }
        }

        $this->publish($files);
    }

    /**
     * Publish the config file, reporting what came of it.
     */
    private function publish(Filesystem $files): bool
    {
        $this->callSilently('vendor:publish', ['--tag' => 'shape-config']);

        $this->publishedConfig = $files->exists(config_path('shape.php'));

        $this->components->twoColumnDetail(
            'config/shape.php',
            $this->publishedConfig ? '<fg=green>published</>' : '<fg=red>could not be published</>',
        );

        return $this->publishedConfig;
    }

    /**
     * Install the icon sets, and publish the names Shape's views ask for.
     *
     * False means the flags disagree with each other and nothing was installed.
     * Everything else -- a set declined, a Composer run that failed, a config
     * file this command is not allowed to edit -- is reported and finished,
     * because each of those leaves the consumer with something to run rather
     * than something to undo.
     */
    private function icons(Composer $composer, Filesystem $files): bool
    {
        /** @var array<string, mixed> $config */
        $config = (array) config('shape.icons');

        $configured = is_string($config['set'] ?? null) ? $config['set'] : 'lucide';

        $sets = $this->chosenSets($configured);

        if ($sets === []) {
            return true;
        }

        $default = $this->defaultSet($sets, $configured);

        if ($default === null) {
            return false;
        }

        // Reached here rather than before any of this, because the sets are what
        // decide whether there is a question to put. A choice config does not
        // already name has to be written down somewhere, and this is the only
        // file it can go in -- so that run publishes it rather than asking. Only
        // a run with nothing to record still has something to ask, and it gets
        // asked here, ahead of the Composer download below.
        $this->config($files, $this->recordable($sets, $default) ? $default : null);

        // One list per set, because the packaged names differ between libraries:
        // `spinner` is Lucide's `loader-circle` and Heroicons' `arrow-path`, and
        // the alias table is what knows that. Config's own entries ride along,
        // so an application that has added names to it gets its own list too.
        $names = [];

        foreach ($sets as $set) {
            $names[$set] = array_map(strval(...), array_keys($this->aliasesFor($config, $set)));
        }

        if (array_filter($names) === []) {
            return true;
        }

        $packages = $this->missingPackages($composer, $sets);

        $fresh = false;

        if ($packages !== []) {
            if (! $this->wanted($packages)) {
                $this->components->warn('Skipped the icon sets. To finish by hand:');
                $this->line('  <fg=gray>composer require '.implode(' ', $packages).'</>');

                foreach ($names as $set => $list) {
                    $this->line("  <fg=gray>php artisan shape:icon:add --set={$set} ".implode(' ', $list).'</>');
                }

                return true;
            }

            $this->components->info('Installing '.implode(' and ', $packages).'.');

            if (! $composer->requirePackages($packages, false, $this->output)) {
                $this->components->error('Could not install '.implode(' and ', $packages).'.');

                foreach ($names as $set => $list) {
                    $this->manually($set, $list);
                }

                return true;
            }

            $fresh = true;
        }

        // Before publishing, not after: the second Artisan process below boots
        // against the config file as it sits on disk, so a choice recorded
        // afterwards would be a choice that run never saw.
        $this->record($files, $sets, $default);

        $this->publishIcons($names, $fresh, $files);

        return true;
    }

    /**
     * Which sets to install.
     *
     * An empty list means there is nothing to do, which is a real answer here:
     * somebody asked to be shown the choice and picked none of it.
     *
     * @return array<int, string>
     */
    private function chosenSets(string $configured): array
    {
        $named = $this->namedSets();

        if ($named !== []) {
            return $named;
        }

        // `--icons` predates there being more than one set to install and still
        // means what it did: install what config names, ask nothing. So does
        // having nobody to ask.
        if ($this->option('icons') || ! $this->canAsk()) {
            return [$configured];
        }

        $options = [];

        foreach (Libraries::KNOWN as $name => $library) {
            $options[$name] = $library['label'];
        }

        /** @var array<int, string> $libraries */
        $libraries = multiselect(
            label: 'Which icon sets should Shape install?',
            options: $options,
            default: array_filter([Libraries::library($configured)]),
            hint: 'Both is fine -- Shape asks which one is the default next.',
        );

        $sets = [];

        foreach ($libraries as $library) {
            $sets = [...$sets, ...$this->weights($library)];
        }

        return $sets;
    }

    /**
     * Which of a library's sets to install.
     *
     * A single-weight library is not a question. Heroicons keeps its weight in
     * the filename and ships four, which is a genuine choice: they are four
     * directories of the same icons drawn differently, and publishing all four
     * unasked would quadruple a directory somebody has to read.
     *
     * @return array<int, string>
     */
    private function weights(string $library): array
    {
        $sets = Libraries::KNOWN[$library]['sets'] ?? [];

        if (count($sets) < 2) {
            return array_keys($sets);
        }

        $options = [];

        foreach ($sets as $name => $prefix) {
            $options[$name] = "{$name} ({$prefix})";
        }

        /** @var array<int, string> $chosen */
        $chosen = multiselect(
            label: 'Which '.Libraries::KNOWN[$library]['label'].' weights should Shape install?',
            options: $options,
            default: [(string) array_key_first($options)],
            hint: 'Each one becomes a set name your views can ask for.',
        );

        return $chosen;
    }

    /**
     * Which set Shape's own components render.
     *
     * Null when `--default` names a set that is not being installed, which is
     * the one contradiction worth stopping for: the default set is the only one
     * that gets the `default/` forwards, so honouring a name that has no icons
     * behind it would finish successfully and render nothing.
     *
     * @param  array<int, string>  $sets
     */
    private function defaultSet(array $sets, string $configured): ?string
    {
        $option = $this->option('default');

        if (is_string($option) && $option !== '') {
            if (! in_array($option, $sets, true)) {
                $this->components->error("--default names [{$option}], which is not one of the sets being installed.");

                return null;
            }

            return $option;
        }

        // One set is not a choice. A question with a single answer costs a
        // keystroke and teaches nothing, so it is only worth asking once the
        // icons could have come from somewhere else.
        if (count($sets) === 1) {
            return $sets[0];
        }

        $preferred = in_array($configured, $sets, true) ? $configured : $sets[0];

        if (! $this->canAsk()) {
            return $preferred;
        }

        return (string) select(
            label: "Which set should Shape's own components use?",
            options: array_combine($sets, $sets),
            default: $preferred,
            hint: 'The one <shape:icon name="spinner" /> resolves to without a set prop.',
        );
    }

    /**
     * The sets named on the command line, in the order they were named.
     *
     * @return array<int, string>
     */
    private function namedSets(): array
    {
        /** @var array<int, mixed> $option */
        $option = (array) $this->option('set');

        return array_values(array_unique(array_filter(
            array_map(strval(...), array_filter($option, 'is_string')),
            fn (string $set): bool => $set !== '',
        )));
    }

    /**
     * The Composer packages the chosen sets need and the application lacks.
     *
     * A set Shape does not know is not an error -- it means the consumer brought
     * their own, so there is nothing to install and the icons are published from
     * whatever is registered.
     *
     * @param  array<int, string>  $sets
     * @return array<int, string>
     */
    private function missingPackages(Composer $composer, array $sets): array
    {
        $packages = [];

        foreach ($sets as $set) {
            $package = Libraries::package($set);

            if ($package === null || $this->installed($composer, $package)) {
                continue;
            }

            $packages[$package] = true;
        }

        return array_keys($packages);
    }

    /**
     * Whether the application already requires the given package.
     *
     * A missing composer.json is not a failure worth stopping for. It means the
     * question cannot be answered, and the honest answer to "is it installed" in
     * that case is the one that leads to asking.
     */
    private function installed(Composer $composer, string $package): bool
    {
        try {
            return $composer->hasPackage($package);
        } catch (RuntimeException) {
            return false;
        }
    }

    /**
     * Whether the icon sets should be installed with Composer.
     *
     * @param  array<int, string>  $packages
     */
    private function wanted(array $packages): bool
    {
        // Naming the sets is the answer. Asking again would be asking somebody
        // to confirm the thing they just typed.
        if ($this->option('icons') || $this->namedSets() !== []) {
            return true;
        }

        if (! $this->canAsk()) {
            return false;
        }

        return confirm(
            label: 'Install '.implode(' and ', $packages).' for icons?',
            default: true,
            hint: 'The sets stay packages you own -- swap or remove them with plain Composer.',
        );
    }

    /**
     * Write the chosen sets into the application's configuration.
     *
     * Runtime first and unconditionally, because the in-process publish below
     * reads the container rather than the file, and the `default/` forwards it
     * writes depend on which set is the default.
     *
     * The file is a different matter. A pristine copy this run published is
     * Shape's own, and rewriting two of its values is no more than publishing a
     * file that already said them. A copy that was already there is somebody's
     * work -- it may have been edited, reordered, or commented -- so it is left
     * exactly as it is and the two lines are printed instead. That is the same
     * bargain the stylesheet step makes.
     *
     * Publishing has already happened by the time this runs -- the icon step
     * sees to it as soon as it knows there is something to record, which is why
     * there is no question about it here.
     *
     * @param  array<int, string>  $sets
     */
    private function record(Filesystem $files, array $sets, string $default): void
    {
        /** @var array<string, mixed> $config */
        $config = (array) config('shape.icons');

        $configured = array_filter((array) ($config['sets'] ?? []), 'is_string');

        $mapped = $this->mapped($configured, $sets);

        config()->set('shape.icons.set', $default);
        config()->set('shape.icons.sets', $mapped);

        // Already what the file says, so there is nothing to record -- the
        // ordinary case of installing the set config already names.
        if ($default === ($config['set'] ?? null) && $mapped === $configured) {
            return;
        }

        $path = config_path('shape.php');

        $contents = $this->publishedConfig ? $this->recorded($files->get($path), $default, $mapped) : null;

        if ($contents === null) {
            $this->components->warn('The icon sets were not written to config/shape.php. Under `icons`, set:');
            $this->line("  <fg=gray>'set' => ".$this->quoted($default).',</>');
            $this->line("  <fg=gray>'sets' => ".$this->inline($mapped).',</>');

            // Worth spelling out, because the icons themselves are fine and the
            // symptom shows up somewhere else: `default/` forwards are written
            // for whichever set config calls the default, so until it names this
            // one, an <shape:icon> with no `set` prop has nothing to resolve to.
            $this->line("  <fg=gray>php artisan shape:icon:update --set={$default}</>");

            return;
        }

        $files->put($path, $contents);

        $this->components->twoColumnDetail('config/shape.php', '<fg=green>icon sets recorded</>');
    }

    /**
     * Whether the chosen sets say anything `config/shape.php` does not already.
     *
     * Asked before the config file is offered and again when it is written, and
     * both times off the same two comparisons -- a run that decided the choice
     * was worth publishing a file for and then found nothing to put in it would
     * have talked somebody into a file for no reason.
     *
     * @param  array<int, string>  $sets
     */
    private function recordable(array $sets, string $default): bool
    {
        /** @var array<string, mixed> $config */
        $config = (array) config('shape.icons');

        $configured = array_filter((array) ($config['sets'] ?? []), 'is_string');

        return $default !== ($config['set'] ?? null)
            || $this->mapped($configured, $sets) !== $configured;
    }

    /**
     * The set table config should end up with: what it names, plus what this run
     * installed.
     *
     * Existing entries win, because a name config already points somewhere is a
     * decision, and the packaged prefix for it is only a default.
     *
     * @param  array<array-key, string>  $configured
     * @param  array<int, string>  $sets
     * @return array<array-key, string>
     */
    private function mapped(array $configured, array $sets): array
    {
        foreach ($sets as $set) {
            $configured[$set] ??= Libraries::prefix($set) ?? $set;
        }

        return $configured;
    }

    /**
     * The config file with `icons.set` and `icons.sets` rewritten.
     *
     * Null when either value is not where it is expected to be, which for a file
     * this command published a moment ago means the shipped one has moved on
     * without this method. Printing the change beats writing a guess into
     * somebody's config.
     *
     * @param  array<array-key, string>  $sets
     */
    private function recorded(string $contents, string $default, array $sets): ?string
    {
        $written = 0;

        // Anchored to the start of a line, because the shipped file explains
        // both keys before it sets them and a commented example is the first
        // thing an unanchored pattern finds. `'sets'` cannot match the first
        // pattern either: it wants the closing quote right after `set`.
        $contents = (string) preg_replace_callback(
            "/^(\h*)'set'\h*=>\h*'[^']*',$/m",
            fn (array $matches): string => $matches[1]."'set' => ".$this->quoted($default).',',
            $contents,
            1,
            $written,
        );

        if ($written !== 1) {
            return null;
        }

        // The shipped table holds no nested arrays, so the first `]` closes it.
        // A published copy that has grown one is a copy worth not rewriting,
        // which is what the count check turns this into.
        $contents = (string) preg_replace_callback(
            "/^(\h*)'sets'\h*=>\h*\[[^\]]*\],$/m",
            function (array $matches) use ($sets): string {
                $block = $matches[1]."'sets' => [\n";

                foreach ($sets as $name => $prefix) {
                    $block .= $matches[1].'    '.$this->quoted((string) $name).' => '.$this->quoted($prefix).",\n";
                }

                return $block.$matches[1].'],';
            },
            $contents,
            1,
            $written,
        );

        return $written === 1 ? $contents : null;
    }

    /**
     * A set table as one line of PHP, for printing rather than writing.
     *
     * @param  array<array-key, string>  $sets
     */
    private function inline(array $sets): string
    {
        $pairs = [];

        foreach ($sets as $name => $prefix) {
            $pairs[] = $this->quoted((string) $name).' => '.$this->quoted($prefix);
        }

        return '['.implode(', ', $pairs).']';
    }

    /**
     * A value as a single-quoted PHP string.
     *
     * Set names arrive from `--set`, so they are typed by a person and go into a
     * file that gets executed. Escaping the two characters a single-quoted
     * string cares about is what keeps a name with an apostrophe in it a bad set
     * name rather than a syntax error in somebody's config.
     */
    private function quoted(string $value): string
    {
        return "'".addcslashes($value, "\\'")."'";
    }

    /**
     * Publish each set's icons, in the set's own directory.
     *
     * @param  array<string, array<int, string>>  $names
     */
    private function publishIcons(array $names, bool $fresh, Filesystem $files): void
    {
        $last = array_key_last($names);

        foreach ($names as $set => $list) {
            if ($list === []) {
                continue;
            }

            // One clear at the end rather than one per set: the compiled views
            // are a single directory, and clearing it four times over is three
            // more than the work needs.
            $clear = $set === $last;

            $published = $fresh
                ? $this->afterInstalling((string) $set, $list, $clear, $files)
                : $this->inProcess((string) $set, $list, $clear);

            if (! $published) {
                $this->manually((string) $set, $list);
            }
        }
    }

    /**
     * Publish a set's icons after installing the package that holds them.
     *
     * This process cannot do it. `composer require` writes the package to disk,
     * but its service provider was never registered here and its autoload
     * entries were read at boot, so Blade Icons still knows nothing about the
     * set -- verified: an in-process publish fails SvgNotFound on every name. A
     * second Artisan process boots against the new vendor directory and gets the
     * answer this one cannot.
     *
     * @param  array<int, string>  $names
     */
    private function afterInstalling(string $set, array $names, bool $clear, Filesystem $files): bool
    {
        $artisan = $this->absolute(artisan_binary());

        if (! $files->exists($artisan)) {
            return false;
        }

        $process = new Process(
            [
                php_binary(),
                $artisan,
                'shape:icon:add',
                '--set='.$set,
                ...($clear ? [] : ['--no-clear']),
                ...$names,
            ],
            base_path(),
        );

        $process->setTimeout(null);

        try {
            $process->run(function (string $type, string $line): void {
                $this->output->write('  '.$line);
            });
        } catch (Throwable) {
            return false;
        }

        return $process->isSuccessful();
    }

    /**
     * Publish a set's icons in this process, for a package already installed.
     *
     * `shape:icon:add` owns everything about writing an icon -- the alias
     * resolution, the default-set forwards, the refusal to overwrite, the
     * compiled-view clear. A second implementation here would be a second copy
     * of all four.
     *
     * @param  array<int, string>  $names
     */
    private function inProcess(string $set, array $names, bool $clear): bool
    {
        $arguments = ['name' => $names, '--set' => $set];

        if (! $clear) {
            $arguments['--no-clear'] = true;
        }

        return $this->call(AddIconCommand::class, $arguments) === self::SUCCESS;
    }

    /**
     * @param  array<int, string>  $names
     */
    private function manually(string $set, array $names): void
    {
        $this->components->warn('Publish the icons once the set is in place:');
        $this->line("  <fg=gray>php artisan shape:icon:add --set={$set} ".implode(' ', $names).'</>');
    }

    /**
     * A path as given, resolved against the application root when it is relative.
     */
    private function absolute(string $path): string
    {
        $normalised = str_replace('\\', '/', $path);

        // A Windows drive letter and a POSIX root are both already absolute; so
        // is a UNC path. Everything else is read from the application root,
        // which is where a path typed into an Artisan command comes from.
        $rooted = str_starts_with($normalised, '/')
            || preg_match('/^[A-Za-z]:\//', $normalised) === 1;

        return $rooted ? $path : base_path($path);
    }

    /**
     * A path as it should read in output: relative to the application root.
     */
    private function relative(string $path): string
    {
        $normalised = str_replace('\\', '/', $path);
        $base = rtrim(str_replace('\\', '/', base_path()), '/').'/';

        return str_starts_with($normalised, $base)
            ? substr($normalised, strlen($base))
            : $normalised;
    }
}
