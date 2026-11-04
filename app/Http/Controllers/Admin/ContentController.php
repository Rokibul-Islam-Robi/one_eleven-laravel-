<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AdminValidator as Validator;
use App\Support\AdminAuth;
use App\Services\OrderService;
use DateTime;
use PDOException;
use RuntimeException;
use Throwable;

/**
 * Website Content — manage homepage hero slides, promo pills and flash bar copy.
 * These rows are read live by the storefront index.php (with fallback to built-in copy).
 */
final class ContentController extends Controller
{


public static function mod_content(string $action, int $id): void
{
    switch ($action) {
        case 'edit':
            require_module('content', 'edit');
            self::content_form(self::content_find($id));
            break;
        case 'update':
            require_module('content', 'edit');
            self::content_save(self::content_find($id));
            break;
        case 'toggle':
            require_module('content', 'edit');
            self::content_toggle($id);
            break;
        default:
            self::content_list();
    }
}

public static function content_find(int $id): array
{
    $row = qrow('SELECT * FROM page_content WHERE id = ?', [$id]);
    if (!$row) {
        adm_status(404);
        render_layout('Content block not found', 'content', '<div class="adm-card adm-card-pad adm-empty"><h3>Not found</h3><a class="abtn primary mt-3" href="' . e(aurl('content')) . '">Back</a></div>');
    }
    return $row;
}

public static function content_list(): void
{
    $rows = qall('SELECT * FROM page_content ORDER BY sort_order, section_key');

    $html = page_head('Website Content', 'Live copy for the storefront homepage. Edits appear on the site immediately (hero slides, promo pills, flash bar).',
        '<a class="abtn secondary" href="' . e(site_url('')) . '" target="_blank" rel="noopener">' . icon('store') . ' View storefront</a>');

    $html .= '<div class="adm-card"><div class="adm-table-wrap"><table class="adm-table"><thead><tr><th>Section</th><th>Title</th><th>Link</th><th>Active</th><th>Updated</th><th></th></tr></thead><tbody>';
    foreach ($rows as $r) {
        $html .= '<tr><td><span class="cell-main">' . e($r['section_key']) . '</span>'
            . ($r['kicker'] ? '<div class="cell-sub">' . e($r['kicker']) . '</div>' : '') . '</td>'
            . '<td style="max-width:320px">' . e(mb_substr(str_replace("\n", ' ', (string)$r['title']), 0, 70)) . '</td>'
            . '<td><span class="code-chip">' . e($r['link_url'] ?: '—') . '</span></td>'
            . '<td>' . ($r['is_active'] ? status_badge('active') : status_badge('inactive')) . '</td>'
            . '<td>' . e(dt($r['updated_at'], 'd M Y, h:i')) . '</td>'
            . '<td class="actions-cell"><a class="abtn secondary xs" href="' . e(aurl('content/edit/' . (int)$r['id'])) . '">Edit</a>';
        if (can('content', 'edit')) {
            $html .= '<form method="post" action="' . e(aurl('content/toggle/' . (int)$r['id'])) . '" class="d-inline">' . csrf_field()
                . '<button class="abtn secondary xs">' . ($r['is_active'] ? 'Disable' : 'Enable') . '</button></form>';
        }
        $html .= '</td></tr>';
    }
    if (!$rows) {
        $html .= '<tr><td colspan="6" class="adm-empty">No content blocks yet. Run <code>php database/seed.php</code> to create the defaults.</td></tr>';
    }
    $html .= '</tbody></table></div></div>';

    render_layout('Website Content', 'content', $html);
}

public static function content_form(array $r, array $errors = [], array $old = []): void
{
    $v = static function (string $k, string $d = '') use ($r, $old) {
        if ($old) { return (string)($old[$k] ?? $d); }
        return (string)($r[$k] ?? $d);
    };
    $err = static fn(string $k): string => isset($errors[$k]) ? ' err' : '';
    $errmsg = static fn(string $k): string => isset($errors[$k]) ? '<div class="aerr">' . e($errors[$k]) . '</div>' : '';
    $isHero = str_starts_with($r['section_key'], 'hero_');

    $html = page_head('Edit: ' . $r['section_key'], 'Changes go live on the storefront as soon as you save.',
        '<a class="abtn ghost" href="' . e(aurl('content')) . '">← Back</a>');

    $html .= '<div class="adm-grid-2" style="grid-template-columns:1.3fr 1fr"><div class="adm-card"><div class="adm-card-pad">';
    $html .= '<form method="post" action="' . e(aurl('content/update')) . '" novalidate>' . csrf_field()
        . '<input type="hidden" name="id" value="' . (int)$r['id'] . '">';

    $html .= '<div class="afield"><label>Section key <span class="hint">(read-only)</span></label><input class="ainput" value="' . e($r['section_key']) . '" disabled></div>';
    if ($isHero || $r['section_key'] === 'flash_bar') {
        $html .= '<div class="afield"><label>Kicker / label</label><input class="ainput' . $err('kicker') . '" name="kicker" value="' . e($v('kicker')) . '" placeholder="LIMITED TIME">' . $errmsg('kicker') . '</div>';
    }
    $html .= '<div class="afield"><label>Title' . ($isHero ? ' <span class="hint">(use a new line for a line break)</span>' : '') . '</label>'
        . '<textarea class="atextarea' . $err('title') . '" name="title" rows="' . ($isHero ? 3 : 2) . '">' . e($v('title')) . '</textarea>' . $errmsg('title') . '</div>';
    if ($isHero || $r['section_key'] === 'flash_bar') {
        $html .= '<div class="afield"><label>Subtitle</label><input class="ainput' . $err('subtitle') . '" name="subtitle" value="' . e($v('subtitle')) . '">' . $errmsg('subtitle') . '</div>';
    }
    if ($isHero) {
        $html .= '<div class="afield"><label>Promo code text</label><input class="ainput' . $err('body') . '" name="body" value="' . e($v('body')) . '" placeholder="OCT26SUPP1">' . $errmsg('body') . '</div>';
        $html .= '<div class="agrid-2"><div class="afield"><label>CTA link</label><input class="ainput" name="link_url" value="' . e($v('link_url')) . '" placeholder="category/supplements"></div>';
        $html .= '<div class="afield"><label>CTA label</label><input class="ainput" name="link_label" value="' . e($v('link_label')) . '"></div></div>';
        $html .= '<div class="afield"><label>Main image URL</label><input class="ainput' . $err('image_url') . '" name="image_url" value="' . e($v('image_url')) . '">' . $errmsg('image_url') . '</div>';
        $html .= '<div class="afield"><label>Secondary image URL</label><input class="ainput' . $err('image_url_2') . '" name="image_url_2" value="' . e($v('image_url_2')) . '">' . $errmsg('image_url_2') . '</div>';
    } elseif ($r['section_key'] === 'pill_1' || $r['section_key'] === 'pill_2') {
        $html .= '<div class="afield"><label>Link</label><input class="ainput" name="link_url" value="' . e($v('link_url')) . '" placeholder="deals"></div>';
    }
    $html .= '<div class="agrid-2"><div class="afield"><label>Sort order</label><input class="ainput" type="number" name="sort_order" value="' . e($v('sort_order', '0')) . '"></div>';
    $html .= '<div class="afield"><label class="d-flex align-items-center gap-2" style="margin-top:26px"><input type="checkbox" class="adm-check" name="is_active" value="1"' . ($v('is_active', '1') !== '0' ? ' checked' : '') . '> Active on storefront</label></div></div>';

    $html .= '<div class="form-actions"><button class="abtn primary">Save content</button><a class="abtn ghost" href="' . e(aurl('content')) . '">Cancel</a></div>';
    $html .= '</form></div></div>';

    /* live preview */
    $html .= '<div class="adm-card"><div class="adm-card-head"><h3>Preview</h3></div><div class="adm-card-pad"><div class="content-preview">';
    if ($v('kicker') !== '') { $html .= '<span class="abadge green">' . e($v('kicker')) . '</span>'; }
    $html .= '<h4 style="margin-top:8px">' . nl2br(e($v('title'))) . '</h4>';
    if ($v('subtitle') !== '') { $html .= '<p>' . e($v('subtitle')) . '</p>'; }
    if ($v('body') !== '') { $html .= '<p><b>Code: ' . e($v('body')) . '</b></p>'; }
    if ($v('link_label') !== '') { $html .= '<span class="abtn primary sm" style="margin-top:8px">' . e($v('link_label')) . ' →</span>'; }
    $html .= '</div>';
    if ($v('image_url') !== '') {
        $html .= '<div class="mt-3"><img src="' . e($v('image_url')) . '" alt="" style="border-radius:10px;max-height:220px;object-fit:cover;width:100%"></div>';
    }
    $html .= '<p style="font-size:11.5px;color:var(--a-muted);margin-top:12px">Storefront URL: <a href="' . e(site_url('')) . '" target="_blank" rel="noopener">' . e(site_url('')) . '</a></p>';
    $html .= '</div></div></div>';

    render_layout('Edit ' . $r['section_key'], 'content', $html, true);
}

public static function content_save(array $existing): void
{
    verify_csrf_post();
    $v = new Validator(request()->post());
    $v->max('title', 190, 'Title')->max('subtitle', 255, 'Subtitle')->max('kicker', 100, 'Kicker')
      ->max('link_url', 255, 'Link')->max('link_label', 120, 'CTA label')
      ->max('image_url', 255, 'Image URL')->max('image_url_2', 255, 'Image URL');

    foreach (['image_url', 'image_url_2'] as $f) {
        $val = $v->str($f);
        if ($val !== '' && !preg_match('#^https?://#i', $val)) {
            $v->errors[$f] = 'Image must be a full http(s) URL.';
        }
    }
    if ($v->fails()) {
        flash('error', 'Please correct the highlighted fields.');
        self::content_form($existing, $v->errors, request()->post());
        return;
    }

    $data = [
        'kicker' => $v->str('kicker') ?: null,
        'title' => $v->str('title') ?: null,
        'subtitle' => $v->str('subtitle') ?: null,
        'body' => $v->str('body') ?: null,
        'link_url' => $v->str('link_url') ?: null,
        'link_label' => $v->str('link_label') ?: null,
        'image_url' => $v->str('image_url') ?: null,
        'image_url_2' => $v->str('image_url_2') ?: null,
        'is_active' => $v->bool('is_active') ? 1 : 0,
        'sort_order' => $v->int('sort_order', 0),
    ];
    update_row('page_content', $data, (int)$existing['id']);
    log_action('content.update', 'content', (int)$existing['id'], $existing['section_key']);
    flash_redirect('success', 'Content “' . $existing['section_key'] . '” saved — the storefront now shows the updated copy.', 'content');
}

public static function content_toggle(int $id): void
{
    verify_csrf_post();
    $r = self::content_find($id);
    q('UPDATE page_content SET is_active = 1 - is_active WHERE id = ?', [$id]);
    log_action('content.toggle', 'content', $id, $r['section_key']);
    flash_redirect('success', '“' . $r['section_key'] . '” ' . ($r['is_active'] ? 'disabled (storefront falls back to default copy)' : 'enabled') . '.', 'content');
}
}
