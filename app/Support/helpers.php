<?php
/* Global helpers carried over from the original includes/bootstrap.php.
   Laravel already provides e(), url() and base_path(); the originals that
   would collide are exposed under new names (site_base_path, ...). */

if (! function_exists('money')) {
    /** "1234" -> "৳1,234" (non-numeric strings pass through). */
    function money($s)
    {
        return is_numeric($s) ? '৳' . number_format((float) $s) : $s;
    }
}

if (! function_exists('priceval')) {
    /** "৳1,299" -> 1299.0 */
    function priceval($s): float
    {
        return (float) preg_replace('/[^0-9.]/', '', (string) $s);
    }
}

if (! function_exists('site_base_path')) {
    /** Sub-folder the app is served from ('' when at the web root). Original: base_path(). */
    function site_base_path(): string
    {
        return rtrim(request()->getBasePath(), '/');
    }
}

if (! function_exists('href')) {
    /** Relative href helper (the storefront layout emits a <base> tag). */
    function href(string $path): string
    {
        return ltrim($path, '/');
    }
}
