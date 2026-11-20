<?php
/**
 * Title: Bistro — trzy korzyści
 * Slug: bistro-przy-stole/uslugi
 * Categories: featured, services
 * Description: Trzy kolumny z korzyściami restauracji.
 */
?>
<!-- wp:group {"className":"mp1-section","layout":{"type":"constrained"}} --><div class="wp-block-group mp1-section">
<!-- wp:heading {"level":2} --><h2 class="wp-block-heading"><?php esc_html_e('Co znajdziesz w naszym bistro?','bistro-przy-stole'); ?></h2><!-- /wp:heading -->
<!-- wp:columns {"className":"mp1-service-columns"} --><div class="wp-block-columns mp1-service-columns">
<?php
$items = [
 ['Lunch w przerwie','Proste dania i czytelne menu dla osób, które mają mało czasu.'],
 ['Sezonowe propozycje','Przykładowa karta dań i składników zmieniających się wraz z porą roku.'],
 ['Spotkanie przy stole','Miejsce na spokojny posiłek i rozmowę ze znajomymi.'],
];
foreach ($items as $item): ?>
<!-- wp:column --><div class="wp-block-column"><!-- wp:group {"className":"mp1-card","layout":{"type":"constrained"}} --><div class="wp-block-group mp1-card">
<!-- wp:heading {"level":3} --><h3 class="wp-block-heading"><?php echo esc_html($item[0]); ?></h3><!-- /wp:heading -->
<!-- wp:paragraph --><p><?php echo esc_html($item[1]); ?></p><!-- /wp:paragraph -->
</div><!-- /wp:group --></div><!-- /wp:column -->
<?php endforeach; ?>
</div><!-- /wp:columns --></div><!-- /wp:group -->
