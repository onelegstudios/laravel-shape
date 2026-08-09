<?php

declare(strict_types=1);

return [

    // Strings a component renders itself, rather than taking from a call site.
    // There are few of them by design: a component that writes its own words is
    // a component an application has to translate, so the slot is the default and
    // this is what is left over -- the label on a state that has no slot to put
    // it in.
    'button' => [

        // The loading button hides its label to show the spinner, which also
        // takes it out of the accessibility tree. This is what the button
        // announces as while it is busy.
        'loading' => 'Loading',

    ],

    'header' => [

        // The accessible name of the `<nav>` inside the bar. A landmark is only
        // worth having if it can be told from the others, and a page with a header
        // nav and a footer nav has two -- so this is what a screen reader's landmark
        // list calls this one. Merged as a default, so a bar with a second nav in it
        // names that one at the call site and leaves this alone.
        'nav' => 'Main',

    ],

];
