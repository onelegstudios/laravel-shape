# Installation

Install the package with Composer, then run the installer:

```bash
composer require onelegstudios/laravel-shape
php artisan shape:install
```

`shape:install` imports the theme into your application stylesheet, offers to install an icon
set, and publishes the icons Shape's own components render. It changes nothing it did not
write — a stylesheet that already imports the theme is left alone, and so is a published icon —
so running it again is how you check the first run worked.

The rest of this page is what the installer does, for anyone who would rather do it by hand
or wants to know what changed.

## Blaze

Composer pulls in [Blaze](https://github.com/livewire/blaze), which compiles Shape's components
into plain PHP functions instead of routing them through Blade's component pipeline. Unlike the
icon set it is a real dependency, because the shipped views carry the `@blaze` directive and
Blade renders an unregistered directive as literal text. Nothing about using Shape changes. See
[Performance](performance.md).

## The theme

Shape requires **Tailwind CSS v4.1** or newer. The installer adds the theme import to your
application stylesheet, immediately after the imports already there:

```css
@import "tailwindcss";
@import "../../vendor/onelegstudios/laravel-shape/resources/css/shape.css";
```

That one line does two things: it defines the semantic colour roles Shape's components
style themselves against, and it tells Tailwind to scan the package's Blade views so the
component classes are actually generated. Skip it and the components render unstyled. See
[Theming](theming.md) for what the theme defines and how to override it.

## An icon set

Icons are published into your application rather than resolved on every render, so the installer
also installs an icon set. It offers Lucide and Heroicons, takes either or both, asks which one
Shape's own components should use when you take both, and publishes the names they ask for into
that set:

```bash
composer require mallardduck/blade-lucide-icons
php artisan shape:icon:add spinner
```

Each library spells those names its own way — the button's spinner is Lucide's `loader-circle`
and Heroicons' `arrow-path`, the select's chevron is `chevrons-up-down` and `chevron-up-down` — and
Shape knows which is which, so a set you pick works without a
config edit. The set stays a package you own — swap or remove it with plain Composer, and
re-publish. `php artisan shape:icon:check --strict` is how a build finds out that an upgraded Shape
draws a mark the application has not published yet. See [Icons](icons.md) for sets, semantic
names, and the rest of the commands.

## Running it unattended

The installer takes flags for each of its steps, so it can run unattended:

```bash
php artisan shape:install --no-interaction --icons
php artisan shape:install --set=lucide --set=solid --default=solid
php artisan shape:install --css=resources/css/theme.css --no-icons
```

| Flag | What it does |
| --- | --- |
| `--css=` | The stylesheet the theme import is added to, instead of `resources/css/app.css` |
| `--no-css` | Leave the stylesheet alone |
| `--icons` | Install the configured set and publish the icons without asking |
| `--set=` | Which sets to install, instead of asking. Repeat it for more than one |
| `--default=` | Which of those sets Shape's own components render, when more than one is named |
| `--no-icons` | Skip the icon set entirely |
| `--config` | Publish `config/shape.php` without asking |

Picking a set that `config/shape.php` does not already name writes the choice into that file,
publishing it first if you have not — without asking, because there is nowhere else the choice can
go: the icon commands read the default set from config, so a run that skipped the file would
publish the icons under the set you just replaced. A config file that was already there is left
exactly as it is, and the two lines to change are printed instead. You are only asked about the
file when there is nothing to record in it, and then it is asked after the set questions, where it
is a question about component defaults alone.

## Publishing resources

You may publish all of the package's resources at once:

```bash
php artisan vendor:publish --tag="shape"
```

Or, you may publish each resource individually:

```bash
php artisan vendor:publish --tag="shape-config"
php artisan vendor:publish --tag="shape-views"
php artisan vendor:publish --tag="shape-lang"
php artisan vendor:publish --tag="shape-css"
```

`shape-icons` is deliberately not part of the `shape` bundle — it takes copies of the icons
Shape's own components render, which you only want if you mean to override them.

```bash
php artisan vendor:publish --tag="shape-icons"
```

---

[Index](README.md) · [Components →](components.md)
