<?php

namespace App\Support;

/** Per-request page metadata set by page templates (replaces the original $pageTitle/$pageDesc/http_response_code globals). */
class PageMeta
{
    public static ?string $title = null;
    public static ?string $desc = null;
    public static int $status = 200;

    public static function reset(): void
    {
        self::$title = null;
        self::$desc = null;
        self::$status = 200;
    }
}
