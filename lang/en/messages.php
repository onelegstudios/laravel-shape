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

];
