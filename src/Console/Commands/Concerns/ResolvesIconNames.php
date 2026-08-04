<?php

declare(strict_types=1);

namespace Onelegstudios\Shape\Console\Commands\Concerns;

use Onelegstudios\Shape\Icons\Libraries;

/**
 * Turn a set name into a Blade Icons prefix, and a Shape name into the one the
 * set actually uses.
 *
 * Shared by add, update, and check because the three have to agree: a name
 * published one way and refreshed another way would rewrite artwork on every
 * run, and the command whose whole job is reporting drift would report drift it
 * caused itself.
 *
 * Both lookups read config first and the packaged registry second. That order is
 * the point -- `config/shape.php` stays the place an application says what it
 * wants, and the registry only answers where the file is silent.
 */
trait ResolvesIconNames
{
    /**
     * The Blade Icons name prefix a set name resolves to.
     *
     * An unknown name is used as a prefix as it stands, which is the bargain
     * every icon command already makes: an ad-hoc `--set=heroicon-o` works
     * without being registered first, and a genuine typo fails naming the prefix
     * it tried rather than quietly answering with the default set's artwork.
     *
     * @param  array<array-key, string>  $sets
     */
    protected function prefixFor(array $sets, string $set): string
    {
        return $sets[$set] ?? Libraries::prefix($set) ?? $set;
    }

    /**
     * The alias table for a set: the library's own names, with config over them.
     *
     * Config's table is flat and covers every set, so an entry there wins
     * everywhere -- an application that has repointed `spinner` means it for
     * whichever set it publishes from. What the registry adds is the case config
     * has nothing to say about: publishing into Heroicons resolves `spinner` to
     * `arrow-path` without anyone having had to know that.
     *
     * @param  array<string, mixed>  $config
     * @return array<string, string>
     */
    protected function aliasesFor(array $config, string $set): array
    {
        /** @var array<string, string> $aliases */
        $aliases = array_filter((array) ($config['aliases'] ?? []), 'is_string');

        return [...Libraries::aliases($set), ...$aliases];
    }
}
