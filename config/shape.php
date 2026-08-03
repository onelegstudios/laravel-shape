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
        'sets' => [
            'lucide' => 'lucide',
        ],

        // Semantic names for the icons Shape's own components render. A component
        // asks for `close`, never for `x`, because the package cannot know which
        // library an application installed -- so a consumer who swaps Lucide for
        // Heroicons remaps a few names here instead of forking a view. Aliases
        // resolve before the prefix is applied.
        //
        // One table covers every set rather than one per set. Shape's components
        // render in the default set, which is the case this exists to serve; if
        // you point a second set at a name that disagrees, spell it out at the
        // call site, where the reader can see which set is in play.
        //
        // Only names Shape itself renders belong here. Everything else falls
        // through untouched, so call sites keep using real icon names and this
        // does not become a second vocabulary to learn.
        //
        // One entry per icon a Shape component actually renders, which is also the
        // list `shape:install` publishes unasked -- adding a name here is how a
        // component's icon reaches an application, and removing one is how a
        // component that renders it starts failing.
        //
        // The button's loading state is the only entry so far. It names Lucide's
        // `loader-circle` -- a single arc, so a plain rotation reads as movement
        // at a glance -- rather than `loader`, whose evenly spaced spokes look
        // nearly still while they turn. Point it at any icon you would rather
        // see and re-publish; the component is untouched either way, which is
        // the whole point of the table.
        'aliases' => [
            'spinner' => 'loader-circle',
        ],

    ],

];
