<?php
/** L5: page-o-nas.php has priority over parent's page.php. Header opens <main>. */
if (!defined('ABSPATH')) { exit; }
get_header();
while (have_posts()) :
    the_post();
    ?>
    <article id="post-<?php the_ID(); ?>" <?php post_class('mf-o-nas'); ?>>
        <header class="entry-header alignwide">
            <h1 class="entry-title"><?php echo esc_html(get_the_title()); ?></h1>
            <p class="mf-lead"><?php echo esc_html__('Poznaj nasze fikcyjne bistro i pomysł na sezonowe jedzenie.', 'mf-child'); ?></p>
        </header>
        <div class="entry-content">
            <?php the_content(); ?>
            <div class="wp-block-group is-style-mf-karta">
                <h2><?php echo esc_html__('Dane bistro (demonstracja)', 'mf-child'); ?></h2>
                <?php echo do_blocks('<!-- wp:mf-child/info-firmy {"pokazTelefon":true} /-->'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- render.php escapes. ?>
                <div class="wp-block-buttons mf-cta-row"><div class="wp-block-button is-style-mf-cien">
                    <a class="wp-block-button__link" href="<?php echo esc_url(home_url('/menu/')); ?>"><?php echo esc_html__('Zobacz przykładowe menu', 'mf-child'); ?></a>
                </div></div>
            </div>
        </div>
    </article>
    <?php
endwhile;
get_footer();
