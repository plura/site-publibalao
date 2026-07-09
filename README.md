# Publibalão

WordPress site repo for [publibalao.com](https://publibalao.com). Only custom-developed code is tracked here — the full WordPress install lives on the server.

## Structure

- `theme/` — Divi child theme, synced to `/public_html/wp/wp-content/themes/publibalao/`
- `plugin/` — custom Publibalão plugin, synced to `/public_html/wp/wp-content/plugins/publibalao/`
- `languages/` — translation files for third-party plugins, synced to `/public_html/wp/wp-content/languages/plugins/`
- `other-plugins/` — third-party plugins (modern-events-calendar, revslider), kept locally for reference only; not tracked or synced
- `mail-templates/` — MJML sources for Contact Form 7 message bodies, one folder per form (`register/` has both the staff-notification and auto-reply templates). Each template exists in a `-pt`/`-en` pair (MJML has no built-in i18n) with its `.mjml` source(s) and built `.html`. `_partials/` holds shared includes — some language-neutral (`head.mjml`, `header.mjml`, `spacer.mjml`), others split into `-pt`/`-en` pairs too (footer, footer-fibaq, header-fibaq, credits). `legacy/` holds superseded HTML-only templates kept for reference. Run `npm run build` (or `npm run build:<name>-pt`/`-en`) inside `mail-templates/` to rebuild the `.html` files after editing a `.mjml` source — paste the built HTML into CF7's mail body field.
- `.backup/` — local backups, not tracked

## Deployment

Deploys via SFTP, configured per-folder in `.vscode/sftp.json` (see `.vscode/sftp.json.example` for the template — fill in host/username/password locally, never commit real credentials).
