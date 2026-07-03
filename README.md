# Publibalão

WordPress site repo for [publibalao.com](https://publibalao.com). Only custom-developed code is tracked here — the full WordPress install lives on the server.

## Structure

- `theme/` — Divi child theme, synced to `/public_html/wp/wp-content/themes/publibalao/`
- `plugin/` — custom Publibalão plugin, synced to `/public_html/wp/wp-content/plugins/publibalao/`
- `languages/` — translation files for third-party plugins, synced to `/public_html/wp/wp-content/languages/plugins/`
- `other-plugins/` — third-party plugins (modern-events-calendar, revslider), kept locally for reference only; not tracked or synced
- `mail-templates/` — HTML templates used as Contact Form 7 message bodies
- `.backup/` — local backups, not tracked

## Deployment

Deploys via SFTP, configured per-folder in `.vscode/sftp.json` (see `.vscode/sftp.json.example` for the template — fill in host/username/password locally, never commit real credentials).
