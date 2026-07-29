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

        // The icon has no `color` default on purpose. Every other styling prop
        // names a value; this one's default is to name nothing, so the icon
        // inherits `currentColor` from whatever it sits inside. An icon in a solid
        // danger button has to come out the button's colour, and any default here
        // -- even `neutral` -- would break that everywhere to serve the rarer
        // standalone case, which can just say `color="danger"`.
        'icon' => [
            'size' => 'md',
        ],

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
        // The four below are examples for now: no Shape component renders an icon
        // yet, so nothing depends on them. They are here to show the shape of the
        // table and to name the icons the first components will reach for. Treat
        // them as a starting point -- prune the ones you have no use for, and
        // expect the list to be filled in properly as components arrive.
        'aliases' => [
            'check' => 'check',
            'chevron-down' => 'chevron-down',
            'close' => 'x',
            'spinner' => 'loader-circle',
        ],

    ],

];
