<?php
/**
 * Inline SVG icon set for the admin sidebar / UI (stroke style, matches storefront icons).
 */


const ADMIN_ICONS = [
    'dashboard' => '<rect x="3.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="3.5" width="7" height="4.5" rx="1.5"/><rect x="13.5" y="10.5" width="7" height="10" rx="1.5"/><rect x="3.5" y="13.5" width="7" height="7" rx="1.5"/>',
    'products'  => '<path d="M12 3l8 4.5v9L12 21l-8-4.5v-9z"/><path d="M12 12l8-4.5M12 12v9M12 12L4 7.5"/>',
    'doctor'    => '<path d="M6 3h12v4a6 6 0 0 1-12 0z"/><path d="M12 13v5a3 3 0 0 0 3 3h1"/><circle cx="18" cy="17" r="2"/>',
    'coupon'    => '<path d="M3 9a2.5 2.5 0 0 0 0 6v3h18v-3a2.5 2.5 0 0 1 0-6V6H3z"/><path d="M12 6v12" stroke-dasharray="2 3"/>',
    'offer'     => '<path d="M20.6 13.4L12 22l-9-9V3h10z"/><circle cx="7.5" cy="7.5" r="1.5"/>',
    'courier'   => '<path d="M3 7h11v9H3z"/><path d="M14 10h4l3 3v3h-7z"/><circle cx="7" cy="18" r="1.6"/><circle cx="17.5" cy="18" r="1.6"/>',
    'rx'        => '<path d="M6 3h12v18H6z"/><path d="M9 8h6M9 12h6M9 16h3"/>',
    'inventory' => '<path d="M4 20V9l8-5 8 5v11"/><path d="M9 20v-6h6v6"/><path d="M4 9h16"/>',
    'orders'    => '<path d="M6 2h12l2 5H4z"/><path d="M4 7v13h16V7"/><path d="M10 11h4"/>',
    'customers' => '<circle cx="12" cy="8" r="3.5"/><path d="M5 20c1.2-3.4 3.8-5 7-5s5.8 1.6 7 5"/>',
    'content'   => '<path d="M4 5h16v14H4z"/><path d="M4 9h16M9 9v10"/>',
    'reports'   => '<path d="M4 20V4"/><path d="M4 20h16"/><path d="M8 16v-5M12.5 16V8M17 16v-7"/>',
    'search'    => '<circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/>',
    'logout'    => '<path d="M14 4H6v16h8"/><path d="M10 12h10M17 8.5l3.5 3.5L17 15.5"/>',
    'store'     => '<path d="M4 9l1.5-5h13L20 9"/><path d="M4 9v11h16V9"/><path d="M9 20v-6h6v6"/>',
    'plus'      => '<path d="M12 5v14M5 12h14"/>',
    'edit'      => '<path d="M4 20h4L19 9l-4-4L4 16z"/><path d="M13.5 6.5l4 4"/>',
    'trash'     => '<path d="M4 7h16"/><path d="M9 7V4h6v3"/><path d="M6 7l1 13h10l1-13"/>',
    'eye'       => '<path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6z"/><circle cx="12" cy="12" r="2.5"/>',
    'alert'     => '<path d="M12 3l10 17H2z"/><path d="M12 10v4M12 17.2v.1"/>',
    'check'     => '<path d="M5 12.5l4.5 4.5L19 7.5"/>',
    'copy'      => '<rect x="9" y="9" width="11" height="11" rx="2"/><path d="M5 15V5a2 2 0 0 1 2-2h10"/>',
    'calendar'  => '<rect x="3.5" y="5" width="17" height="15" rx="2"/><path d="M3.5 10h17M8 3v4M16 3v4"/>',
    'pill'      => '<rect x="3.2" y="9" width="17.6" height="7.5" rx="3.75" transform="rotate(-40 12 12.75)"/><path d="M9 8.5l6.5 6.5"/>',
    'settings'  => '<circle cx="12" cy="12" r="3"/><path d="M12 2.5l1.2 2.4 2.6-.6 1 2.5 2.6.6-.6 2.6L21 12l-2.2 1.9.6 2.6-2.6.6-1 2.5-2.6-.6L12 21.5l-1.2-2.4-2.6.6-1-2.5-2.6-.6.6-2.6L3 12l2.2-1.9-.6-2.6 2.6-.6 1-2.5 2.6.6z"/>',
    'download'  => '<path d="M12 4v11M7.5 11l4.5 4.5L16.5 11"/><path d="M4 20h16"/>',
    'filter'    => '<path d="M3 5h18l-7 8v6l-4 2v-8z"/>',
];

function icon(string $key, string $cls = 'ic'): string
{
    $body = ADMIN_ICONS[$key] ?? ADMIN_ICONS['dashboard'];
    return '<svg class="' . e($cls) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $body . '</svg>';
}
