# Architecture

Neatbnb Operations is three role-gated systems on one WordPress install.
Everything below reflects the structure shipped in `setup/sql` and
enforced by the `neatbnb-ops` plugin.

```
                        ┌──────────────────────────────┐
                        │        WordPress +           │
                        │  Formidable Forms (Pro)      │
                        │  Elementor · ACF · WP Code   │
                        └──────────────┬───────────────┘
                                       │
        ┌──────────────────────────────┼──────────────────────────────┐
        │                              │                              │
┌───────▼────────┐            ┌────────▼───────┐            ┌─────────▼──────┐
│ 1. PROPERTY    │            │ 2. PLATFORM    │            │ 3. CLEANING    │
│    MANAGERS    │            │    MANAGEMENT  │            │    COMPANY     │
│  (hosts)       │            │ (admins, mgrs) │            │ (ops workers)  │
└───────┬────────┘            └────────┬───────┘            └─────────┬──────┘
        │                              │                              │
  Host Management              all dashboards +              Cleaning Management
  New Order                    financial back-office         Maintenance Management
  Financial Settlement         (settlements, finance         Status Update / Start Task
  Purchases & Invoices           CSV upload, QC reports)     Attendance, QC Check,
  Finance link (Google Sheets)                             Purchases, Complaints
```

## Roles

| Role | System | Typical users | Lands on (after login) |
|---|---|---|---|
| `host` | Property managers | property owners/managers | `/host-management/` |
| `cleaning_operations` | Cleaning company | cleaning supervisors/workers | `/cleaning-management/` |
| `maintanace_operations` | Cleaning company (maintenance) | maintenance workers | `/maintenance-management/` |
| `administrator` | Platform | founders | front page (unrestricted) |
| `manager` | Platform | ops managers | front page (unrestricted) |
| `operations_manager`, `hr`, `mphb_customer` | Platform / booking | staff & customers | (login redirect → `/account/`) |

Access control is implemented in `class-nb-access.php`:
guests hitting a restricted page go to `wp-login.php`; logged-in users
without the right role get a 403; admins/managers bypass all
restrictions. After login everyone lands on `/account/`
(`class-nb-auth.php`), and the front page redirects each role to its
own dashboard.

## Custom post type

`financial_settlement` — one page per host, auto-created on user
registration (`class-nb-settlement.php`), stores `host_id` meta. The
production site used this CPT without ever registering it; the plugin
registers it properly.

## Data model (Formidable)

Forms and views are exported in `setup/sql/wp_frm_definitions.sql`.
The core operational forms:

| Form ID | Name | Used by |
|---|---|---|
| 23 | QC Check (93 fields) | cleaning company → platform QC reports |
| 25 | Orders (16 fields) | hosts creating jobs; task/property AJAX reads fields 291/433/277 |
| 26 | Status Update | cleaning company status flow |
| 34 | Maintenance | maintenance tasks |
| 37 | Purchases | purchases & invoices |
| 38 | Status Update Workers | worker-facing status |
| 47 | Host Registration | onboarding new hosts |
| 49 / 50 | Attendance In / Out | worker clock in/out |

Key meta fields used by the AJAX lookups (configurable in settings):
task select `291`, task type source `433`, order property `277`,
hidden outputs `439`/`534`, conditional fields `292`/`297`/`454`.

Views (`frm_display`): Orders calendar/table, QC table, Status tables,
Purchases & Invoices tables (incl. finance-specific variants), KYE
workers tables, compensation claims.

## Custom code map

All production custom code now lives in `plugin/neatbnb-ops`:

| Original location | Plugin module |
|---|---|
| WPCode "Link by role" | `class-nb-access.php` |
| WPCode "Logout", "Change Password", "Popup Login on /account" (was corrupted) | `class-nb-auth.php` |
| WPCode "Financial Data on Google Sheets", "Displaying verification status", "Telegram Channel Status", "Upload CSV for Finance" | `class-nb-shortcodes.php` |
| WPCode "Telegram Channel Creation" (contained a live bot token) | `class-nb-telegram.php` (token now a setting) |
| WPCode "For each new host a financial settlement page is created" | `class-nb-settlement.php` |
| WPCode "Disable The WP Admin Bar", "Anti-Cheat", "Disable GTranslate" | `class-nb-frontend.php` + `assets/` |
| Child theme AJAX (task type / property map) | `class-nb-tasks.php` |
| Child theme "Require Login" form gate | `class-nb-login-gate.php` |
| Elementor HTML widgets (inline login forms ×7 pages) | `[nb_login_widget]` shortcode |
| (missing) `custom_login` AJAX handler | `class-nb-login-gate.php::ajax_login()` |

## Shortcodes

`[go_back_link]`, `[user_finance_link]`, `[user_meta key="..."]`,
`[telegram_channel]`, `[telegram_channel_button]`,
`[password_change_form]`, `[admin_host_financial_upload]`,
`[nb_login_widget redirect="/page/"]`.
