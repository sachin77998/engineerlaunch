<?php

// Extra assistant agents. Each needs a class with handle(AgentRequest): AgentResponse; keywords route a message to it
// and aliases are other names the frontend may pass. Adding an agent is a new class plus an entry here.
return [
    'extra' => [
        'property' => [
            'class' => App\AI\Agents\PropertyAgent::class,
            'keywords' => ['bhk', 'flat for', 'flats in', 'apartment', 'house for rent', 'house for sale', 'villa', 'plot', 'property in', 'properties in', 'real estate',
                'pg in', 'paying guest', 'flatmate', 'office space', 'shop for rent', 'shop for sale', 'warehouse', 'pre-leased', 'preleased', 'builder', 'new project',
                'possession', 'rera', 'rent a flat', 'buy a flat', 'buy a house', 'ghar', 'makaan', 'kiraye'],
            'aliases' => ['properties', 'real-estate', 'real_estate', 'housing', 'home'],
        ],
    ],
];
