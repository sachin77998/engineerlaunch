<?php

// Extra steps of the post-property wizard (each implements App\Contracts\PropertyStep). Add a class here to add a step.
return [
    'extra' => [
        App\Services\Property\Steps\CommercialDetailsStep::class,
        App\Services\Property\Steps\ProjectStep::class,
    ],
];
