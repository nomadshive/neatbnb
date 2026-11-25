# Installation

## What you need (not bundled)

This repository contains **only original, redistributable code**.
The following are required and must be obtained separately:

| Component | License | Why it's needed |
|---|---|---|
| **Bellevue parent theme** | ThemeForest Regular License (purchase) | parent of the shipped child theme |
| **Formidable Forms + Pro + addons** (Views, User Tracking, Registration, ACF, Quizzes, Google Spreadsheet, PDFs, Chat, Logs, Stripe, PayPal) | commercial | all forms/views |
| **Elementor Pro** (or free + Essential Addons / ElementsKit) | commercial/free | page builder used by system pages |
| **MotoPress Hotel Booking** (+ addons) | commercial | booking side of the site |
| **RevSlider, MasterSlider** | commercial | sliders |
| Free: ACF, WP Mail SMTP, Members/User Role Editor, Peter's Login Redirect, Menu by User Roles, GTranslate, Rank Math, WooCommerce services | GPL | see `setup/sql/plugin-versions.txt` for the full production list |

> `active_plugins` is intentionally **not** imported — activate plugins
> yourself so you control versions and licenses.

## Steps

### 1. Fresh WordPress

```bash
# via docker (repo dev stack) or any host
cd docker && docker compose up -d
```

### 2. Install & activate required plugins

At minimum for the skeleton to function:
Formidable Forms (+ Pro and the addons you need), Elementor,
Advanced Custom Fields, User Role Editor (for the custom roles),
and your purchased Bellevue parent theme.

### 3. Remove default content

```bash
wp post delete 1 2 3 --force   # hello world, sample page, privacy policy
```

### 4. Import the skeleton

```bash
cd setup
DB_HOST=... DB_NAME=... DB_USER=... DB_PASS=... bash import.sh
```

This imports (structure only, **no business data**):

- `wp_options.sql` — custom roles (upserts), permalinks, menu locations
- `wp_terms*.sql` — navigation menus
- `wp_frm_definitions.sql` — 50 forms, fields, views, form actions
- `wp_posts.sql` — pages, views, menus, ACF groups, Elementor templates
- `wp_postmeta.sql` — Elementor layouts, ACF field definitions, page meta

### 5. Point the site at your domain

```bash
wp search-replace 'example-site.com' 'your-domain.tld'
wp search-replace 'example.com' 'your-domain.tld'
```

(The skeleton was scrubbed of the original domains; these two
placeholder hosts appear throughout the imported content.)

### 6. Install the plugin and child theme

```bash
wp plugin activate neatbnb-ops
wp theme activate bellevuex-child    # requires parent theme present
```

Or copy `plugin/neatbnb-ops` into `wp-content/plugins/` and
`theme/bellevuex-child` into `wp-content/themes/` (with your licensed
`bellevuex/` parent next to it).

### 7. Configure

**Settings → Neatbnb Ops** — Telegram credentials, page slugs,
property map, Formidable field IDs. See
[configuration.md](configuration.md).

### 8. Create your users

Create users and assign roles (`host`, `cleaning_operations`,
`maintanace_operations`, …). Registering a user with the `host` role
auto-creates their `financial_settlement` page.

## Post-install checklist

- [ ] Permalink structure flushed (Settings → Permalinks → Save)
- [ ] Front page set (Settings → Reading)
- [ ] Roles visible in Users → Add New (User Role Editor imported them)
- [ ] Shortcodes render on dashboard pages
- [ ] `/account/` shows the login modal for guests
- [ ] `/logout` logs out and returns home
- [ ] Login redirect lands on `/account/`
