<?php
/**
 * MP1 — dynamiczne linki menu dla GitHub Codespaces.
 * Mała wtyczka MU: URL-e są pobierane z aktualnych stron i bieżącej domeny.
 * Nie modyfikuje treści ani danych osobowych.
 */
if (!defined('ABSPATH')) { exit; }
add_filter('render_block_data', static function (array $block): array {
    if (($block['blockName'] ?? '') !== 'core/navigation-link') {
        return $block;
    }
    $attrs = $block['attrs'] ?? [];
    $input_url = (string) ($attrs['url'] ?? '');
    $path = parse_url($input_url, PHP_URL_PATH);
    $slug = is_string($path) ? trim($path, '/') : '';
    $known = [
        'menu' => 'menu', 'rezerwacje' => 'rezerwacje', 'o-nas' => 'o-nas',
        'aktualnosci' => 'aktualnosci', 'kontakt' => 'kontakt',
        'faq' => 'faq', 'polityka-prywatnosci' => 'polityka-prywatnosci',
    ];
    if (!isset($known[$slug])) {
        $label_slug = sanitize_title((string) ($attrs['label'] ?? ''));
        if (isset($known[$label_slug])) {
            $slug = $label_slug;
        } elseif ($slug === '' && in_array($label_slug, ['start', 'strona-glowna'], true)) {
            $block['attrs']['url'] = home_url('/');
            return $block;
        } else {
            return $block; // Pozostałe linki pozostają bez zmian.
        }
    }
    $page = get_page_by_path($known[$slug]);
    if ($page instanceof WP_Post && $page->post_status === 'publish') {
        $block['attrs']['url'] = get_permalink($page);
    }
    return $block;
}, 10, 1);
