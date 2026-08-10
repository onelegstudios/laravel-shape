# Icons

Shape resolves an icon once, when you publish it, rather than on every render. Install a set,
publish the icons you use, and write them the same way you always would:

```bash
composer require mallardduck/blade-lucide-icons
php artisan shape:icon:add check chevron-down spinner
```

`php artisan shape:install` does both of those for you on a fresh install — it offers
[Lucide and Heroicons](#the-sets-the-installer-offers), installs the ones you pick, and publishes
the names Shape's own components render into the default set. Everything below is for the icons
you add afterwards.

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
saving available, and it is why the indirection sits at publish time rather than on the render path.

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
| `--set=` | Which set to take the icons from. Defaults to `icons.set`. |
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
nothing to add fails rather than quietly adding nothing.

## Removing Icons

```bash
php artisan shape:icon:remove check                  # one icon
php artisan shape:icon:remove check close            # several at once
php artisan shape:icon:remove check --set=solid      # from a particular set
php artisan shape:icon:remove --all                  # everything published in the set
php artisan shape:icon:remove --all --force          # ...without being asked first
php artisan shape:icon:remove                        # pick them interactively
php artisan shape:icon:remove spinner --force        # an icon Shape's own components render
```

| Option | What it does |
| --- | --- |
| `--set=` | Which published set to remove from. Defaults to the only one, or to `icons.set`. |
| `--all` | Remove every icon published in the set, instead of naming them. |
| `--force` | Remove the icons Shape's own components render, and answer the `--all` confirmation. |
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

### The Icons Shape Renders

A few names are not yours to lose by accident: the [semantic names](#semantic-names) Shape's own
components ask for, which `shape:install` publishes unasked so those components have artwork. Today
they are:

| Name | Rendered by |
|---|---|
| `spinner` | the button's [loading state](components/button.md#loading) |
| `error` | the [validation message](components/input.md#invalid) |
| `select-chevron` | the [select](components/select.md) |
| `checkbox-check` | a checked [checkbox](components/checkbox.md#indeterminate) |
| `checkbox-indeterminate` | an indeterminate one |

Removing one does not leave you short of an icon you chose — it leaves a button
rendering nothing mid-submit, or a form that throws at the moment it has something to report.

So they are held back from every route into the command. `--all` sweeps around them, the prompt
does not offer them, and naming one outright is refused:

```
php artisan shape:icon:remove spinner

  lucide/spinner ......................................... kept

   Removed 0 icon(s) from resources/views/vendor/shape-icons.
   Kept 1 icon(s) Shape's own components render. Pass --force to remove them.
```

That run **fails**, so a script that asked for the spinner finds out it did not get it. Other names
in the same run are still removed — the refusal is about the one name, not the whole instruction.

`--force` is the way past all three. An application that renders its own spinner, or has stopped
using the button, is entitled to say the package is wrong about what it needs:

```bash
php artisan shape:icon:remove spinner --force
php artisan shape:icon:remove --all --force    # sweeps them too
```

Only the shipped names are held back. Your own `icons.aliases` entries are your vocabulary for your
own call sites, and `shape:icon:remove close` takes `close.blade.php` away like any other file.

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
honest and the compiled-view clear only happens when something actually moved. One exception: a
file whose header is missing its [stamp](#checking-icons) differs from a fresh render whatever the
artwork below it says, so updating rewrites it.

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

**It changes nothing.** That is the point of it: the other way to find out whether your icons are
current is to run [`shape:icon:update`](#updating-icons) and read what it rewrote — an answer that
costs you the hand edits it is reporting on.

```
php artisan shape:icon:check

  lucide/check ............................................. up to date
  lucide/close (lucide-x) .................................. update available
  lucide/menu .............................................. edited
  lucide/trash (lucide-trash) .............................. missing from set

  default/select-chevron ................................... not published

  INFO Checked 24 icon(s) in /app/resources/views/vendor/shape-icons.
  INFO 21 up to date.
  WARN 1 out of date. Run `php artisan shape:icon:update`.
  WARN 1 edited by hand. Updating overwrites the edit.
  WARN 1 icon(s) are no longer in their set. Remove them, or add them under their new names.
  ERROR 1 icon(s) Shape's own components render are not published. Run
        `php artisan shape:install` or `php artisan shape:icon:add`.
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
| `default/<name> — not published` | A name [Shape's own components render](#the-icons-shape-renders) has no artwork. |

**The last row is the one that matters after an upgrade,** and it is the only absence any of these
four verbs can see. `add` is driven by what you named, `remove` and `update` by what is already on
disk — so a name that was never published is invisible to all three. When a Shape release starts
drawing a mark it did not draw before, an application that has not re-run `shape:install` has a view
that throws the first time that component renders. This is how you find out before a page does:

```bash
php artisan shape:icon:check --strict
```

Reported against `default/`, because that is the directory those components resolve through — they
ask without a `set` prop. Artwork sitting in a set with no forward beside it is a directory nothing
renders from, which is the same absence. It is reported whether or not you narrowed the run with
`--set`, since what these names are missing from is not a set you can name; it is left out only when
you asked about specific names, because a question about one icon does not want a paragraph about
four others.

**Telling an edit from an upgrade takes evidence**, because from the outside they are the same
thing: bytes that differ from what the set renders now. So each published file carries a stamp,
taken of the file at the moment it was written. A file that no longer matches its own stamp was
edited here; a stamp that no longer matches a fresh render means the set moved. The two are
independent, which is why `edited, update available` is a state this can name rather than guess at.

Updating still does not read the stamp, and should not — it resolves through config and compares
bytes, which is what makes it the verb that brings a directory in line with configuration. The
stamp is there so that a command forbidden from fixing anything can explain what it found.

**A file whose header carries no stamp is reported `(unstamped)`.** There is nothing to compare it
against, so an edit cannot be told from an upgrade — but the artwork below the header still can be:
you get the right answer about whether it is out of date, and no answer about whether anyone edited
it. One `shape:icon:update` gives it a stamp and the distinction comes back.

**It checks every set, and never asks anything.** Its three siblings act, so narrowing them to one
set is a safety property; this one only looks, and a status report covering one of three published
directories is half an answer. Nothing is prompted for either — the run that needs this most is the
one in CI with nobody at the terminal.

**Drift is not failure.** A hand-edited icon is a choice somebody made, so the command succeeds and
reports. `--strict` is the opt-in gate, and it fails on anything that is not `up to date` —
including a name you asked about that was never published, since in a scripted check that is
usually a stale list rather than a stale icon.

A missing semantic name is the one thing reported as an **error** rather than a warning. Everything
else here is a directory that has drifted from a set, which is a thing to decide about; that is a
shipped component with no artwork behind it, which is a page that breaks, and the fix is one command
away.

## Where They Land

Icons are published into a directory per set:

```
resources/views/vendor/shape-icons/
    default/
        check.blade.php          forwards to the configured default set
        close.blade.php
        art/
            check.blade.php      forwards to the same set's artwork
            close.blade.php
    lucide/
        check.blade.php          the component a call site addresses
        close.blade.php
        art/
            check.blade.php      the SVG itself
            close.blade.php
    solid/
        check.blade.php
        art/
            check.blade.php
```

Two files per icon, and the split is not filing. `<shape:icon>` reaches artwork with `@include`
rather than by resolving a component, because an include is what a *dynamic* icon can afford —
a `:name` bound to a variable, or a mark a component sizes from one, cannot fold and would
otherwise pay a component resolution on every render. An include cannot end at a file carrying
`@blaze`, though: Blaze compiles one into a function definition, so including it renders nothing
at all. So the artwork is a plain view that anything may include, and the component beside it is
the half that folds.

Writing `<x-shape-icon::lucide.check />` by hand still resolves to the component and still folds
away to the SVG — folding evaluates the include at compile time, so the pair collapses to one
inline `<svg>` exactly as a single file did.

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
render. Views say `set="solid"`; config says what `solid` means.

### The Sets the Installer Offers

Shape knows two libraries well enough to install them, and knows what each one calls the icons
its own components render:

| Library | Composer package | Set names |
| --- | --- | --- |
| Lucide | `mallardduck/blade-lucide-icons` | `lucide` |
| Heroicons | `blade-ui-kit/blade-heroicons` | `outline`, `solid`, `mini`, `micro` |

`shape:install` asks which of them you want, and which Heroicons weights — they are four
spellings of the same icons, so it is worth picking. Take both libraries and it asks which one is
the default; take one and that one is. The answer is written into `config/shape.php`, and it is
the only thing about your choice that has to be recorded, because those set names resolve to
their prefixes whether or not `icons.sets` lists them.

```bash
php artisan shape:install                                             # pick from the list
php artisan shape:install --set=lucide --set=solid --default=solid     # or say so up front
```

Every set you pick is installed; only the default one is published into. The names the installer
publishes are the ones Shape's own components render, and those components ask for them without a
`set` prop — so the same artwork in a second set would be a directory nothing renders from until
you write a call site for it. When you do, `shape:icon:add --set=` is how you say so.

Any other Blade Icons set works exactly as it does anywhere else — Shape maps onto sets, it does not
supply them. Install the package, name the set in `icons.sets`, and add the names your components
use to `icons.aliases`.

### Swapping One For Another

Swapping Lucide for Heroicons after the fact, start to finish:

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

Or, for the two libraries Shape knows, the same swap as one command and no config editing:

```bash
php artisan shape:install --set=outline --no-css
php artisan shape:icon:remove --all --set=lucide --force
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

Shape's own components can't name `loader-circle` or `arrow-path` directly — the package has no idea
which library you installed. The button's [loading state](components/button.md#loading) asks for
`spinner`, the [validation message](components/input.md#invalid) asks for `error`, and the
[select](components/select.md) asks for `select-chevron`; the set each is
published from decides what those mean:

```blade
<shape:icon name="spinner" />                  {{-- lucide-loader-circle --}}
<shape:icon name="spinner" set="solid" />      {{-- heroicon-s-arrow-path --}}

<shape:icon name="error" />                    {{-- lucide-circle-alert --}}
<shape:icon name="error" set="solid" />        {{-- heroicon-s-exclamation-circle --}}

<shape:icon name="select-chevron" />           {{-- lucide-chevrons-up-down --}}
<shape:icon name="select-chevron" set="solid" /> {{-- heroicon-s-chevron-up-down --}}
```

Two of the five translate nothing: the [checkbox](components/checkbox.md)'s `checkbox-check` and
`checkbox-indeterminate` are `check` and `minus` in both libraries. They are names anyway, and for
two reasons. Being in the table is what makes the package publish and protect them — that is how
`shape:install` knows a checkbox needs artwork. And naming them for the component rather than the
glyph is what lets you repoint the mark inside a checkbox without repointing every
`<shape:icon name="check" />` you wrote yourself.

The [radio](components/radio.md)'s dot is not in the table and never will be: Heroicons ships no
filled circle to point at, so it is drawn in CSS instead.

Nothing in your config says either of those. Shape ships the names for the libraries it can
[install](#the-sets-the-installer-offers), one table per library, which is what lets
`shape:icon:add --set=solid spinner` land the Heroicons artwork under Shape's name for it.
`shape:install` publishes exactly these names into the default set, so a component's icon reaches
your application without you being asked for it — and [`shape:icon:remove` holds them
back](#the-icons-shape-renders) unless you pass `--force`, so a sweep of the set does not leave a
shipped component with nothing to render.

`icons.aliases` is where you say something different. It is empty as shipped, and an entry in it
wins for every set:

```php
'aliases' => [
    'spinner' => 'loader-pinwheel',   // instead of the name Shape ships
    'close' => 'x',                   // your own name, for your own call sites
],
```

That one table covers every set, which is worth knowing if you publish two libraries at once: a
name spelled Lucide's way stops resolving in a Heroicons directory. Leave the shipped names to
the shipped table and put only your own here.

Your own names are the ordinary use for it. Alias `close` to `x` and every call site can write
`<shape:icon name="close" />`, which survives the day you swap Lucide for a library that calls
it `x-mark`.

Aliases resolve when an icon is published, and the file is named for what your views ask for
rather than what the set calls it: `php artisan shape:icon:add close` writes `close.blade.php`
holding Lucide's `x`. Swapping libraries is remapping these few names and re-publishing; no call
site moves.

An unaliased name passes straight through, so your own call sites keep using real icon names and
this doesn't become a second vocabulary to learn.

## Size and Colour

`size` uses the same four rungs as the button — `xs`, `sm`, `md`, `lg`, defaulting to `md` — so
a `sm` icon in a `sm` button is the obvious thing to write rather than a lookup. Better still,
don't write it: the button's `icon` and `icon-trailing` props take a name and size the mark to
the button's own rung, which is one fewer thing to keep in step. See
[Button](components/button.md#icons).

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
<shape:icon name="circle-x" color="danger" label="Failed" />
```

Inside a button, the name belongs on the button rather than on the mark, which is where
assistive tech looks for it — so the icon stays hidden and `aria-label` goes on the tag:

```blade
<shape:button icon="close" aria-label="Dismiss" />
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

---

[← Heading](components/heading.md) · [Index](README.md) · [Theming →](theming.md)
