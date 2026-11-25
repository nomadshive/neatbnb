# Neatbnb Operations

An open-source **short-term rental operations platform** built on
WordPress + Formidable Forms, distilled from a production site that
runs three organizations on one install:

1. **Property managers (hosts)** — create orders, track financial
   settlements, upload purchases, view their finance sheet.
2. **Platform management** — administrators/managers overseeing all
   dashboards, QC reports, finance back-office, host onboarding.
3. **Cleaning company (workers & supervisors)** — cleaning and
   maintenance task flows, status updates, attendance, QC checks,
   purchases, complaints.

Role-gated dashboards, form-driven workflows, per-host financial
settlement pages, and Telegram channel provisioning — all as original
GPL-2.0 code you can fork and adapt.

## Repository layout

```
neatbnb/
├── plugin/neatbnb-ops/       ← the whole custom backend (PHP + assets)
│   ├── neatbnb-ops.php       ← bootstrap, CPT registration
│   ├── includes/             ← access, auth, shortcodes, telegram,
│   │                           tasks AJAX, login gate, frontend, settlement
│   └── assets/               ← anti-cheat, no-translate, login widget
├── theme/bellevuex-child/    ← child theme (parent NOT bundled, see below)
├── setup/
│   ├── sql/                  ← structure-only DB skeleton: roles, pages,
│   │                           50 forms + views, menus, Elementor layouts
│   └── import.sh             ← one-shot importer
├── demo/
│   ├── demo.sh               ← one-command demo stack (docs/demo.md)
│   └── seed-entries.php      ← synthetic demo entries (no real data)
├── docker/docker-compose.yml ← dev/demo stack (wp + db + wpcli)
├── docs/
│   ├── architecture.md       ← the 3 systems, roles, data model, code map
│   ├── installation.md       ← step-by-step setup
│   ├── configuration.md      ← settings reference
│   ├── demo.md               ← demo walkthrough + fork/rebrand guide
│   └── security-and-privacy.md ← what's excluded and why
└── LICENSE                   ← GPL-2.0-or-later
```

## Quick start — demo in one command

```bash
git clone https://github.com/YOUR-ACCOUNT/neatbnb.git
cd neatbnb
./demo/demo.sh                # → http://localhost:8080
```

Log in as `demo-host` / `demo1234` (or `demo-cleaner`, `admin` —
same password) and click through the three systems. Full walkthrough,
options and the fork/rebrand checklist: [docs/demo.md](docs/demo.md).

## Quick start — manual install

```bash
git clone https://github.com/YOUR-ACCOUNT/neatbnb.git
cd neatbnb/docker && docker compose up -d      # WordPress on :8080
# …follow docs/installation.md (plugins → import → search-replace → activate)
```

## What is NOT included (on purpose)

- **Commercial plugins/themes**: Bellevue parent theme (ThemeForest),
  Formidable Pro + addons, MotoPress Hotel Booking, RevSlider,
  MasterSlider, Elementor Pro. Buy/install them separately —
  `setup/sql/plugin-versions.txt` lists the exact production stack.
- **Business data**: form entries, users, bookings — none exported.
- **Secrets**: API keys, tokens, private links, personal emails were
  scrubbed from every file (see `docs/security-and-privacy.md`).

## Requirements

WordPress 6.0+, PHP 7.4+, Formidable Forms (Pro for views), Elementor,
ACF, plus the free plugins listed in `setup/sql/plugin-versions.txt`.

## License

GPL-2.0-or-later — see [LICENSE](LICENSE).

Custom code in this repository is original work released under GPL;
third-party components remain under their own licenses and are not
redistributed here.
