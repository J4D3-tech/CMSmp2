<?php
/**
 * MP1 — dane demonstracyjne; uruchomienie: wp eval-file /work/seed.php.
 * Skrypt nie nadpisuje istniejących stron ani wpisów (zachowuje ręczne zmiany w Gutenberg).
 */
if (!defined('ABSPATH')) { exit(1); }
// Przy pracy z WP-CLI ustaw admina jako kontekst autora tworzonych treści.
$mp1_admins = get_users(['role' => 'administrator', 'number' => 1]);
if (!empty($mp1_admins)) { wp_set_current_user((int) $mp1_admins[0]->ID); }

function mp1_p(string $content): string {
    return '<!-- wp:paragraph --><p>' . esc_html($content) . '</p><!-- /wp:paragraph -->';
}
function mp1_raw_p(string $html, string $class = ''): string {
    $attr = $class ? ' {"className":"' . esc_attr($class) . '"}' : '';
    $css = $class ? ' class="' . esc_attr($class) . '"' : '';
    return '<!-- wp:paragraph' . $attr . ' --><p' . $css . '>' . $html . '</p><!-- /wp:paragraph -->';
}
function mp1_h(string $content, int $level = 2): string {
    return '<!-- wp:heading {"level":' . $level . '} --><h' . $level . ' class="wp-block-heading">' . esc_html($content) . '</h' . $level . '><!-- /wp:heading -->';
}
function mp1_group(string $inner, string $class = 'mp1-section'): string {
    return '<!-- wp:group {"className":"' . esc_attr($class) . '","layout":{"type":"constrained"}} -->' .
        '<div class="wp-block-group ' . esc_attr($class) . '">' . $inner . '</div><!-- /wp:group -->';
}
function mp1_card(string $title, string $description): string {
    return '<!-- wp:column --><div class="wp-block-column">' .
      '<!-- wp:group {"className":"mp1-card","layout":{"type":"constrained"}} -->' .
      '<div class="wp-block-group mp1-card">' . mp1_h($title, 3) . mp1_p($description) . '</div><!-- /wp:group -->' .
      '</div><!-- /wp:column -->';
}
function mp1_columns(string $cards): string {
    return '<!-- wp:columns {"className":"mp1-service-columns"} -->' .
        '<div class="wp-block-columns mp1-service-columns">' . $cards . '</div><!-- /wp:columns -->';
}
function mp1_cta_button(string $label, string $url): string {
    return '<!-- wp:buttons --><div class="wp-block-buttons">' .
      '<!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="' . esc_url($url) . '">' .
      esc_html($label) . '</a></div><!-- /wp:button --></div><!-- /wp:buttons -->';
}
function mp1_hero_home(): string {
    $inner = mp1_h('Dobry smak zaczyna się przy stole', 1) .
      mp1_p('Bistro Przy Stole — fikcyjna restauracja z prostym, sezonowym menu na lunch i spotkania.') .
      mp1_cta_button('Zobacz menu', '/menu/');
    return '<!-- wp:cover {"dimRatio":100,"overlayColor":"accent","isUserOverlayColor":true,"minHeight":410,"minHeightUnit":"px","className":"mp1-hero","layout":{"type":"constrained"}} -->' .
      '<div class="wp-block-cover mp1-hero" style="min-height:410px"><span aria-hidden="true" class="wp-block-cover__background has-accent-background-color has-background-dim-100 has-background-dim"></span>' .
      '<div class="wp-block-cover__inner-container">' . $inner . '</div></div><!-- /wp:cover -->';
}
function mp1_hero_offer_copy(): string {
    // Niezależna kopia wzorca patterns/hero-cta.php umieszczona na stronie Menu.
    return '<!-- wp:group {"className":"mp1-hero","backgroundColor":"accent","layout":{"type":"constrained"}} -->' .
      '<div class="wp-block-group mp1-hero has-accent-background-color has-background">' .
      mp1_h('Zaplanuj wizytę przy naszym stole', 2) .
      mp1_p('Sprawdź menu i wyślij demonstracyjne zapytanie o stolik — bez rzeczywistej rezerwacji.') .
      mp1_cta_button('Zapytaj o stolik (demo)', '/rezerwacje/') .
      '</div><!-- /wp:group -->';
}
function mp1_create_page(string $slug, string $title, string $content): int {
    $existing = get_page_by_path($slug, OBJECT, 'page');
    if ($existing) { return (int) $existing->ID; }
    $result = wp_insert_post([
      'post_type' => 'page', 'post_status' => 'publish', 'post_name' => $slug,
      'post_title' => $title, 'post_content' => wp_slash($content),
    ], true);
    if (is_wp_error($result)) { WP_CLI::error($result->get_error_message()); }
    return (int) $result;
}
function mp1_create_post(string $slug, string $title, string $content, int $category_id): void {
    if (get_page_by_path($slug, OBJECT, 'post')) { return; }
    $result = wp_insert_post([
      'post_type' => 'post', 'post_status' => 'publish', 'post_name' => $slug,
      'post_title' => $title, 'post_content' => wp_slash($content),
      'post_category' => [$category_id],
    ], true);
    if (is_wp_error($result)) { WP_CLI::error($result->get_error_message()); }
}

function mp1_contact_shortcode(): string {
    if (!function_exists('wpcf7_save_contact_form')) { return ''; }
    $existing = get_posts([
      'post_type' => 'wpcf7_contact_form', 'post_status' => 'any',
      'posts_per_page' => 50, 'suppress_filters' => true,
    ]);
    foreach ($existing as $form) {
        if ($form->post_title === 'Zapytanie o stolik MP1') {
            return '[contact-form-7 id="' . (int) $form->ID . '" title="Zapytanie o stolik MP1"]';
        }
    }
    // Formularz demonstracyjny. Nie obsługuje automatycznego rezerwowania stolików.
    $form_html = <<<'FORM'
<label>Imię i nazwisko (wymagane)
[text* your-name autocomplete:name]</label>
<label>Adres e-mail (wymagany)
[email* your-email autocomplete:email]</label>
<label>Proponowany termin wizyty (tekst, np. sobota, 18:00)
[text visit-date]</label>
<label>Liczba osób (wpisz liczbę)
[number people min:1 max:12]</label>
<label>Wiadomość (wymagana)
[textarea* your-message]</label>
[acceptance zgoda-rodo] Zapoznałem/-am się z <a href="/polityka-prywatnosci/">polityką prywatności</a>. [/acceptance]
[submit "Wyślij zapytanie (demo)"]
FORM;
    $cf = wpcf7_save_contact_form([
      'title' => 'Zapytanie o stolik MP1', 'locale' => 'pl_PL', 'form' => $form_html,
    ], 'save');
    return $cf && method_exists($cf, 'id')
      ? '[contact-form-7 id="' . (int) $cf->id() . '" title="Zapytanie o stolik MP1"]' : '';
}

$home = mp1_hero_home();
$home .= mp1_group(
  mp1_h('Co znajdziesz w naszym bistro?') .
  mp1_columns(
    mp1_card('Lunch w przerwie', 'Proste dania i czytelne menu dla osób, które mają mało czasu.') .
    mp1_card('Sezonowe propozycje', 'Przykładowa karta z daniami i składnikami zmieniającymi się wraz z porą roku.') .
    mp1_card('Spotkanie przy stole', 'Miejsce na spokojny posiłek i rozmowę z bliskimi lub znajomymi.')
  )
);
$home .= mp1_group(
  mp1_h('Przykładowa opinia gościa') .
  '<!-- wp:quote {"className":"mp1-quote"} --><blockquote class="wp-block-quote mp1-quote">' .
  mp1_p('Przytulne wnętrze i krótkie menu — łatwo wybrać coś na lunch.') .
  '<cite>Fikcyjna wypowiedź demonstracyjna, nie jest opinią prawdziwego gościa.</cite>' .
  '</blockquote><!-- /wp:quote -->'
);
$home .= mp1_group(
  mp1_h('Masz ochotę wpaść?') .
  mp1_p('Sprawdź przykładowe pozycje w menu i przejdź do formularza demonstracyjnego.') .
  mp1_cta_button('Zapytaj o stolik (demo)', '/rezerwacje/')
);

$menu = mp1_p('Menu jest demonstracyjne: nazwy, skład i ceny nie są ofertą handlową prawdziwej restauracji. Informacje o alergenach są przykładowe — przed realną sprzedażą wymagają weryfikacji.');
$menu .= mp1_group(mp1_h('Dania główne') . mp1_columns(
  mp1_card('Krem pomidorowy — 19 zł', 'Z grzankami i bazylią; przykładowy alergen: gluten w grzankach.') .
  mp1_card('Makaron z pieczonymi warzywami — 36 zł', 'Z sosem z pomidorów i ziołami; przykładowy alergen: gluten w makaronie.') .
  mp1_card('Kurczak z kaszą i warzywami — 42 zł', 'Pieczony filet, kasza i sezonowe warzywa; skład orientacyjny.')
));
$menu .= mp1_group(mp1_h('Coś słodkiego i napoje') . mp1_columns(
  mp1_card('Sernik domowy — 18 zł', 'Porcja ciasta; przykładowe alergeny: mleko, jaja, gluten.') .
  mp1_card('Lemoniada cytrynowa — 14 zł', 'Napój bezalkoholowy z miętą; skład orientacyjny.') .
  mp1_card('Kawa czarna — 12 zł', 'Klasyczna gorąca kawa; szczegóły zależą od sposobu podania.')
));
$menu .= mp1_group(mp1_hero_offer_copy());

$reservations = mp1_p('Ta podstrona pokazuje proces zapytania o stolik. Nie jest połączona z kalendarzem i nie tworzy prawdziwych rezerwacji.');
$reservations .= mp1_group(mp1_h('Jak wyglądałaby rezerwacja?') .
  mp1_p('1. Gość sprawdza menu. 2. Podaje preferowany termin i liczbę osób w formularzu demonstracyjnym. 3. W rzeczywistej restauracji obsługa potwierdza dostępność; w projekcie MP1 nie ma takiego potwierdzenia.'));
$reservations .= mp1_group(mp1_h('Wyślij zapytanie testowe') .
  mp1_p('Użyj wyłącznie fikcyjnych danych; poczta wychodząca nie jest skonfigurowana. Formularz znajduje się na stronie Kontakt.') .
  mp1_cta_button('Przejdź do formularza (demo)', '/kontakt/'));

$about = mp1_p('Bistro Przy Stole to fikcyjna restauracja stworzona na potrzeby zadania MP1 z systemów CMS. Nie ma prawdziwego lokalu ani godzin otwarcia.');
$about .= mp1_group(mp1_h('Nasz pomysł') . mp1_p('Krótka karta dań, sezonowe dodatki i swobodna atmosfera. Taką tożsamość komunikuje prosta strona z czytelnym menu.'));
$about .= mp1_group(mp1_h('Dla kogo?') . mp1_p('Dla osoby szukającej szybkiego lunchu oraz dla grupy planującej wspólny posiłek.'));

$faq = mp1_h('Czy bistro działa naprawdę?') . mp1_p('Nie. Jest to wyłącznie demonstracja WordPress na potrzeby projektu MP1.');
$faq .= mp1_h('Czy mogę zarezerwować stolik?') . mp1_p('Nie. Formularz jest demonstracyjny i nie potwierdza dostępności miejsc.');
$faq .= mp1_h('Czy ceny i informacje o alergenach są aktualne?') . mp1_p('Nie. Wszystkie ceny, składniki i alergeny stanowią fikcyjne przykłady.');
$faq .= mp1_h('Jak przetestować formularz?') . mp1_p('Użyj fikcyjnego imienia i testowego e-maila. Nie przesyłaj danych prawdziwych osób.');

$privacy = mp1_raw_p('<strong>Szablon edukacyjny, nie gotowa polityka dla prawdziwej restauracji.</strong> Przed publikacją komercyjną należy wskazać rzeczywistego administratora i sprawdzić obowiązki prawne.', 'mp1-note');
$privacy .= mp1_h('1. Administrator danych') . mp1_p('Bistro Przy Stole jest fikcyjną restauracją używaną w projekcie studenckim MP1. Nie działa jako rzeczywisty przedsiębiorca.');
$privacy .= mp1_h('2. Formularz zapytania o stolik') . mp1_p('Formularz służy do demonstracji. Nie wysyłaj prawdziwych danych. Wersja demo nie ma konfiguracji SMTP ani mechanizmu potwierdzania rezerwacji.');
$privacy .= mp1_h('3. Cookies') . mp1_p('Projekt nie dodaje reklam ani analityki. WordPress może stosować techniczne pliki cookie, zwłaszcza do logowania. Dodanie wtyczek wymaga osobnej oceny cookies.');
$privacy .= mp1_h('4. Obowiązki przed publikacją') . mp1_p('Prawdziwa restauracja musi uzupełnić informacje o administratorze, celach i podstawach przetwarzania, prawach osób, retencji i odbiorcach danych.');
$privacy .= mp1_h('5. Kontakt') . mp1_p('kontakt@bistro-przy-stole.example to nieczynny adres przykładowy, nie służy do składania rezerwacji.');

$contact = mp1_p('Masz pytanie o przykładowe menu lub chcesz sprawdzić formularz? To demonstracja, nie prawdziwa restauracja.');
$contact .= mp1_raw_p('<strong>Ważne:</strong> nie przesyłaj prawdziwych danych; formularz nie gwarantuje wysłania wiadomości i nie potwierdza stolika.', 'mp1-note');
$shortcode = mp1_contact_shortcode();
if ($shortcode) {
    $contact .= '<!-- wp:shortcode -->' . $shortcode . '<!-- /wp:shortcode -->';
} else {
    $contact .= mp1_raw_p('Formularz pojawi się po poprawnym zainstalowaniu i włączeniu wtyczki Contact Form 7. Uruchom ponownie <code>bash scripts/start.sh</code>.');
}
$contact .= mp1_raw_p('Przykładowy e-mail: <a href="mailto:kontakt@bistro-przy-stole.example">kontakt@bistro-przy-stole.example</a>.');

$home_id = mp1_create_page('start', 'Start', $home);
mp1_create_page('menu', 'Menu', $menu);
mp1_create_page('rezerwacje', 'Rezerwacje', $reservations);
mp1_create_page('o-nas', 'O nas', $about);
$news_id = mp1_create_page('aktualnosci', 'Aktualności', '');
$contact_id = mp1_create_page('kontakt', 'Kontakt', $contact);
$current_contact = get_post($contact_id);
if ($shortcode && $current_contact &&
    strpos($current_contact->post_content, 'Formularz pojawi się po poprawnym') !== false &&
    strpos($current_contact->post_content, '[contact-form-7') === false) {
    wp_update_post(['ID' => $contact_id, 'post_content' => wp_slash($contact)]);
}
$privacy_id = mp1_create_page('polityka-prywatnosci', 'Polityka prywatności', $privacy);
mp1_create_page('faq', 'FAQ', $faq);

update_option('show_on_front', 'page');
update_option('page_on_front', $home_id);
update_option('page_for_posts', $news_id);
update_option('wp_page_for_privacy_policy', $privacy_id);
update_option('timezone_string', 'Europe/Warsaw');

if (!term_exists('nowosci', 'category')) { wp_insert_term('Nowości', 'category', ['slug' => 'nowosci']); }
if (!term_exists('kulinaria', 'category')) { wp_insert_term('Kulinarne inspiracje', 'category', ['slug' => 'kulinaria']); }
$category_news = get_term_by('slug', 'nowosci', 'category');
$category_food = get_term_by('slug', 'kulinaria', 'category');
if (!$category_news || !$category_food) { WP_CLI::error('Nie można odtworzyć kategorii wpisów.'); }

mp1_create_post('przykladowe-menu-na-jesien', 'Przykładowe menu na jesień',
    mp1_p('Krótkie, sezonowe menu może pomóc gościom szybko wybrać posiłek. W Bistro Przy Stole to pomysł demonstracyjny.') .
    mp1_h('Co znajdziesz w karcie?') . mp1_p('Zupa, prosty makaron i jedno danie mięsne — wszystkie jako przykładowe pozycje.') .
    mp1_raw_p('Szkic tekstu z pomocą AI. Treść i oferta są fikcyjne.', 'mp1-note'),
    (int) $category_news->term_id);
mp1_create_post('jak-wybrac-lunch', 'Jak wybrać lunch w krótkiej przerwie',
    mp1_p('Czytelne menu i proste opisy potraw pozwalają szybciej zdecydować, na co masz ochotę.') .
    mp1_h('Wskazówka') . mp1_p('Warto sprawdzić skład i zapytać obsługę o alergeny. W tym projekcie dane są przykładowe.') .
    mp1_raw_p('Szkic tekstu z pomocą AI. Treść i oferta są fikcyjne.', 'mp1-note'),
    (int) $category_food->term_id);
mp1_create_post('spotkanie-przy-stole', 'Pomysł na spokojne spotkanie przy stole',
    mp1_p('Restauracja może być miejscem rozmowy ze znajomymi. Dobrze zaplanowane menu ułatwia wspólne wyjście.') .
    mp1_h('Na co zwrócić uwagę?') . mp1_p('W rzeczywistym lokalu warto sprawdzić godziny otwarcia i dostępność stolików. To bistro jest fikcyjne.') .
    mp1_raw_p('Szkic tekstu z pomocą AI. Treść i oferta są fikcyjne.', 'mp1-note'),
    (int) $category_food->term_id);

$hello = get_page_by_path('hello-world', OBJECT, 'post');
if ($hello && $hello->post_title === 'Hello world!') { wp_delete_post($hello->ID, true); }
$sample_page = get_page_by_path('sample-page', OBJECT, 'page');
if ($sample_page && $sample_page->post_title === 'Sample Page') { wp_delete_post($sample_page->ID, true); }
WP_CLI::success('MP1 restauracja: 8 stron, 2 kategorie, 3 wpisy, strona główna i Aktualności. Istniejących treści nie nadpisano.');


// MP2/L5: klasyczny motyw Twenty Twenty-One używa menu w lokacji primary.
// Tworzymy je tylko w motywie potomnym; inne motywy zachowują swoją nawigację blokową.
if (get_stylesheet() === 'mf-child') {
    $menu_name = 'Menu Bistro Przy Stole MP2';
    $menu = wp_get_nav_menu_object($menu_name);
    $menu_id = $menu ? (int) $menu->term_id : wp_create_nav_menu($menu_name);
    if (!is_wp_error($menu_id)) {
        $items = wp_get_nav_menu_items($menu_id);
        if (empty($items)) {
            foreach (array(
                array('Start','/'),array('Menu','/menu/'),array('Rezerwacje','/rezerwacje/'),
                array('O nas','/o-nas/'),array('Aktualności','/aktualnosci/'),array('Kontakt','/kontakt/')
            ) as $pair) {
                wp_update_nav_menu_item($menu_id, 0, array(
                    'menu-item-title' => $pair[0], 'menu-item-url' => home_url($pair[1]),
                    'menu-item-status' => 'publish', 'menu-item-type' => 'custom'
                ));
            }
        }
        $locations = get_theme_mod('nav_menu_locations', array());
        if (!isset($locations['primary']) || (int) $locations['primary'] !== (int) $menu_id) {
            $locations['primary'] = (int) $menu_id;
            set_theme_mod('nav_menu_locations', $locations);
        }
    }
}
