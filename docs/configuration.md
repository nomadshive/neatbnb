# Configuration

Everything that was hardcoded in the original production snippets is
now under **Settings → Neatbnb Ops**.

## System pages

Slug of each dashboard page (must match imported page slugs, or edit
the pages to match your slugs):

| Setting | Default | Used by |
|---|---|---|
| Account page slug | `account` | login modal, post-login redirect |
| Host dashboard page slug | `host-management` | role redirect, page restriction, go-back link |
| Cleaning dashboard page slug | `cleaning-management` | same |
| Maintenance dashboard page slug | `maintenance-management` | same |
| Additional verification page slug | `additional-verification` | `[user_meta]` "rejected" link |
| Quiz page slug | `general-orientation-quiz` | anti-cheat + no-translate assets |

## Telegram channel provisioning

Used by `[telegram_channel_button]`. The original code shipped with a
**live bot token and chat ID in source**; both are settings now and
empty by default.

1. Create a bot with [@BotFather](https://t.me/BotFather), paste the token.
2. Create a seed group/channel, add your bot to it, paste its chat ID
   (forward a message from the group to [@userinfobot](https://t.me/userinfobot)
   to read it).
3. List the bot usernames you want auto-joined to member channels
   (one per line).

> **Rotate before deploying:** if you forked from the original code
> history, treat any token you saw there as compromised and roll it
> with BotFather.

## Task & property lookups

`[formidable]` field IDs used by the AJAX auto-fill on the Orders form
(original production IDs shown as defaults):

| Setting | Default | Meaning |
|---|---|---|
| Task select | `291` | dropdown whose change triggers the lookup |
| Task type (source) | `433` | field on the task entry holding Cleaning/Maintenance |
| Property on order form | `277` | property field on form 25 |
| Task type (hidden output) | `439` | receives the fetched type |
| Property (hidden output) | `534` | receives the fetched property |
| Conditional fields | `292`, `297` (cleaning), `455`→`454` (maintenance) | shown/hidden by task type |

If your Formidable install assigns different field IDs (very likely on
a fresh import — IDs are preserved by the SQL import, but re-created
forms get new IDs), update these to match
(`wp_frm_fields` table → `id` column).

### Property map

The original code mapped internal property entry IDs to display
numbers in PHP:

```
406 = 1
407 = 2
...
```

Edit the list to match your properties; one `entry value = display
value` pair per line. Unmapped values pass through unchanged.

## Role behaviour without settings

- **Page restrictions** (which roles are denied which dashboard) are
  filters in `class-nb-access.php`:
  `nb_restricted_pages`, `nb_role_home_map`, `nb_unrestricted_roles`.
  Add a small mu-plugin to adjust them:

```php
add_filter( 'nb_restricted_pages', function ( $pages ) {
    $pages['some-page'] = array( 'host' );   // deny hosts
    return $pages;
} );
```

- **Shortcodes** are documented in [architecture.md](architecture.md).

## Formidable "Require Login"

Forms can be marked *Require Login* in their options (added by the
plugin). Guests then see an inline AJAX login in front of the fields;
the handler is `custom_login` in `class-nb-login-gate.php`.
