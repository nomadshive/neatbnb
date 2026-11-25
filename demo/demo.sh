#!/usr/bin/env bash
# One-command demo: fresh WordPress + neatbnb site skeleton + synthetic
# demo data on Docker Compose. See docs/demo.md.
#
#   ./demo/demo.sh                   # http://localhost:8080
#   DEMO_PORT=9000 ./demo/demo.sh    # custom port
#
# Re-running is safe (every step is guarded). Full reset:
#   cd docker && docker compose down -v
set -euo pipefail

cd "$(dirname "$0")/../docker"

export COMPOSE_PROJECT_NAME="${COMPOSE_PROJECT_NAME:-neatbnb-demo}"
export DEMO_PORT="${DEMO_PORT:-8080}"
URL="http://localhost:${DEMO_PORT}"

step() { printf '\n=== %s\n' "$*"; }
# < /dev/null keeps compose from forwarding this script's stdin into the
# container (which would swallow the rest of the script when piped).
wp()   { docker compose exec -T wpcli wp "$@" < /dev/null; }
sql()  { docker compose exec -T db sh -c 'mysql --force -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"'; }

step "Starting stack (project: ${COMPOSE_PROJECT_NAME}, port: ${DEMO_PORT})"
docker compose up -d

step "Waiting for WordPress at ${URL}"
ready=""
for _ in $(seq 1 60); do
  if curl -fsS -o /dev/null "${URL}/wp-admin/install.php" 2>/dev/null; then
    ready=1
    break
  fi
  sleep 2
done
[ -n "$ready" ] || { echo "ERROR: WordPress did not respond at ${URL}"; exit 1; }

step "Installing WordPress"
if wp core is-installed >/dev/null 2>&1; then
  echo "already installed — skipping"
else
  wp core install --url="${URL}" --title="Neatbnb Demo" \
    --admin_user=admin --admin_password=demo1234 \
    --admin_email=admin@example.com --skip-email
fi

step "Installing free plugins"
for p in formidable elementor advanced-custom-fields user-role-editor; do
  wp plugin is-installed "$p" 2>/dev/null || wp plugin install "$p"
  wp plugin is-active "$p" 2>/dev/null || wp plugin activate "$p"
done

# Formidable only creates its tables when its DB upgrade runs (normally
# on the first wp-admin visit) — run it explicitly so the definitions
# import below has tables to write to, then drop the freshly-created
# default form so the Forms list matches the skeleton exactly.
step "Preparing Formidable Forms tables"
wp eval '
global $wpdb;
$n = count( $wpdb->get_col( "SHOW TABLES LIKE \"{$wpdb->prefix}frm_%\"" ) );
if ( $n < 5 ) {
	delete_option( "frm_db_version" ); // force the fresh-install path
	( new FrmMigrate() )->upgrade();
	$n = count( $wpdb->get_col( "SHOW TABLES LIKE \"{$wpdb->prefix}frm_%\"" ) );
}
if ( $n < 5 ) {
	fwrite( STDERR, "Formidable tables missing ($n)\n" );
	exit( 1 );
}
foreach ( array( "frm_forms", "frm_fields" ) as $t ) {
	$wpdb->query( "DELETE FROM {$wpdb->prefix}{$t}" );
}
echo "$n Formidable tables ready\n";
'

step "Removing default content"
wp post delete 1 2 3 --force >/dev/null 2>&1 || true

step "Importing site skeleton (structure only, no business data)"
# wp_frm_view_layouts only exists with Formidable Pro (views are a Pro
# feature) — skip those rows on a free-Formidable demo install.
HAS_VIEWS=$(wp eval 'global $wpdb; echo $wpdb->get_var( "SHOW TABLES LIKE \"{$wpdb->prefix}frm_view_layouts\"" ) ? "1" : "0";' 2>/dev/null | tail -n 1)
for f in wp_options wp_terms wp_term_taxonomy wp_frm_definitions \
         wp_posts wp_postmeta wp_term_relationships; do
  echo "  -> ${f}.sql"
  if [ "$f" = "wp_frm_definitions" ] && [ "$HAS_VIEWS" != "1" ]; then
    grep -v 'wp_frm_view_layouts' "../setup/sql/${f}.sql" | sql
  else
    sql < "../setup/sql/${f}.sql"
  fi
done

step "Pointing URLs at ${URL}"
wp search-replace 'https://example-site.com' "${URL}" --quiet
wp search-replace 'http://example-site.com'  "${URL}" --quiet
wp search-replace 'https://example.com'       "${URL}" --quiet
wp search-replace 'http://example.com'        "${URL}" --quiet
# JSON-escaped variants inside Elementor data, then schemeless leftovers.
wp search-replace 'https:\/\/example-site.com' "http:\/\/${URL#*://}" --quiet
wp search-replace 'http:\/\/example-site.com'  "http:\/\/${URL#*://}" --quiet
wp search-replace 'example-site.com' "${URL#*://}" --quiet

step "Disabling outbound mail (demo only)"
docker compose exec -T wpcli sh -c 'mkdir -p /var/www/html/wp-content/mu-plugins && printf "%s\n" "<?php" "function wp_mail() { return true; }" > /var/www/html/wp-content/mu-plugins/nb-demo-no-mail.php' < /dev/null

step "Activating theme and custom plugin"
# The imported skeleton references the Bellevue theme (parent not
# bundled) — fall back to the bundled default theme for the demo.
wp theme activate twentytwentyfive >/dev/null 2>&1 \
  || wp theme install twentytwentyfive --activate
wp plugin activate neatbnb-ops

step "Creating demo users"
wp user get demo-host >/dev/null 2>&1 || \
  wp user create demo-host demo-host@example.com \
    --role=host --user_pass=demo1234
wp user get demo-cleaner >/dev/null 2>&1 || \
  wp user create demo-cleaner demo-cleaner@example.com \
    --role=cleaning_operations --user_pass=demo1234

step "Seeding synthetic demo entries (listings, orders, statuses, purchases)"
wp eval-file /demo/seed-entries.php

step "Enabling pretty permalinks"
wp rewrite structure '/%postname%/' >/dev/null 2>&1 || true
docker compose exec -T wordpress sh -c 'cat > /var/www/html/.htaccess' <<'HTACCESS' || true
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

cat <<EOF

============================================================
 Demo ready: ${URL}

   Admin:     admin        / demo1234
   Host:      demo-host    / demo1234
   Cleaner:   demo-cleaner / demo1234

 Walkthrough:  docs/demo.md
 Reset:        cd docker && docker compose down -v
============================================================
EOF
