<?php
/** L5: dynamic server-side render: all database data escaped at output. */
if (!defined('ABSPATH')) { exit; }
$mf_firma = get_bloginfo('name');
$mf_telefon = get_option('mf_bistro_telefon', '+48 000 000 000'); // demonstracja, nie prawdziwy numer
$mf_tel_url = 'tel:' . preg_replace('/[^0-9+]/', '', $mf_telefon);
?>
<div <?php echo get_block_wrapper_attributes(array('class'=>'mf-info-firmy')); // WP outputs escaped attributes. ?>>
    <p><strong><?php echo esc_html($mf_firma); ?></strong></p>
    <?php if (!empty($attributes['pokazTelefon'])): ?>
        <p><?php echo esc_html__('Numer demonstracyjny:', 'mf-child'); ?>
            <a href="<?php echo esc_url($mf_tel_url); ?>"><?php echo esc_html($mf_telefon); ?></a>
        </p>
    <?php endif; ?>
</div>
