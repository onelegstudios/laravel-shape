# Icons

Shape resolves an icon once, when you publish it, rather than on every render. Install a set,
publish the icons you use, and write them the same way you always would:

```bash
composer require mallardduck/blade-lucide-icons
php artisan shape:icon:add check close chevron-down
```

`php artisan shape:install` does both of those for you on a fresh install, publishing every name
in the alias table. Everything below is for the icons you add afterwards.

```blade
<shape:icon name="check" />

<shape:button variant="solid" color="primary">
    <shape:icon name="check" />
    Save changes
</shape:button>
```

Publishing writes each icon into your application as a small Blade component holding the SVG.
Nothing is looked up at render time — no set lookup, no alias table, no file read — which is
what lets [Blaze](https://github.com/livewire/blaze) fold an icon away entirely, leaving the
`<svg>` inline in the compiled view. On a page dense with icons that is the single largest
saving available, and it is why the indirection moved to publish time instead of being dropped.

[Blade Icons](https://github.com/blade-ui-kit/blade-icons) still does the reading, and its
ecosystem of sets is still what you install — but only `shape:icon:add` and `shape:icon:update`
talk to it. Once an icon is published, the set package is no longer on the render path at all.

## The Icon Commands

Each thing you do to your published icons is its own command, and `shape:icon` on its own lists
them:

```bash
php artisan shape:icon
```

| Command | What it does |
| --- | --- |
| `shape:icon:add` | Publish icons from an installed set. Never overwrites. |
| `shape:icon:check` | Report which published icons are out of date or edited. Changes nothing. |
| `shape:icon:update` | Rewrite published icons from the set as it stands now. |
| `shape:icon:remove` | Take published icons back out. |

They are separate names rather than one command taking an action argument because icon names and
action names are the same vocabulary — `check`, `plus` and `x` are all icons in some set — so
`shape:icon check` could never mean one thing reliably.

## Adding Icons

```bash
php artisan shape:icon:add check                    # one icon from the default set
php artisan shape:icon:add check close settings     # several at once
php artisan shape:icon:add check --set=solid        # from a set you configured
php artisan shape:icon:add --all                    # everything the set contains
php artisan shape:icon:add                          # pick them interactively
php artisan shape:icon:add check --no-clear         # leave compiled views alone
```

| Option | What it does |
| --- | --- |
| `--set=` | Which configured set to take the icons from. Defaults to `icons.set`. |
| `--all` | Publish every icon the set contains, instead of naming them. |
| `--no-clear` | Skip the compiled-view clear, for scripting many publishes before one clear. |

**Adding never overwrites.** An icon that is already published is reported and left exactly as it
is, so re-running the command to pick up one more name is always safe and can never undo an edit
you made by hand — there is no flag here that says otherwise. Naming ten icons when nine are
already there adds the tenth and warns about the nine. Refreshing one against an upgraded set is
[`shape:icon:update`](#updating-icons), where overwriting is the thing you asked for by name.

Publishing clears your compiled views, and it has to: folding copies an icon's markup into every
compiled view that renders it, and editing the published file does not invalidate those. Without
the clear a re-published icon keeps serving the old artwork. `--no-clear` is there for the script
that publishes fifty icons and would rather clear once at the end.

## Picking Icons Interactively

Naming no icons at all asks instead of failing:

```
php artisan shape:icon:add

 ┌ Which set should these icons come from? ─────────────────────┐
 │ › outline (heroicon-o)                                       │
 │   solid (heroicon-s)                                         │
 └──────────────────────────────────────────────────────────────┘
   Configured in config/shape.php under icons.sets.

 ┌ Which icon? ─────────────────────────────────────────────────┐
 │ chev                                                         │
 ├──────────────────────────────────────────────────────────────┤
 │ › chevron-down                                               │
 │   chevron-left                                               │
 └──────────────────────────────────────────────────────────────┘
   2 queued. Leave empty to finish.
```

The set question only appears once `icons.sets` holds more than one entry, and never when
`--set` already answered it. The icon question repeats until you answer it with an empty line,
and completes against everything the set actually holds — plus your [semantic
names](#semantic-names), since `close` is as valid an answer here as `x` and is the name the
published file will get.

A name the set does not have is rejected at the prompt rather than at the end, so a typo costs
one answer instead of the session.

This is the only mode that needs a terminal. `--no-interaction`, a redirected stdin, or a name
on the command line all take the non-interactive path, so a scripted `shape:icon:add` with
nothing to add still fails the way it always did rather than quietly adding nothing.

## Removing Icons

```bash
php artisan shape:icon:remove check                  # one icon
php artisan shape:icon:remove check close            # several at once
php artisan shape:icon:remove check --set=solid      # from a particular set
php artisan shape:icon:remove --all                  # everything published in the set
php artisan shape:icon:remove --all --force          # ...without being asked first
php artisan shape:icon:remove                        # pick them interactively
```

| Option | What it does |
| --- | --- |
| `--set=` | Which published set to remove from. Defaults to the only one, or to `icons.set`. |
| `--all` | Remove every icon published in the set, instead of naming them. |
| `--force` | Answer the `--all` confirmation, for runs with nobody to ask. |
| `--no-clear` | Skip the compiled-view clear, for scripting many removals before one clear. |

**Name the file, not the icon.** Aliases are not resolved here, because they were resolved when
the icon was published: `shape:icon:add close` writes `close.blade.php`, so `shape:icon:remove
close` is what takes it away. The name you remove is the name you see in the directory.

Removing an icon takes its `default/` forward with it, and only that one — a forward pointing at
a different set belongs to that set and goes when it does. A set directory left empty goes too,
so what is on disk stays an honest answer to "which sets have I published?".

A name that was never published is reported and skipped rather than failing the run, so removing
ten icons when only nine are there is not an error. Naming an icon **is** the confirmation, though:
a published file you edited by hand is removed like any other, because there is nothing else the
command could reasonably think you meant.

`--all` is the exception, since it is the one form where a typo costs the whole set:

```
php artisan shape:icon:remove --all

 ┌ Remove all 24 published icon(s) from set [lucide]? ──────────┐
 │ Yes / No                                                     │
 └──────────────────────────────────────────────────────────────┘
   They can be published again with `php artisan shape:icon:add`.
```

`--force` answers it. A scripted `--all` **must** pass `--force` and fails without it, rather than
letting an unanswerable prompt fall back to its default and report success having removed nothing.

Naming nothing asks which icons should go, as one list rather than the repeated question adding
asks. The two are choosing from different things: a set holds two thousand names and can only be
searched, where what you have published is short enough to read and check off. The set question
comes first when more than one set has icons in it — and it lists sets by what is *published*,
not by what is configured, which is what lets you clean up after a library you have already
uninstalled.

## Updating Icons

```bash
php artisan shape:icon:update check                  # one icon
php artisan shape:icon:update check close            # several at once
php artisan shape:icon:update check --set=solid      # in a particular set
php artisan shape:icon:update --all                  # everything published in the set
php artisan shape:icon:update --all --force          # ...without being asked first
php artisan shape:icon:update                        # pick them interactively
```

| Option | What it does |
| --- | --- |
| `--set=` | Which published set to refresh. Defaults to the only one, or to `icons.set`. |
| `--all` | Refresh every icon published in the set, instead of naming them. |
| `--force` | Answer the `--all` confirmation, for runs with nobody to ask. |
| `--no-clear` | Skip the compiled-view clear, for scripting many updates before one clear. |

**Updating overwrites — that is the whole verb.** Naming an icon is the confirmation, the same
bargain removing makes, and a file you edited by hand is rewritten like any other. `--all` is the
exception, because it is the form where a mistake costs every edit in the set at once: it asks
first, and a scripted sweep must pass `--force` rather than let an unanswerable prompt fall back
to its default.

**It resolves through config, not through the file.** The header a published icon carries is
documentation, not state. Names go through `icons.aliases` and `icons.sets` as they stand now,
which is what makes this the verb that brings a published directory in line with configuration —
repoint `close` from `x` to `x-mark` and re-run it, and that is the entire migration. The file it
rewrites is still the one named `close.blade.php`: aliases decide what goes *inside*, never which
file is addressed.

**An icon that is already current is reported `unchanged` and not touched**, so mtimes stay
honest and the compiled-view clear only happens when something actually moved. One exception you
will meet once: icons published before this release carry an older header comment — they predate
the [stamp](#checking-icons) — so the first update rewrites them all even where the artwork is
identical.

**An icon the set no longer has is reported and skipped**, not fatal — a glyph renamed upstream
should not abort a two-hundred-icon refresh. The message names the *resolved* name, which is the
one to search for upstream:

```
lucide/close (lucide-x) ................................ missing from set
```

From there, either remove the icon or point its alias at the new name and update again.

**Updating the default set fixes its forwards.** A missing `default/` forward is written, and one
still pointing at a set you have moved away from is repointed here. That is the opposite of what
removing does, and safe for a reason removing cannot claim: the artwork being pointed at was
confirmed a moment ago, so the rewrite cannot leave a dangling forward behind.

**`--all` means every icon you have published in the set** — the same as removing, the opposite
of adding, which sweeps everything the set *contains*.

## Checking Icons

```bash
php artisan shape:icon:check                     # everything published, in every set
php artisan shape:icon:check check close         # just these
php artisan shape:icon:check --set=solid         # just this set
php artisan shape:icon:check --strict            # ...and fail the build if anything has drifted
```

| Option | What it does |
| --- | --- |
| `--set=` | Limit the report to one published set. Defaults to every set on disk. |
| `--strict` | Exit non-zero when anything is not up to date, for a CI gate. |

**It changes nothing.** That is the point of it: until this verb existed, the way to find out
whether your icons were current was to run [`shape:icon:update`](#updating-icons) and read what it
rewrote — an answer that costs you the hand edits it is reporting on.

```
php artisan shape:icon:check

  lucide/check ............................................. up to date
  lucide/close (lucide-x) .................................. update available
  lucide/menu .............................................. edited
  lucide/trash (lucide-trash) .............................. missing from set

  INFO Checked 24 icon(s) in /app/resources/views/vendor/shape-icons.
  INFO 21 up to date.
  WARN 1 out of date. Run `php artisan shape:icon:update`.
  WARN 1 edited by hand. Updating overwrites the edit.
  WARN 1 icon(s) are no longer in their set. Remove them, or add them under their new names.
```

| Report | What it means |
| --- | --- |
| `up to date` | The file says exactly what the installed set says. |
| `update available` | The set — or your config — now renders something else. |
| `edited` | The file has been changed since it was published, and the set has not moved. |
| `edited, update available` | Both. Updating takes the new artwork and loses the edit. |
| `forward out of date` | The icon is current, but its `default/` forward is missing or names another set. |
| `missing from set` | The name no longer resolves. Named with the resolved name, as updating does. |
| `not published` | You asked about a name that no set on disk has. |

**Telling an edit from an upgrade takes evidence**, because from the outside they are the same
thing: bytes that differ from what the set renders now. So each published file carries a stamp,
taken of the file at the moment it was written. A file that no longer matches its own stamp was
edited here; a stamp that no longer matches a fresh render means the set moved. The two are
independent, which is why `edited, update available` is a state this can name rather than guess at.

Updating still does not read the stamp, and should not — it resolves through config and compares
bytes, which is what makes it the verb that brings a directory in line with configuration. The
stamp is there so that a command forbidden from fixing anything can explain what it found.

**Icons published before the stamp existed are reported `(unstamped)`.** Their header is in the
older format, so it cannot be compared, but the artwork below it still can: you get the right
answer about whether they are out of date, and no answer about whether anyone edited them. One
`shape:icon:update` gives them a stamp and the distinction comes back.

**It checks every set, and never asks anything.** Its three siblings act, so narrowing them to one
set is a safety property; this one only looks, and a status report covering one of three published
directories is half an answer. Nothing is prompted for either — the run that needs this most is the
one in CI with nobody at the terminal.

**Drift is not failure.** A hand-edited icon is a choice somebody made, so the command succeeds and
reports. `--strict` is the opt-in gate, and it fails on anything that is not `up to date` —
including a name you asked about that was never published, since in a scripted check that is
usually a stale list rather than a stale icon.

## Where They Land

Icons are published into a directory per set:

```
resources/views/vendor/shape-icons/
    default/
        check.blade.php
        close.blade.php
    lucide/
        check.blade.php
        close.blade.php
    solid/
        check.blade.php
```

A directory per set, rather than one flat pile, because two sets sharing a name is the normal
case and not an edge one: Heroicons outline and solid have the same names for nearly every
icon, and you would reasonably want both. Nesting them makes a collision impossible rather than
something the command has to notice and warn you about.

`default/` holds a one-line component forwarding to whichever set `icons.set` names. That is
what lets `<shape:icon name="check" />` find an icon without the component reading config —
a config read would be frozen into every compiled view the first time it rendered, which is
exactly what folding cannot survive. The forward folds away too, so it costs nothing.

Change the directory with `icons.path` in `config/shape.php` if `resources/views/vendor` is not
where you want them.

## Icon Sets

The `set` prop names a set; `config/shape.php` decides which library that is:

```php
'icons' => [

    'set' => 'lucide',

    'sets' => [
        'lucide' => 'lucide',
    ],

],
```

That indirection is still the point, it just resolves when you publish rather than when you
render. Views say `set="solid"`; config says what `solid` means. Swapping Lucide for Heroicons,
start to finish:

```bash
composer remove mallardduck/blade-lucide-icons
composer require blade-ui-kit/blade-heroicons
php artisan vendor:publish --tag="shape-config"
```

```php
// config/shape.php
'set' => 'outline',

'sets' => [
    'outline' => 'heroicon-o',
    'solid' => 'heroicon-s',
    'brand' => 'app',        // a directory set of your own, registered with Blade Icons
],
```

```bash
php artisan shape:icon:remove --all --set=lucide --force
php artisan shape:icon:add --all
```

The old set goes first because the artwork in it is now from a library you no longer have, and
adding leaves anything already published alone. `--set` still names it even though `sets` no
longer does: removing works off what is on disk, which is the whole reason it can clean up after
a package you have already uninstalled.

This is a removal and an add rather than an [update](#updating-icons) because the set name
changes, and with it the directory. Updating is for the same set after a package upgrade.

No views change. The re-publish is the step that replaces the old find-and-replace, and it is
the one thing this design asks of you that the runtime lookup did not.

```blade
<shape:icon name="check" />                    {{-- heroicon-o-check --}}
<shape:icon name="check" set="solid" />        {{-- heroicon-s-check --}}
<shape:icon name="logomark" set="brand" />     {{-- app-logomark --}}
```

A value in `sets` is a Blade Icons **name prefix**, which is not always the same as a Blade
Icons *set*: `blade-heroicons` registers one set, `heroicon`, and keeps the weight in the
filename — so `heroicon-o` and `heroicon-s` are two entries pointing into it. A name that isn't
listed is used as a prefix as it stands, so `--set=heroicon-o` works without being registered
first; a typo fails at the command naming the prefix it tried, rather than quietly publishing
from the default set.

To register a directory of your own SVGs, use Blade Icons' own config — Shape maps onto sets,
it doesn't replace how they're declared:

```bash
php artisan vendor:publish --tag=blade-icons
```

```php
// config/blade-icons.php
'sets' => [
    'app' => [
        'path' => 'resources/svg',
        'prefix' => 'app',
    ],
],
```

## Semantic Names

Shape's own components can't name `x` or `x-mark` directly — the package has no idea which
library you installed. They ask for `close`, and `config/shape.php` maps it:

```php
'aliases' => [
    'check' => 'check',
    'chevron-down' => 'chevron-down',
    'close' => 'x',            // Heroicons calls this 'x-mark'
    'spinner' => 'loader-circle',
],
```

Aliases resolve when an icon is published, and the file is named for what your views ask for
rather than what the set calls it: `php artisan shape:icon:add close` writes `close.blade.php`
holding Lucide's `x`. Swapping libraries is remapping these few names and re-publishing; no call
site moves.

An unaliased name passes straight through, so your own call sites keep using real icon names and
this doesn't become a second vocabulary to learn.

## Size and Colour

`size` uses the same four rungs as the button — `xs`, `sm`, `md`, `lg`, defaulting to `md` — so
a `sm` icon in a `sm` button is the obvious thing to write rather than a lookup.

The default is a literal in the component rather than a config value, for the same reason the
set is resolved at publish time: a `config()` read would cost folding. A call site that wants
another rung names it.

**Colour is inherited, not set.** An icon carries no colour class, so it takes the colour of
whatever it sits inside: put one in a solid danger button and it comes out white, with nothing
to configure. `color` is for the icon that stands alone and carries meaning by itself, and it
names the same semantic roles the button does, including your own:

```blade
<shape:icon name="circle-check" color="success" label="Passed" />
```

## Accessibility

Icons are decorative by default and get `aria-hidden="true"`, because most of them repeat a
label that's already beside them. Pass `label` for the icon that *is* the content — an
icon-only button — and it renders as `role="img"` with an `aria-label` instead:

```blade
<shape:button aria-label="Dismiss"><shape:icon name="close" /></shape:button>

<shape:icon name="circle-x" color="danger" label="Failed" />
```

The published files deliberately carry no accessibility attributes of their own. Blade's
`merge` can add an attribute but never take one away, so an icon that hid itself could never be
unhidden by a `label` above it.

## Missing Icons

An icon you never published fails the way any other typo'd Blade component does, naming what it
looked for:

```
Unable to locate a class or view for component [shape-icon::default.chek].
```

That is the trade this design makes, and it is the better half of it: the failure lands the
first time you load the page in development, rather than at runtime in production or silently
from the wrong set. A name built at runtime — `<shape:icon :name="$status->icon" />` — is the
one case that can still surprise you, since nothing can know at publish time which branch you
will hit. Publish those explicitly.

## Writing Icons Directly

`<shape:icon>` is the ergonomic form: it applies the size scale, the colour role, and the
accessibility default. A published icon can also be written straight out, which skips all three
and folds unconditionally — even with a dynamic class, because `class` is passed through rather
than used in logic:

```blade
<x-shape-icon::lucide.check class="size-4" />
```

Reach for it where you are rendering an icon thousands of times and have already settled its
size in a class. Everywhere else `<shape:icon>` folds too, as long as the name is written at the
call site.

## Overriding a Packaged Icon

Shape ships the icons its own components render. Your published directory is searched first, so
publishing an icon under the same name replaces the packaged one — no registry, no precedence
setting, just the filename:

```bash
php artisan vendor:publish --tag="shape-icons"    # take copies of Shape's own
```

They are deliberately not part of the `shape` bundle: publishing every resource at once should
not quietly make you the owner of icons the package would otherwise keep current for you.

## Keeping Icons Current

A published icon stops tracking the set it came from, which is the ordinary bargain for
published assets and the one real cost here. Updating is a step you run, not a subscription.
After upgrading an icon package:

```bash
php artisan shape:icon:check                 # what moved, and what you changed yourself
php artisan shape:icon:update check          # the ones you care about
php artisan shape:icon:update --all --force  # or the whole set, in CI
```

[Check](#checking-icons) first, and especially before the sweeping form: it is the one command here
that tells you which files you have edited by hand, which are exactly the ones `--all` is about to
overwrite. In a build that should never drift, `shape:icon:check --strict` is the gate.

[Updating](#updating-icons) is its own verb rather than a `--force` on adding, for the same
reason adding never overwrites: the objection was never to overwriting, it was to the *routine*
command being able to. Adding is what you re-run to pick up one more name, and a flag on it would
put undoing a hand edit one keystroke from a command you run without thinking. A verb called
`update` cannot be run by accident.

It also replaced a recipe that had a real cost. Removing and then re-adding worked, but it
deleted the file before anything had confirmed the replacement resolved at all — so a glyph the
new package had renamed left you with nothing.

Each published file records which set and which name it came from, so you can always tell what a
given icon is:

```blade
{{-- lucide-check -- published from set "lucide" by Shape's icon commands.
     Adding again leaves this file alone; `shape:icon:update` rewrites it.
     stamp:3f9a1c2d5e7b0148 --}}
```

The stamp is a digest of the file as it was written, and the only part of that header anything
reads back: it is what lets [`shape:icon:check`](#checking-icons) tell an icon you edited from one
the set has moved under. Editing the artwork without editing the stamp is what you want — that is
how the edit gets noticed. Nothing else consults the header, which stays documentation.

## A Word on Set Size

Icon sets are large — Lucide is around 2,000 SVGs, Heroicons about 1,300 — and Blade Icons
registers a Blade component for every icon in every set so that `<x-lucide-check />` works. That
means a directory scan and a couple of thousand component registrations per request.

None of that is on Shape's render path any more: published icons are ordinary Blade files, and
the set package is only consulted when you publish. If you don't write `<x-lucide-check />`-style
tags yourself, switch them off and both costs disappear:

```php
// config/blade-icons.php
'components' => [
    'disabled' => true,
],
```

The name oversells it: that flag disables the generated per-icon tags, not icon components in
general. `@svg('lucide-check')` and `<x-icon name="lucide-check" />` keep working — only the
`<x-lucide-check />` shorthand goes.

Because nothing reads the set at runtime, you can also move it to `require-dev` and keep it out
of production entirely. Weigh that against the fact that it becomes a build-time dependency:
anyone who runs `shape:icon:add` needs it installed, and `composer install --no-dev` on a machine
that publishes icons will not have it.
