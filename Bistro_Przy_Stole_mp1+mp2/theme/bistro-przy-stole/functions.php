<?php
/**
 * Style frontendu i edytora. Sam theme.json definiuje paletę i skalę,
 * a style.css zawiera responsywność i reguły dostępności.
 */
add_action('wp_enqueue_scripts', static function (): void {
    wp_enqueue_style(
        'bistro-przy-stole-mp1',
        get_stylesheet_uri(),
        [],
        wp_get_theme()->get('Version')
    );
});
add_action('after_setup_theme', static function (): void {
    add_theme_support('editor-styles');
    add_editor_style('style.css');
});
