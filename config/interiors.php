<?php

// Home interiors: rooms / styles / categories for the design gallery, the room-wise estimator and the style quiz.
// Estimator rates are the owner's own indicative prices — change them here before going live.
return [
    'rooms' => [
        'living' => ['Foyer & living', '🛋️'], 'master_bedroom' => ['Master bedroom', '🛏️'], 'guest_bedroom' => ['Guest bedroom', '🛏'], 'kids_bedroom' => ['Kids bedroom', '🧸'],
        'kitchen' => ['Kitchen', '🍳'], 'dining' => ['Dining area', '🍽️'], 'study' => ['Study', '📚'], 'bathroom' => ['Bathroom', '🛁'], 'puja' => ['Puja room', '🪔'], 'balcony' => ['Balcony', '🌿'],
    ],
    'styles' => [
        'modern' => ['Modern', 'Clean lines, flat finishes and a calm neutral base.', ['#e7e5e4', '#57534e', '#1c1917']],
        'contemporary_indian' => ['Indian contemporary', 'Warm woods, brass accents and traditional motifs in a modern layout.', ['#b45309', '#fde68a', '#7c2d12']],
        'minimal' => ['Minimal', 'Few pieces, hidden storage and lots of empty space.', ['#f5f5f4', '#d6d3d1', '#78716c']],
        'scandinavian' => ['Scandinavian', 'Light woods, white walls, soft textiles and daylight.', ['#fafaf9', '#d4b896', '#94a3b8']],
        'modern_classical' => ['Modern classical', 'Mouldings, panelled walls and rich fabrics with modern comfort.', ['#e5d3b3', '#a16207', '#3f3f46']],
        'mid_century' => ['Mid-century modern', 'Tapered legs, walnut tones and bold accent colours.', ['#92400e', '#0f766e', '#f59e0b']],
        'tropical' => ['Tropical', 'Cane, rattan, plants and fresh greens.', ['#15803d', '#ca8a04', '#fef3c7']],
        'eclectic' => ['Eclectic', 'Mixed eras, patterns and collected pieces that still feel planned.', ['#be123c', '#1d4ed8', '#eab308']],
    ],
    'categories' => [
        'wardrobe' => 'Wardrobes', 'modular_kitchen' => 'Modular kitchens', 'false_ceiling' => 'False ceilings', 'tv_unit' => 'TV units', 'wall_panel' => 'Wall panels & decor',
        'storage' => 'Storage', 'lighting' => 'Lighting', 'flooring' => 'Flooring', 'crockery' => 'Crockery units', 'study_table' => 'Study tables', 'bed' => 'Beds', 'vanity' => 'Bathroom vanities',
    ],

    // Estimator: rooms per home size (editable by the visitor) and base cost per room in the economy package.
    'bhk' => [1 => '1 BHK', 2 => '2 BHK', 3 => '3 BHK', 4 => '4 BHK'],
    'bhk_rooms' => [
        1 => ['living' => 1, 'kitchen' => 1, 'master_bedroom' => 1, 'bathroom' => 1],
        2 => ['living' => 1, 'dining' => 1, 'kitchen' => 1, 'master_bedroom' => 1, 'guest_bedroom' => 1, 'bathroom' => 2],
        3 => ['living' => 1, 'dining' => 1, 'kitchen' => 1, 'master_bedroom' => 1, 'guest_bedroom' => 1, 'kids_bedroom' => 1, 'bathroom' => 3],
        4 => ['living' => 1, 'dining' => 1, 'kitchen' => 1, 'master_bedroom' => 1, 'guest_bedroom' => 2, 'kids_bedroom' => 1, 'study' => 1, 'bathroom' => 4],
    ],
    'room_rates' => [
        'living' => 100000, 'dining' => 50000, 'kitchen' => 150000, 'master_bedroom' => 120000, 'guest_bedroom' => 90000, 'kids_bedroom' => 90000,
        'study' => 60000, 'bathroom' => 40000, 'puja' => 30000, 'balcony' => 25000,
    ],
    'room_includes' => [
        'living' => 'TV unit, shoe rack, false ceiling, lights, wall panel', 'dining' => 'Crockery unit, ceiling, lights', 'kitchen' => 'Modular cabinets, countertop, chimney & hob space, loft',
        'master_bedroom' => 'Wardrobe, bed with storage, side tables, ceiling', 'guest_bedroom' => 'Wardrobe, bed, side table', 'kids_bedroom' => 'Wardrobe, bed, study table',
        'study' => 'Study table, shelves', 'bathroom' => 'Vanity unit, mirror cabinet', 'puja' => 'Mandir unit', 'balcony' => 'Seating, planters, flooring',
    ],
    'packages' => [
        'economy' => ['Economy', 1.0, 'Laminate finishes, standard hardware'],
        'premium' => ['Premium', 1.6, 'Acrylic / PU finishes, soft-close hardware, profile lights'],
        'luxury' => ['Luxury', 2.6, 'Veneer / lacquer, branded fittings, designer lighting'],
    ],
    'range_pct' => 12, // estimate shown as total ± this %
    'max_rooms' => 6,  // per room type

    // Style quiz: each answer adds points to styles.
    'quiz' => [
        ['How should your living room feel?', [['Calm and uncluttered', ['minimal' => 2, 'scandinavian' => 1]], ['Warm and festive', ['contemporary_indian' => 2, 'eclectic' => 1]], ['Elegant and grand', ['modern_classical' => 2]], ['Fresh and green', ['tropical' => 2, 'scandinavian' => 1]]]],
        ['Pick a material', [['Walnut wood', ['mid_century' => 2, 'contemporary_indian' => 1]], ['Light oak', ['scandinavian' => 2, 'minimal' => 1]], ['Marble and brass', ['modern_classical' => 2, 'modern' => 1]], ['Cane and rattan', ['tropical' => 2, 'eclectic' => 1]]]],
        ['Your colour comfort zone', [['Whites and greys', ['minimal' => 2, 'modern' => 1]], ['Earthy browns and ochre', ['contemporary_indian' => 2, 'mid_century' => 1]], ['Bold mixes', ['eclectic' => 2, 'mid_century' => 1]], ['Beige and gold', ['modern_classical' => 2]]]],
        ['What do you do with decor?', [['Keep only what I use', ['minimal' => 2]], ['Display what I collect', ['eclectic' => 2]], ['A few statement pieces', ['modern' => 2, 'modern_classical' => 1]], ['Plants everywhere', ['tropical' => 2]]]],
        ['Pick a sofa', [['Low, boxy, neutral', ['modern' => 2, 'minimal' => 1]], ['Tufted with carved legs', ['modern_classical' => 2]], ['Wooden frame with cushions', ['contemporary_indian' => 1, 'mid_century' => 2]], ['Linen, soft and light', ['scandinavian' => 2]]]],
    ],

    'statuses' => ['new' => 'New', 'contacted' => 'Contacted', 'site_visit' => 'Site visit', 'quoted' => 'Quoted', 'won' => 'Won', 'lost' => 'Lost'],
    'image_max_kb' => 8192,
];
