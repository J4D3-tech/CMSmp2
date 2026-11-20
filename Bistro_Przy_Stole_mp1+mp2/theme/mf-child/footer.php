<?php
/** L5: selected override of Twenty Twenty-One footer, with dynamic info block. */
if (!defined('ABSPATH')) { exit; }
?>
</main><!-- #main; otwarte w header.php rodzica -->
        </div><!-- #primary -->
    </div><!-- #content -->
<footer id="colophon" class="site-footer" role="contentinfo">
    <div class="site-info">
        <?php echo do_blocks('<!-- wp:mf-child/info-firmy {"pokazTelefon":false} /-->'); // Output escaped inside render.php. ?>
        <p><?php echo esc_html__('Fikcyjne bistro — projekt edukacyjny MP1/MP2. Treści opracowane z pomocą AI.', 'mf-child'); ?></p>
        <p><a href="<?php echo esc_url(home_url('/polityka-prywatnosci/')); ?>"><?php echo esc_html__('Polityka prywatności', 'mf-child'); ?></a></p>
    </div>
</footer><!-- #colophon -->
</div><!-- #page; otwarte w header.php rodzica -->
<?php wp_footer(); ?>
</body>
</html>
