# Security & privacy

## What this repository deliberately excludes

| Excluded | Why |
|---|---|
| 574 Formidable entries, 26 users, 81 bookings | business data |
| Bellevue parent theme, Formidable Pro, MotoPress, sliders | third-party licenses |
| Telegram bot token, seed chat ID | secrets (see below) |
| Formidable REST API key found in form actions | secret |
| Google Sheet document ID, Telegram invite link | private resources |
| Staff/worker personal emails found in email actions | personal data |
| Production domain URLs | replaced with `example-site.com` / `example.com` placeholders |

All SQL in `setup/sql` passed a secret scan (bot token, chat ID, API
key, personal emails, sheet ID, invite link, production hosts: 0 hits).

**Length-preserving replacements:** the scrubber rewrote secrets to
equal-byte-length placeholders so PHP-serialized strings in the dumps
remain valid — do not "clean up" those `xx` paddings casually; if you
re-scrub, preserve byte lengths or use a serialization-aware tool.

## Secrets that existed in the original code

If you have access to the original production code or git history:

1. **Telegram bot token** — rotate it with @BotFather immediately;
   it was committed in plaintext and must be considered compromised.
2. **Formidable API key** (`user:pass` pair) — regenerate under
   Formidable → Settings → API.
3. **Seed chat ID** — rotate by creating a new seed group if the old
   one was exposed.

## Hardening notes for deployers

- The plugin adds nonces to password change, channel creation, and
  CSV-upload forms; the AJAX login endpoint validates credentials via
  `wp_signon` (no custom password handling).
- Quiz "anti-cheat" JS is a **deterrent only** — content is still in
  the DOM. Do not put answer keys or secrets in quiz page content.
- Page restrictions are enforced server-side
  (`template_redirect` → 403), not by hiding links.
- Geo/IP restrictions (Cloudflare WAF), SMTP, and login alerting are
  deployment concerns documented by your hosting setup, not this repo.

## Reporting

Please open a GitHub issue (mark security topics as **private
security advisory** if your fork supports it) rather than a public
issue when reporting vulnerabilities.
