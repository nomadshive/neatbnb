# Demo spinoff

One command gives you a running copy of the platform — WordPress, the
site skeleton (roles, pages, 50 forms, menus, Elementor layouts), the
custom plugin, and synthetic demo entries so every dashboard has
content. No production data, no secrets, no commercial licenses needed.

## Requirements

- Docker + Docker Compose v2
- ~2 GB free RAM, ports `8080` (or your `DEMO_PORT`) and `3306` local
- `curl` and `bash`

## Quick start

```bash
git clone https://github.com/YOUR-ACCOUNT/neatbnb.git
cd neatbnb
./demo/demo.sh
# → http://localhost:8080
```

Custom port: `DEMO_PORT=9000 ./demo/demo.sh`.

## Accounts

| Login | Password | Role | Sees |
|---|---|---|---|
| `admin` | `demo1234` | administrator | wp-admin, everything |
| `demo-host` | `demo1234` | `host` | own orders, purchases, settlement page |
| `demo-cleaner` | `demo1234` | `cleaning_operations` | task lists, status updates, QC |

These credentials are public and demo-only. Never reuse them.

## What the script does

1. `docker compose up -d` — WordPress (PHP 8.3), MySQL 8, WP-CLI helper.
2. Waits for the site, then `wp core install` (admin/demo1234).
3. Installs the free plugins: Formidable Forms, Elementor, ACF,
   User Role Editor.
4. Deletes the three default posts, then imports the 7 structure-only
   SQL files from `setup/sql/` (options/roles → terms → Formidable
   definitions → pages → postmeta → menu relationships).
5. `wp search-replace` swaps `example-site.com` / `example.com` for
   `http://localhost:$DEMO_PORT`.
6. Activates a bundled default theme (the Bellevue parent is not
   bundled) and the `neatbnb-ops` plugin.
7. Creates `demo-host` (role `host` — which also auto-creates their
   `financial_settlement` page) and `demo-cleaner`
   (role `cleaning_operations`). Outbound mail is disabled in the demo
   via an mu-plugin, so no notification emails leave the container.
8. Runs `demo/seed-entries.php`: **4 synthetic listings, 6 orders,
   6 status updates (APPROVED/CANCELLED/UNAVAILABLE), 2 purchases** —
   all invented values, all registered with the property map.
9. Enables pretty permalinks and writes `.htaccess`.

Every step is guarded, so re-running `./demo/demo.sh` is safe. For a
clean slate: `cd docker && docker compose down -v`.

## Walkthrough

**Host** (`demo-host`) — `/account/` shows the inline login form; after
login you land on the host dashboard (`/host-management/`). Open
`/new-order/` to see the order form (property dropdown is populated
from the seeded listings), check `/purchases/` for the two seeded
expense entries, and find the auto-created `financial_settlement` page
in the Pages list.

**Cleaner** (`demo-cleaner`) — `/cleaning-management/` lists the
seeded tasks; `/status-update/` is the form used to approve or cancel
them (Task ID dropdown → seeded order IDs). Conditional "Assigned to"
fields appear once a task type is chosen.

**Admin** (`admin`) — wp-admin: 50 forms under Forms → Formidable,
pages/Elementor layouts under Pages, roles under Users, plugin
settings under **Settings → Neatbnb Ops**.

## Known limitations of the demo

- **Formidable Forms free**: the demo installs the free plugin from
  wp.org. Two things degrade compared to production:
  - the *data* fields behind the property dropdown are a **Pro**
    feature and fall back to plain fields;
  - the front-end dashboard lists are **Formidable Views (Pro)** and
    won't render with free installed. You can still browse every
    seeded entry in **wp-admin → Formidable → Entries**.
  The `wp_frm_view_layouts` import is skipped automatically when the
  table is absent; install your Pro license (see
  `setup/sql/plugin-versions.txt` for the production version) and
  re-run `setup/import.sh` to load the view layouts for full fidelity.
- **Bellevue theme not bundled**: the demo runs on the default
  WordPress theme, so the production header/footer styling is absent
  and navigation falls back to a page list. Drop your licensed
  `bellevuex/` parent into `theme/` and re-run `./demo/demo.sh` to
  activate the real UI.
- **Elementor images** point at placeholder URLs and won't load —
  layout and text still render.
- Telegram notifications are inert (no bot token configured, by design).

## Options

| Variable | Default | Purpose |
|---|---|---|
| `DEMO_PORT` | `8080` | published HTTP port |
| `COMPOSE_PROJECT_NAME` | `neatbnb-demo` | isolates containers/volumes from other stacks |

## Fork & rebrand checklist

1. Fork the repo (or use it as a GitHub template), rename, adjust the
   README badges/links.
2. Run `./demo/demo.sh`, click through all three roles.
3. Set your own identity: **Settings → General** (title, tagline) and
   **Settings → Neatbnb Ops** (page slugs, property map, Telegram —
   leave tokens empty in any public fork).
4. Replace the seeded listings/orders with your own test data, or wipe
   them: entries live in `wp_frm_items` (forms 22/25/26/37) and can be
   deleted from the Formidable UI.
5. When you go live: `docs/installation.md` (manual path),
   `docs/configuration.md` (field IDs may differ on a fresh Formidable
   install — map them in Settings → Neatbnb Ops), and
   `setup/sql/plugin-versions.txt` for the commercial stack.
6. Keep `docs/security-and-privacy.md` in mind: never commit real
   entries, users, uploads, or tokens.
