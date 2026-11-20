<?php
/** L5: actions, filters, block styles, i18n and dynamic block. */
if (!defined('ABSPATH')) { exit; }

add_action('after_setup_theme', 'mf_child_setup');
function mf_child_setup() {
    load_child_theme_textdomain('mf-child', get_stylesheet_directory() . '/languages');
    add_theme_support('editor-styles');
    add_editor_style('style.css');
}

add_action('wp_enqueue_scripts', 'mf_child_enqueue_styles');
function mf_child_enqueue_styles() {
    // Parent Twenty Twenty-One already registers its own stylesheet under this handle.
    wp_enqueue_style(
        'mf-child-style',
        get_stylesheet_uri(),
        array('twenty-twenty-one-style'),
        wp_get_theme()->get('Version')
    );
}

add_filter('excerpt_length', 'mf_excerpt_length', 20);
function mf_excerpt_length($length) {
    return 25;
}

add_filter('the_content', 'mf_add_note');
function mf_add_note($content) {
    if (is_single() && in_the_loop() && is_main_query()) {
        $note = esc_html__('Masz pytania o przykładowe menu? Napisz do nas — to demonstracja.', 'mf-child');
        $content .= '<p class="mf-note">' . $note . '</p>';
    }
    return $content;
}

add_action('init', 'mf_register_block_styles');
function mf_register_block_styles() {
    register_block_style('core/button', array(
        'name' => 'mf-cien',
        'label' => __('Z cieniem', 'mf-child'),
        'inline_style' => '.wp-block-button.is-style-mf-cien .wp-block-button__link {'
            . 'background:#85351f;color:#fff;box-shadow:0 5px 0 #442016;border-radius:8px;}',
    ));
    register_block_style('core/group', array(
        'name' => 'mf-karta',
        'label' => __('Karta', 'mf-child'),
        'inline_style' => '.wp-block-group.is-style-mf-karta {'
            . 'background:#fffaf3;color:#29231f;border:2px solid #b8957f;border-radius:14px;padding:1.5rem;}',
    ));
}

add_action('init', 'mf_register_info_block');
function mf_register_info_block() {
    register_block_type(get_stylesheet_directory() . '/blocks/info-firmy');
}
