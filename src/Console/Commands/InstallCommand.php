<?php

declare(strict_types=1);

namespace Onelegstudios\Shape\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Composer;
use Onelegstudios\Shape\Console\Commands\Concerns\AsksWhenAnswerable;
use RuntimeException;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Process\Process;
use Throwable;

use function Illuminate\Support\artisan_binary;
use function Illuminate\Support\php_binary;
use function Laravel\Prompts\confirm;
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

    /**
     * The theme stylesheet, as it sits in the consumer's vendor directory.
     *
     * Doubles as the marker for "already imported": matched as a substring, so
     * an import written by hand with different quoting or a different number of
     * `../` segments still counts as done.
     */
    private const string THEME = 'vendor/onelegstudios/laravel-shape/resources/css/shape.css';

    /**
     * The Composer package that installs each set Shape's config can name.
     *
     * Only sets this command can actually install belong here. A configured set
     * that is missing from the map is not an error -- it means the consumer
     * brought their own, so the Composer step is skipped and the icons are
     * published from whatever is installed.
     *
     * @var array<string, string>
     */
    private const array PACKAGES = [
        'lucide' => 'mallardduck/blade-lucide-icons',
    ];

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
        {--no-icons : Skip the icon set and the icons that come with it}
        {--config : Publish config/shape.php without asking}';

    /**
     * The command description.
     */
    protected $description = 'Install Shape: import the theme, and publish the icons the components render.';

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

        $this->config($files);

        if (! $this->option('no-icons')) {
            $this->icons($composer, $files);
        }

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
     * Offer to publish the config file.
     *
     * Only the config. Publishing views means forking `button.blade.php` and
     * giving up package updates to it, which is a real decision and not one to
     * make on someone's behalf at install time.
     */
    private function config(Filesystem $files): void
    {
        $path = config_path('shape.php');

        if ($files->exists($path)) {
            $this->components->twoColumnDetail('config/shape.php', '<fg=yellow>already published</>');

            return;
        }

        if (! $this->option('config')) {
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

        $this->callSilently('vendor:publish', ['--tag' => 'shape-config']);

        $this->components->twoColumnDetail('config/shape.php', '<fg=green>published</>');
    }

    /**
     * Install the configured icon set, and publish the names Shape's views ask for.
     */
    private function icons(Composer $composer, Filesystem $files): void
    {
        /** @var array<string, mixed> $config */
        $config = (array) config('shape.icons');

        // The alias table is the list of names the package's own components
        // render, which is exactly the set worth publishing unasked. Read rather
        // than hard-coded, so an application that has published its config and
        // added names to it gets its own list.
        $names = array_map(strval(...), array_keys(array_filter(
            (array) ($config['aliases'] ?? []),
            'is_string',
        )));

        if ($names === []) {
            return;
        }

        $set = is_string($config['set'] ?? null) ? $config['set'] : 'lucide';

        $package = self::PACKAGES[$set] ?? null;

        if ($package === null || $this->installed($composer, $package)) {
            $this->publish($names);

            return;
        }

        if (! $this->wanted($package)) {
            $this->components->warn('Skipped the icon set. To finish by hand:');
            $this->line("  <fg=gray>composer require {$package}</>");
            $this->line('  <fg=gray>php artisan shape:icon:add '.implode(' ', $names).'</>');

            return;
        }

        $this->components->info("Installing {$package}.");

        if (! $composer->requirePackages([$package], false, $this->output)) {
            $this->components->error("Could not install {$package}.");
            $this->manually($names);

            return;
        }

        $this->afterInstalling($names, $files);
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
     * Whether the icon set should be installed with Composer.
     */
    private function wanted(string $package): bool
    {
        if ($this->option('icons')) {
            return true;
        }

        if (! $this->canAsk()) {
            return false;
        }

        return confirm(
            label: "Install {$package} for icons?",
            default: true,
            hint: 'The set stays a package you own -- swap or remove it with plain Composer.',
        );
    }

    /**
     * Publish the icons after installing the set that holds them.
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
    private function afterInstalling(array $names, Filesystem $files): void
    {
        $artisan = $this->absolute(artisan_binary());

        if (! $files->exists($artisan)) {
            $this->manually($names);

            return;
        }

        $process = new Process(
            [php_binary(), $artisan, 'shape:icon:add', ...$names],
            base_path(),
        );

        $process->setTimeout(null);

        try {
            $process->run(function (string $type, string $line): void {
                $this->output->write('  '.$line);
            });
        } catch (Throwable) {
            $this->manually($names);

            return;
        }

        if (! $process->isSuccessful()) {
            $this->manually($names);
        }
    }

    /**
     * Publish the icons in this process, for a set that was already installed.
     *
     * `shape:icon:add` owns everything about writing an icon -- the alias
     * resolution, the default-set forwards, the refusal to overwrite, the
     * compiled-view clear. A second implementation here would be a second copy
     * of all four.
     *
     * @param  array<int, string>  $names
     */
    private function publish(array $names): void
    {
        if ($this->call(AddIconCommand::class, ['name' => $names]) !== self::SUCCESS) {
            $this->manually($names);
        }
    }

    /**
     * @param  array<int, string>  $names
     */
    private function manually(array $names): void
    {
        $this->components->warn('Publish the icons once the set is in place:');
        $this->line('  <fg=gray>php artisan shape:icon:add '.implode(' ', $names).'</>');
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
