<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * CMS copy (`page_content` rows) with the original hard-coded fallbacks.
 * Ported from storefront_content() in includes/bootstrap.php.
 */
class Content
{
    private static ?array $rows = null;
    private static bool $ok = true;

    public static function get(string $key, array $defaults): array
    {
        if (! self::$ok) {
            return $defaults;
        }
        try {
            if (self::$rows === null) {
                self::$rows = [];
                $q = DB::table('page_content')->where('is_active', 1)
                    ->get(['section_key', 'kicker', 'title', 'subtitle', 'body', 'link_url', 'link_label', 'image_url', 'image_url_2']);
                foreach ($q as $r) {
                    $r = (array) $r;
                    $k = $r['section_key'];
                    unset($r['section_key']);
                    self::$rows[$k] = $r;
                }
            }
            if (! isset(self::$rows[$key])) {
                return $defaults;
            }
            foreach (self::$rows[$key] as $k => $v) {
                if ($v !== null && $v !== '') {
                    $defaults[$k] = $v;
                }
            }
            return $defaults;
        } catch (\Throwable $ex) {
            self::$ok = false;
            return $defaults;
        }
    }
}
