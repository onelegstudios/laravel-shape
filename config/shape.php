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

    ],

];
