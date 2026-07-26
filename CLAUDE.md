# Kramo — project instructions

WooCommerce starter kit for the Polish market. GeneratePress plus the
`kramo-child` theme, three visual presets, self-hosted fonts, Polish-first copy.

## Design System

Always read DESIGN.md before making any visual or UI decisions.
All font choices, colors, spacing, motion and aesthetic direction are defined there.
Do not deviate without explicit user approval.
In QA mode, flag any code that doesn't match DESIGN.md.

## Build rules

- One concern per file in `theme/kramo-child/inc/`, never a pile in `functions.php`.
- All strings through `__()` with the `kramo` text domain.
- Polish in the client-facing interface, English in code.
- Never patch WooCommerce core — hooks and filters only.
- No hard-coded colors outside `assets/css/tokens.css`; `node scripts/check-contrast.js`
  must pass for every preset.
- Fonts stay self-hosted and OFL-licensed. No Google Fonts: external font requests
  before consent are a RODO problem, and non-OFL licences block subsetting and
  client delivery.
- Secrets live in `docker/.env` (git-ignored) or `wp-config.php`, never in the database.
- Update `CHANGELOG.md` with every task.
