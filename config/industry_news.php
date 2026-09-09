<?php

return [
    'endpoint' => 'https://news.google.com/rss/search',
    'locale' => ['hl' => 'en-IN', 'gl' => 'IN', 'ceid' => 'IN:en'],
    'feeds' => [
        'gear' => [
            'name' => 'Gear Industry',
            'category' => 'gear-industry',
            'icon' => '⚙️',
            'queries' => [
                'gear manufacturing India', 'gear industry India',
                'gear manufacturing automotive India', 'precision gears India',
                'gear cutting CNC India', 'gear grinding India',
                'transmission gears India', 'automotive gear manufacturing India',
            ],
        ],
        'forging' => [
            'name' => 'Forging Industry',
            'category' => 'forging',
            'icon' => '🔩',
            'queries' => [
                'forging industry India', 'forging manufacturing India',
                'forged components India', 'precision forging India',
                'automotive forging India', 'steel forging India',
                'ring rolling India', 'forging plant India',
                'forging investment India',
            ],
        ],
        'steel' => [
            'name' => 'Steel Industry',
            'category' => 'steel-metals',
            'icon' => '🏗️',
            'queries' => [
                'steel industry India', 'steel manufacturing India',
                'steel plant India', 'stainless steel India',
                'specialty steel India', 'steel investment India',
                'steel production India', 'steel demand India',
                'steel exports India', 'steel technology India',
            ],
        ],
    ],
];
