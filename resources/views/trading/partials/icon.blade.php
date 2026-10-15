@php
    $paths = [
        'rocket' => '<path d="M5 15c-1.5 1.5-2 5-2 5s3.5-.5 5-2"/><path d="M9 11a14 14 0 0 1 11-8c0 4-2 9-8 11l-3-3Z"/><circle cx="15" cy="9" r="1.5"/><path d="M9 11H5l2-4h5M13 15v4l4-2v-5"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4M8 14h2M14 14h2M8 17h2"/>',
        'coins' => '<ellipse cx="9" cy="7" rx="6" ry="3"/><path d="M3 7v4c0 1.7 2.7 3 6 3s6-1.3 6-3V7"/><path d="M9 14v3c0 1.7 2.7 3 6 3s6-1.3 6-3v-4c0-1.7-2.7-3-6-3"/>',
        'building' => '<rect x="5" y="3" width="14" height="18" rx="1"/><path d="M9 7h2M13 7h2M9 11h2M13 11h2M9 15h2M13 15h2M10 21v-3h4v3"/>',
        'buildings' => '<rect x="3" y="8" width="8" height="13" rx="1"/><rect x="11" y="3" width="10" height="18" rx="1"/><path d="M6 12h2M6 16h2M14 7h4M14 11h4M14 15h4"/>',
        'shop' => '<path d="M4 9h16l-1-5H5L4 9Z"/><path d="M5 9v11h14V9M9 20v-6h6v6"/>',
        'layers' => '<path d="m12 3 9 5-9 5-9-5 9-5Z"/><path d="m3 13 9 5 9-5"/>',
        'receipt' => '<path d="M6 3h12v18l-3-2-3 2-3-2-3 2V3Z"/><path d="M9 8h6M9 12h6"/>',
        'line' => '<path d="M3 17l5-5 4 4 8-9"/><path d="M15 7h5v5"/>',
        'grid' => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
        'drop' => '<path d="M12 3s7 7 7 12a7 7 0 0 1-14 0c0-5 7-12 7-12Z"/>',
        'balance' => '<path d="M12 3v18M5 21h14M6 7h12"/><path d="m6 7-3 7h6l-3-7ZM18 7l-3 7h6l-3-7Z"/>',
        'chart' => '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
    ];
@endphp
<svg width="{{ $size ?? 30 }}" height="{{ $size ?? 30 }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $paths[$name] ?? $paths['chart'] !!}</svg>
