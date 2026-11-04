<?php

namespace App\Support;

/** Inline SVG icon set (ported from includes/bootstrap.php $ICONS + ic()). */
class Icons
{
    public const SET = [
        'search'   => '<circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/>',
        'user'     => '<circle cx="12" cy="8" r="3.5"/><path d="M5 20c1.2-3.4 3.8-5 7-5s5.8 1.6 7 5"/>',
        'heart'    => '<path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>',
        'cart'     => '<path d="M4 5h2l2.2 10.2A2 2 0 0 0 10.2 17h7.3a2 2 0 0 0 2-1.5L21 8H7"/><circle cx="10.5" cy="20" r="1.2"/><circle cx="17.5" cy="20" r="1.2"/>',
        'shield'   => '<path d="M12 3l7 3v5.5c0 4.3-3 7.6-7 9.5-4-1.9-7-5.2-7-9.5V6z"/><path d="M9 12l2.2 2.2L15.5 10"/>',
        'truck'    => '<path d="M3 7h11v9H3z"/><path d="M14 10h4l3 3v3h-7z"/><circle cx="7" cy="18" r="1.6"/><circle cx="17.5" cy="18" r="1.6"/>',
        'refresh'  => '<path d="M20 12a8 8 0 1 1-2.3-5.6"/><path d="M20 4v5h-5"/>',
        'star'     => '<path d="M12 3.6l2.5 5.1 5.6.8-4 3.9 1 5.6-5.1-2.7-5.1 2.7 1-5.6-4-3.9 5.6-.8z"/>',
        'check'    => '<path d="M5 12.5l4.5 4.5L19 7.5"/>',
        'flask'    => '<path d="M10 3h4"/><path d="M11 3v6L5.5 18A2 2 0 0 0 7.2 21h9.6a2 2 0 0 0 1.7-3L13 9V3"/><path d="M8.2 15h7.6"/>',
        'leaf'     => '<path d="M5 19c8 1 14-4 14-14-8 0-14 4-14 10a5 5 0 0 0 5 4z"/><path d="M5 19c3-6 7-9 11-10"/>',
        'snow'     => '<path d="M12 3v18M4.2 7.5l15.6 9M19.8 7.5l-15.6 9"/>',
        'phone'    => '<path d="M6 3h4l1.5 4.5-2.3 1.6a12 12 0 0 0 5.7 5.7l1.6-2.3L21 14v4a2 2 0 0 1-2.2 2A16 16 0 0 1 4 5.2 2 2 0 0 1 6 3z"/>',
        'chat'     => '<path d="M21 12a8 8 0 0 1-11.6 7.1L4 20.5l1.5-5A8 8 0 1 1 21 12z"/>',
        'mail'     => '<path d="M3.5 6.5h17v11h-17z"/><path d="M3.5 7l8.5 6 8.5-6"/>',
        'play'     => '<path d="M5 3.5v17l14-8.5z"/>',
        'clock'    => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3.2 2"/>',
        'pill'     => '<rect x="3.2" y="9" width="17.6" height="7.5" rx="3.75" transform="rotate(-40 12 12.75)"/><path d="M9 8.5l6.5 6.5"/>',
        'sparkle'  => '<path d="M12 4l1.6 4.6L18 10.2l-4.4 1.6L12 16.4l-1.6-4.6L6 10.2l4.4-1.6z"/><path d="M18.5 15.5l.8 2 2 .8-2 .8-.8 2-.8-2-2-.8 2-.8z"/>',
        'dumbbell' => '<path d="M4 9v6M7 7v10M17 7v10M20 9v6M7 12h10"/>',
        'basket'   => '<path d="M4 9h16l-1.6 9.2a2 2 0 0 1-2 1.8H7.6a2 2 0 0 1-2-1.8z"/><path d="M8.5 9l2-5M15.5 9l-2-5"/>',
        'bottle'   => '<path d="M10 3h4v3.5l2 3V20a1 1 0 0 1-1 1H9a1 1 0 0 1-1-1V9.5l2-3z"/><path d="M8 13h8"/>',
        'home'     => '<path d="M4 11l8-6.5 8 6.5"/><path d="M6.5 9.5V20h11V9.5"/>',
        'arrow'    => '<path d="M5 12h13"/><path d="M13 6.5l5.5 5.5L13 17.5"/>',
        'plus'     => '<path d="M12 6v12M6 12h12"/>',
        'minus'    => '<path d="M6 12h12"/>',
        'trash'    => '<path d="M4 7h16"/><path d="M9 7V5h6v2"/><path d="M6.5 7l1 13h9l1-13"/>',
        'pin'      => '<path d="M12 21s6.5-6.1 6.5-11a6.5 6.5 0 1 0-13 0C5.5 14.9 12 21 12 21z"/><circle cx="12" cy="10" r="2.4"/>',
        'tag'      => '<path d="M11 3H4v7l10 10 7-7z"/><circle cx="7.5" cy="6.5" r="1.3"/>',
        'grid'     => '<rect x="4" y="4" width="7" height="7" rx="1.5"/><rect x="13" y="4" width="7" height="7" rx="1.5"/><rect x="4" y="13" width="7" height="7" rx="1.5"/><rect x="13" y="13" width="7" height="7" rx="1.5"/>',
        'close'    => '<path d="M6 6l12 12M18 6L6 18"/>',
        'chevron'  => '<path d="M6 9l6 6 6-6"/>',
    ];

    public static function ic($k, $cls = 'ic'): string
    {
        static $w = '<svg class="%s" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">%s</svg>';
        return sprintf($w, e($cls), self::SET[$k] ?? '');
    }
}
