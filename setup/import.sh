#!/usr/bin/env bash
# Import the site skeleton (structure only — no business data) into a
# fresh WordPress database.
#
# Usage:
#   DB_HOST=localhost DB_NAME=wordpress DB_USER=wp DB_PASS=secret ./import.sh
#
# Prerequisites:
#   1. Fresh WordPress installed, URL configured.
#   2. Required plugins installed AND ACTIVATED (Formidable Forms etc.)
#      so their tables exist before the Formidable definitions import.
#   3. Default content removed:
#        wp post delete 1 2 3 --force   # hello world, sample page, privacy policy
#
# The script is idempotent-ish: re-running may produce duplicate-key
# warnings for posts already imported; that is safe to ignore.
set -euo pipefail

SQL_DIR="$(cd "$(dirname "$0")" && pwd)/sql"
: "${DB_HOST:?set DB_HOST}"
: "${DB_NAME:?set DB_NAME}"
: "${DB_USER:?set DB_USER}"
: "${DB_PASS:?set DB_PASS}"

MYSQL_ARGS=(-h "$DB_HOST" -u "$DB_USER" "-p$DB_PASS" "$DB_NAME")

run() {
  local file="$1"
  echo "==> importing $(basename "$file")"
  # --force: continue past benign duplicate-key errors on re-runs
  mysql --force "${MYSQL_ARGS[@]}" < "$file"
}

# Order matters: roles/options first (so roles exist), then forms,
# then posts, then relationships that reference both.
run "$SQL_DIR/wp_options.sql"                 # roles, permalinks, theme mods (upserts)
run "$SQL_DIR/wp_terms.sql"                   # nav menu terms
run "$SQL_DIR/wp_term_taxonomy.sql"
run "$SQL_DIR/wp_frm_definitions.sql"         # forms, fields, views (requires Formidable tables)
run "$SQL_DIR/wp_posts.sql"                   # pages, views, menus, ACF, Elementor templates
run "$SQL_DIR/wp_postmeta.sql"                # Elementor data, ACF field defs, page meta
run "$SQL_DIR/wp_term_relationships.sql"      # menu item -> menu assignments

echo
echo "Import finished. Now:"
echo "  1. wp search-replace 'example-site.com' 'YOUR-DOMAIN.TLD'"
echo "  2. wp search-replace 'example.com' 'YOUR-DOMAIN.TLD'"
echo "  3. Install & activate: neatbnb-ops plugin, bellevuex-child theme"
echo "     (parent theme 'bellevuex' must be purchased separately — see docs/installation.md)"
echo "  4. Configure: Settings -> Neatbnb Ops (see docs/configuration.md)"
echo "  5. Activate remaining plugins manually (active_plugins is intentionally NOT imported)"
