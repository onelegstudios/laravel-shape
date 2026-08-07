<?php

declare(strict_types=1);

return [

    // The value a styling prop takes when a call site does not name one. This is
    // where an application states its house style: set `variant` to `solid` and
    // every plain <shape:button> is a filled button, with no view to edit. A call
    // site that names a prop still wins, so this moves the starting point rather
    // than taking the choice away.
    //
    // The shipped values are the quiet ones, which is what keeps the prominent
    // button an explicit decision instead of the one you get by accident.
    //
    // A value a component does not recognise falls back to that same quiet
    // default rather than rendering unstyled -- as does a key missing from this
    // file entirely, which is worth knowing because Laravel merges package config
    // only one level deep: a published copy of this file replaces `components`
    // wholesale, so a key deleted here is a key the package never restores.
    'components' => [

        'button' => [
            'variant' => 'outline',
            'color' => 'neutral',
            'size' => 'md',
        ],

        // Two keys, and neither of them is emphasis. There is no `variant`: an input
        // is not competing for attention the way a button is, so there is no ladder
        // to put it on. There is no `color` either -- the only thing an input's
        // colour ever says is whether the value is wrong, and that is read from the
        // validator rather than named at the call site.
        //
        // `affix` is which of two shapes the ends of the field have when a `prefix`
        // or a `suffix` is given, and it is here rather than left to the call site
        // because it is a house style: an application that sets its currency fields
        // on a plate wants all of them on a plate. It buys nothing on a field with
        // no affix.
        'input' => [
            'size' => 'md',
            'affix' => 'inline',
        ],

        // Every other control in the family, on the same one axis and for the same
        // reasons. Listed separately rather than sharing the input's key so that an
        // application can put its checkboxes on a different rung from its text
        // fields -- which is a real thing to want in a dense form, where the boxes
        // read fine small and the fields do not.
        'select' => [
            'size' => 'md',
        ],

        'textarea' => [
            'size' => 'md',
        ],

        'file' => [
            'size' => 'md',
        ],

        'checkbox' => [
            'size' => 'md',
        ],

        'radio' => [
            'size' => 'md',
        ],

        // The switch is a checkbox underneath and takes the checkbox's rungs, but
        // it gets a key of its own rather than reading that one: the two are the
        // same control only in the markup. An application that runs its forms
        // dense and its settings pages roomy wants to say so once, here, rather
        // than at every call site.
        'switch' => [
            'size' => 'md',
        ],

        // The two controls the browser draws part of. The rung buys the same thing
        // it buys everywhere else -- these take the input's four outer heights, so a
        // slider or a swatch stands level with the field beside it -- but it is
        // worth naming them separately for the opposite reason to the switch's: a
        // slider and a swatch are the controls most likely to sit in a settings
        // panel rather than in a form, where an application may well want them
        // roomier than the fields it runs dense.
        'range' => [
            'size' => 'md',
        ],

        'color' => [
            'size' => 'md',
        ],

        // The icon takes no defaults from here. It renders published components,
        // and folding those away at compile time is only safe while the component
        // reads nothing global -- a `size` default read from config would be
        // frozen into every compiled view the first time it rendered. The size
        // scale lives in the component with `md` as a literal default; a call
        // site that wants another rung names it.

    ],

    // Icon resolution. Shape ships no icons and does not read SVGs: Blade Icons
    // (blade-ui-kit/blade-icons) does that, and sets come from its ecosystem
    // packages or from a directory of your own. What lives here is the naming layer
    // on top, which is the part that makes swapping a set cheap.
    //
    // Shape deliberately does not require a set. The default below names Lucide, but
    // installing it is the application's call, which is what keeps swapping it an
    // ordinary `composer remove` instead of a fight with a dependency it cannot
    // reach.
    'icons' => [

        // Where `shape:icon` writes published icons, and the first place the
        // "shape-icons" view namespace looks. Icons published here shadow the
        // ones the package ships by filename, so overriding one is a matter of
        // publishing it under the same name.
        //
        // Null keeps the default, resource_path('views/vendor/shape-icons'),
        // which is resolved when it is used rather than here -- a package config
        // file is merged before the application has finished booting, and naming
        // a path this early is how you get one built from the wrong base.
        'path' => null,

        // The set an <shape:icon> uses when the call site does not name one.
        'set' => 'lucide',

        // Named sets, each pointing at a Blade Icons *name prefix*. The
        // indirection is the whole point: views say `set="solid"`, config decides
        // what that means, and moving an application from one library to another
        // is an edit here rather than a find-and-replace across every view.
        //
        // A prefix is not the same thing as a Blade Icons set. blade-heroicons
        // registers one set, `heroicon`, and keeps the weight in the filename --
        // so `heroicon-o` and `heroicon-s` are two entries here pointing into it:
        //
        //     'sets' => [
        //         'outline' => 'heroicon-o',
        //         'solid' => 'heroicon-s',
        //         'brand' => 'app',
        //     ],
        //
        // Lucide is single-weight, so the shipped mapping is an identity one. It
        // still earns its place: `set` names a role in your design, and the day a
        // second set arrives the call sites are already speaking the right
        // language. A name that is not listed is passed through as a prefix
        // as-is, so `set="heroicon-o"` works without being registered first.
        //
        // Shape knows the set names of the libraries `shape:install` offers, so
        // `outline`, `solid`, `mini` and `micro` resolve to their Heroicons
        // prefixes whether or not they are listed here. The installer writes the
        // ones you chose into this table anyway, because a table that says what
        // is installed is the one worth reading.
        'sets' => [
            'lucide' => 'lucide',
        ],

        // Semantic names for the icons Shape's own components render. A component
        // asks for `close`, never for `x`, because the package cannot know which
        // library an application installed. Aliases resolve before the prefix is
        // applied, and they resolve when an icon is published rather than when
        // one renders.
        //
        // Empty, because Shape already knows what the libraries it can install
        // call these. The button's loading state asks for `spinner`, which is
        // `loader-circle` in Lucide and `arrow-path` in Heroicons; the validation
        // message asks for `error`, which is `circle-alert` and
        // `exclamation-circle`; the select asks for `select-chevron`, which is
        // `chevrons-up-down` and `chevron-up-down`; and the checkbox asks for
        // `checkbox-check` and `checkbox-indeterminate`, which both libraries
        // happen to spell `check` and `minus`. `shape:icon:add` reads all of that
        // from the set it is publishing from. Those
        // names are also the ones `shape:install` publishes unasked, into the
        // default set alone, so a fresh install needs nothing here at all.
        //
        // The last two are worth a word, because their entries translate nothing.
        // They are in the packaged table so that the package knows it needs them:
        // `shape:install` publishes them, `shape:icon:check` reports them missing,
        // and `shape:icon:remove` holds them back. Naming them for the component
        // rather than the glyph is what lets you repoint the mark inside a checkbox
        // without repointing every `<shape:icon name="check" />` you wrote
        // yourself.
        //
        // The radio's dot is not here and never will be: it is two tokens of CSS
        // in the component, because Heroicons ships no filled circle to point at.
        //
        // What belongs here is your own vocabulary, and any packaged name you
        // would rather point somewhere else:
        //
        //     'aliases' => [
        //         'spinner' => 'loader-pinwheel',   // instead of the packaged one
        //         'close' => 'x',                   // your own name
        //     ],
        //
        // One table covers every set rather than one per set, and it wins over
        // the packaged names for all of them -- an entry here is you saying what
        // you mean. Which is worth knowing if you publish two libraries at once:
        // a name spelled Lucide's way stops resolving in the Heroicons set, so
        // leave those to the packaged table and put only your own names here.
        //
        // Everything else falls through untouched, so call sites keep using real
        // icon names and this does not become a second vocabulary to learn.
        'aliases' => [
            //
        ],

    ],

];
