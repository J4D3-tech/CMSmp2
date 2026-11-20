#!/usr/bin/env bash
# MP1 + MP2 — Bistro Przy Stole. Jeden skrypt Bash: start, warianty motywu, kontrola, WXR.
# Pierwszy start i powtórne uruchomienie w Codespaces; nie usuwa danych WP.
set -Eeuo pipefail
cd "$(dirname "${BASH_SOURCE[0]}")/.."

usage() {
  cat <<'USAGE'
MP1 + MP2 — Bistro Przy Stole (GitHub Codespaces)
  bash scripts/start.sh            # uruchom / ponownie skonfiguruj serwis
  bash scripts/start.sh --block    # aktywuj samodzielny motyw blokowy L4
  bash scripts/start.sh --child    # zainstaluj TT1 i aktywuj potomny motyw L5
  bash scripts/start.sh --check    # sprawdź zawartość, port i linki
  bash scripts/start.sh --export   # eksport treści WordPress do WXR/XML
  bash scripts/start.sh --help     # pomoc

Jeśli trzeba, podaj dokładny adres z zakładki PORTS:
  SITE_URL='https://NAZWA-8080.app.github.dev' bash scripts/start.sh
USAGE
}

mode="${1:-start}"
case "$mode" in start|--block|--child|--check|--export|--help) ;; *) usage >&2; exit 2 ;; esac
if [[ "$mode" == --help ]]; then usage; exit 0; fi

if ! command -v docker >/dev/null 2>&1 || ! docker compose version >/dev/null 2>&1; then
  echo 'BŁĄD: Docker Compose nie jest dostępny. Uruchom repozytorium w GitHub Codespaces.' >&2
  exit 1
fi

if [[ "$mode" == start || "$mode" == --block || "$mode" == --child ]]; then
  if [[ ! -f .env ]]; then
  umask 077
  if command -v openssl >/dev/null 2>&1; then
    random_secret() { openssl rand -hex 20; }
  else
    random_secret() { python3 -c 'import secrets; print(secrets.token_hex(20))'; }
  fi
  cat > .env <<SECRETS
DB_PASSWORD=$(random_secret)
DB_ROOT_PASSWORD=$(random_secret)
WP_ADMIN_USER=mp1admin
WP_ADMIN_PASSWORD=$(random_secret)
WP_ADMIN_EMAIL=admin@example.test
SECRETS
  echo 'Utworzono prywatny .env z losowymi hasłami (nie jest dodawany do Git).'
  fi
fi
if [[ ! -f .env ]]; then
  echo 'Brak .env. Najpierw uruchom: bash scripts/start.sh' >&2
  exit 1
fi

if [[ -n "${SITE_URL:-}" ]]; then
  site_url="${SITE_URL%/}"
elif [[ -n "${CODESPACE_NAME:-}" ]]; then
  site_url="https://${CODESPACE_NAME}-8080.${GITHUB_CODESPACES_PORT_FORWARDING_DOMAIN:-app.github.dev}"
else
  echo 'Nie wykryto CODESPACE_NAME. Podaj SITE_URL z zakładki PORTS.' >&2
  exit 1
fi
# Zapobiega przypadkowemu ustawieniu localhost i wstrzykiwaniu kodu do wp-config.php.
if [[ ! "$site_url" =~ ^https://[a-zA-Z0-9.-]+$ ]] || [[ "$site_url" != *-8080.* ]]; then
  echo "Niepoprawny adres przekierowanego portu 8080: $site_url" >&2
  exit 1
fi
site_host="${site_url#https://}"
wp() { docker compose --profile tools run --rm -T cli "$@"; }

if [[ "$mode" == --export ]]; then
  mkdir -p dokumentacja/eksport
  target=dokumentacja/eksport/MP1-tresci-WXR.xml
  wp core is-installed >/dev/null
  wp export --stdout > "$target"
  if ! grep -q '<rss' "$target"; then rm -f "$target"; echo 'Błąd eksportu WXR' >&2; exit 1; fi
  echo "Eksport wykonany: $target"
  exit 0
fi

check_site() {
  printf '\n=== Stan instalacji ===\n'
  wp core is-installed >/dev/null
  printf 'Adres WordPress: %s\n' "$(wp option get home)"
  printf 'Język: %s\n' "$(wp option get WPLANG)"
  printf 'Motyw: %s\n' "$(wp theme list --status=active --field=name)"
  printf 'Motyw blokowy L4: %s\n' "$(wp theme is-installed bistro-przy-stole >/dev/null 2>&1 && echo dostępny || echo brak)"
  printf 'Motyw potomny L5: %s\n' "$(wp theme is-installed mf-child >/dev/null 2>&1 && echo dostępny || echo brak)"
  printf 'Strony (oczekiwane 8): %s\n' "$(wp post list --post_type=page --post_status=publish --format=count)"
  printf 'Wpisy (oczekiwane 3): %s\n' "$(wp post list --post_type=post --post_status=publish --format=count)"
  printf 'Link Kontakt: %s/kontakt/\n' "$site_url"
  printf '\n=== Test odpowiedzi Apache przez port 8080 ===\n'
  if command -v curl >/dev/null 2>&1; then
    for path in / /menu/ /kontakt/ /rezerwacje/ /o-nas/; do
      code="$(curl -sS --max-time 15 -o /dev/null -w '%{http_code}' \
        -H "Host: $site_host" -H 'X-Forwarded-Proto: https' \
        "http://127.0.0.1:8080${path}" || true)"
      printf '%-18s HTTP %s\n' "$path" "${code:-brak połączenia}"
    done
    echo 'Przekierowania (nie powinny zawierać localhost):'
    curl -sSI --max-time 15 -H "Host: $site_host" \
      -H 'X-Forwarded-Proto: https' 'http://127.0.0.1:8080/kontakt/' \
      | grep -Ei '^HTTP/|^location:' || true
  else
    echo 'Nie znaleziono curl; otwórz port 8080 w zakładce PORTS.'
  fi
  printf '\nSprawdź też ręcznie wygląd mobilny oraz raport Lighthouse/WAVE.\n'
}
if [[ "$mode" == --check ]]; then check_site; exit 0; fi

printf '\n1/7 — Uruchamianie MariaDB i WordPress ...\n'
docker compose up -d db wordpress
ready=0
for ((i=0; i<60; i++)); do
  if docker compose exec -T wordpress test -f /var/www/html/wp-config.php >/dev/null 2>&1; then
    ready=1; break
  fi
  sleep 2
done
if [[ "$ready" != 1 ]]; then
  echo 'Brak wp-config.php. Diagnostyka: docker compose logs --tail=80' >&2
  exit 1
fi

printf '\n2/7 — Stała poprawka domeny Codespaces i HTTPS (również po ponownym starcie) ...\n'
# To właśnie ta poprawka usunęła przekierowanie na localhost w działającym Codespace.
# Nie zależy od URL-a innego użytkownika — adres jest wykrywany na bieżąco.
docker compose exec -T -e MP1_SITE_URL="$site_url" -e MP1_SITE_HOST="$site_host" wordpress php <<'PHP'
<?php
$path = '/var/www/html/wp-config.php';
$url = getenv('MP1_SITE_URL');
$host = getenv('MP1_SITE_HOST');
if (!is_file($path) || !is_readable($path)) {
    fwrite(STDERR, "Brak wp-config.php\n");
    exit(1);
}
$data = file_get_contents($path);
if ($data === false) { fwrite(STDERR, "Błąd odczytu wp-config.php\n"); exit(1); }
$begin = '/* BEGIN MP1 CODESPACES PROXY FIX */';
$end = '/* END MP1 CODESPACES PROXY FIX */';
if (str_contains($data, $begin)) {
    $pattern = '~' . preg_quote($begin, '~') . '.*?' . preg_quote($end, '~') . '\s*~s';
    $without = preg_replace($pattern, '', $data, 1, $count);
    if ($count !== 1 || $without === null) {
        fwrite(STDERR, "Nie można zaktualizować poprzedniej poprawki\n"); exit(1);
    }
    $data = $without;
}
$snippet = <<<'CODE'
/* BEGIN MP1 CODESPACES PROXY FIX */
if (!defined('WP_HOME')) { define('WP_HOME', '__MP1_URL__'); }
if (!defined('WP_SITEURL')) { define('WP_SITEURL', '__MP1_URL__'); }
if (PHP_SAPI !== 'cli') {
    $_SERVER['HTTP_HOST'] = '__MP1_HOST__';
    $_SERVER['SERVER_NAME'] = '__MP1_HOST__';
    $_SERVER['SERVER_PORT'] = '443';
    $_SERVER['HTTPS'] = 'on';
    $_SERVER['REQUEST_SCHEME'] = 'https';
    $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
}
/* END MP1 CODESPACES PROXY FIX */
CODE;
$snippet = str_replace(['__MP1_URL__', '__MP1_HOST__'], [$url, $host], $snippet);
$updated = preg_replace('/<\?php\s*/', "<?php\n" . $snippet . "\n\n", $data, 1, $matches);
if ($matches !== 1 || $updated === null) {
    fwrite(STDERR, "Nie znaleziono początku wp-config.php\n"); exit(1);
}
$current = file_get_contents($path);
if ($updated !== $current) {
    $backup = '/tmp/mp1-wp-config-backup-' . gmdate('Ymd-His') . '.php';
    if (!copy($path, $backup)) { fwrite(STDERR, "Nie można utworzyć kopii wp-config.php\n"); exit(1); }
    if (file_put_contents($path, $updated, LOCK_EX) === false) {
        fwrite(STDERR, "Nie można zapisać wp-config.php\n"); exit(1);
    }
    echo "Poprawiono host/HTTPS (kopia: $backup).\n";
} else {
    echo "Ustawienia host/HTTPS były już poprawne.\n";
}
PHP
docker compose exec -T wordpress php -l /var/www/html/wp-config.php

printf '\n3/7 — Motyw, baza i instalacja WordPress ...\n'
# Motyw z repozytorium pozostaje edytowalny. CLI ma ten sam bind mount.
docker compose exec -T wordpress sh -c \
  'if [ ! -e /var/www/html/wp-content/themes/bistro-przy-stole ]; then ln -s /opt/mp1-theme /var/www/html/wp-content/themes/bistro-przy-stole; fi'
docker compose exec -T wordpress sh -c \
  'if [ ! -e /var/www/html/wp-content/themes/mf-child ]; then ln -s /opt/mp2-child /var/www/html/wp-content/themes/mf-child; fi'
if ! wp core is-installed >/dev/null 2>&1; then
  set -a
  # shellcheck source=/dev/null
  source .env
  set +a
  wp core install --url="$site_url" --title='Bistro Przy Stole' \
     --admin_user="$WP_ADMIN_USER" --admin_password="$WP_ADMIN_PASSWORD" \
     --admin_email="$WP_ADMIN_EMAIL" --skip-email
fi

printf '\n4/7 — Poprawa zapisanych URL-i (bez usuwania treści) ...\n'
# Czytamy wartości BEZPOŚREDNIO z bazy, ponieważ WP_HOME może maskować stare dane.
old_home="$(wp eval 'global $wpdb; echo $wpdb->get_var($wpdb->prepare("SELECT option_value FROM {$wpdb->options} WHERE option_name=%s", "home"));' | tr -d '\r\n')"
old_siteurl="$(wp eval 'global $wpdb; echo $wpdb->get_var($wpdb->prepare("SELECT option_value FROM {$wpdb->options} WHERE option_name=%s", "siteurl"));' | tr -d '\r\n')"
for old in "$old_home" "$old_siteurl" 'http://localhost:8080' 'https://localhost:8080' 'http://localhost' 'https://localhost'; do
  old="${old%/}"
  if [[ -z "$old" || "$old" == "$site_url" ]]; then continue; fi
  wp search-replace "${old}/" "${site_url}/" --all-tables-with-prefix \
    --skip-columns=guid --precise --report-changed-only >/dev/null
 done
wp option update home "$site_url" >/dev/null
wp option update siteurl "$site_url" >/dev/null
wp option update blogname 'Bistro Przy Stole' >/dev/null
wp option update blogdescription 'Dobre jedzenie, dobry czas przy stole' >/dev/null
wp rewrite structure '/%postname%/' --hard >/dev/null

printf '\n5/7 — Język polski, motyw i formularz ...\n'
if ! wp language core install pl_PL --activate; then
  echo 'UWAGA: Nie pobrano języka polskiego; spróbuj ponownie przy dostępie do internetu.' >&2
fi
# MP2: independent block theme L4 by default, optional classic child Twenty Twenty-One L5.
if [[ "$mode" == --child ]]; then
  if ! wp theme is-installed twentytwentyone >/dev/null 2>&1; then
    wp theme install twentytwentyone
  fi
  wp theme activate mf-child >/dev/null
elif [[ "$mode" == --block || "$mode" == start ]]; then
  wp theme activate bistro-przy-stole >/dev/null
fi
if wp plugin is-installed contact-form-7 >/dev/null 2>&1; then
  wp plugin activate contact-form-7 >/dev/null
elif ! wp plugin install contact-form-7 --activate; then
  echo 'UWAGA: Nie udało się pobrać Contact Form 7; formularz może nie działać.' >&2
fi
# MU-plugin normalizuje odnośniki nawigacji na właściwą domenę.
docker compose exec -T wordpress sh -c \
  'mkdir -p /var/www/html/wp-content/mu-plugins && cat > /var/www/html/wp-content/mu-plugins/mp1-codespaces-links.php && chmod 644 /var/www/html/wp-content/mu-plugins/mp1-codespaces-links.php' \
  < mu-plugins/mp1-codespaces-links.php

printf '\n6/7 — Treści Gutenberg i przyjazne adresy ...\n'
wp eval-file /work/seed.php
wp rewrite flush --hard >/dev/null
# Standardowe rewrite WP — w CLI sam rewrite flush nie zawsze tworzy .htaccess.
if ! docker compose exec -T wordpress sh -c \
  'test -f /var/www/html/.htaccess && grep -q "RewriteRule . /index.php" /var/www/html/.htaccess'; then
  docker compose exec -T wordpress sh -c \
    'if [ -f /var/www/html/.htaccess ]; then cp /var/www/html/.htaccess /var/www/html/.htaccess.mp1-backup; fi; cat > /var/www/html/.htaccess; chmod 644 /var/www/html/.htaccess' <<'HTACCESS'
# BEGIN WordPress
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
RewriteBase /
RewriteRule ^index\.php$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /index.php [L]
</IfModule>
# END WordPress
HTACCESS
fi
wp cache flush >/dev/null || true

printf '\n7/7 — Weryfikacja ...\n'
check_site
printf '\nGOTOWE: %s\nPanel: %s/wp-admin/\n' "$site_url" "$site_url"
printf 'Aktywny motyw: %s\n' "$(wp theme list --status=active --field=name)"
printf 'Login: mp1admin; hasło: grep "^WP_ADMIN_PASSWORD=" .env\n'
printf 'W Codespaces otwórz port 8080 przez PORTS → Open in Browser (Private).\n'
printf 'Po zajęciach użyj docker compose stop oraz zatrzymaj Codespace.\n'
printf 'Nie uruchamiaj docker compose down -v (utrata danych).\n'
