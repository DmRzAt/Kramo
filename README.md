# Kramo

A WooCommerce starter kit for the Polish e-commerce market. It exists to turn a
typical store project from 85–119 hours into 30–40 hours of adaptation.

GeneratePress plus the `kramo-child` theme, three visual presets, Polish
localisation, payments and shipping built for the PL market, and a consent
banner that genuinely withholds analytics until the visitor agrees.

## Quick start

Requires Docker and Docker Compose.

```sh
cp docker/.env.example docker/.env
make fresh
make demo
```

A few minutes later `localhost:8080` serves a Polish store with 12 variable
products. phpMyAdmin runs on `localhost:8081`. No manual step in the browser is
needed.

Other commands:

```sh
make up                      # start
make down                    # stop
make wp CMD="plugin list"    # any wp-cli command
make logs
```

## Public demo

`Dockerfile.production` and `railway.json` deploy the real store — catalog,
filters, variants, personalization, cart, shipping calculation and checkout all
work, backed by a managed MySQL service and a volume for uploads. The first
boot provisions and seeds everything without a single click in the browser, and
Railway only routes traffic once `/kramo-health` reports success.

`KRAMO_DEMO=1` adds a demo ribbon and disables outgoing e-mail, and cash on
delivery keeps checkout completable without live gateway keys. Steps and
variables: [`docs/deployment.md`](docs/deployment.md).

Live: **https://p01--kramo-wp--bpk4d66g4n48.code.run** — running on a free
Northflank sandbox (0.2 shared vCPU, 512 MB, no page cache). Lighthouse mobile on
the product page scores 72-90 across runs with LCP 1.8-4.0 s; TBT 0 ms and CLS 0
every time, so the variance is server response, not front-end code. Numbers and
method: [`docs/performance.md`](docs/performance.md).

## Layout

```
kramo/
├── docker/                  docker-compose.yml and .env.example
├── mu-plugins/              payment bootstrap, loaded before regular plugins
├── scripts/                 provisioning, demo data, checks, font subset
├── theme/kramo-child/       child theme, text domain kramo
│   ├── inc/                 one concern per file
│   └── assets/css|js|fonts
└── docs/                    Polish client documents and measurements
```

## What is inside

**Design.** Three token layers in `assets/css/tokens.css`; hard-coded colours
outside that file are not allowed. Three presets — `craft`, `service`,
`premium` — switch under **Wygląd → Kramo** without touching code. A preset may
only override variables, and `scripts/check-contrast.js` fails the build when a
pair drops below WCAG AA.

**Catalog and product.** A 2/3/4 column grid, variant swatches, hover to the
second photo, a plugin-free wishlist (localStorage for guests, user meta once
logged in), filters on `pre_get_posts` with `pushState` and shareable URLs,
video in the gallery, and reviews restricted to verified buyers.

**Personalization.** An engraving or embroidery field travels the whole chain:
product → cart → order → customer e-mail → admin e-mail → order screen. Two
identical products with different text stay separate line items. Covered by
seven test cases in `scripts/check-personalization.php`.

**Payments.** Przelewy24 and PayPal. Keys are read only from `.env` or
`wp-config.php` and **never reach the database** — writes to `wp_options` are
blocked by filters. `KRAMO_PAYMENT_MODE` switches between sandbox and live.

**Shipping.** A "Polska" zone with InPost Paczkomat, InPost Kurier, Orlen
Paczka and free delivery above a threshold. Weight tiers live in code and a
parcel locker always costs less than a courier. The chosen pickup point appears
in the admin order, in e-mails and on the order-received page.

**SEO.** Rank Math is configured without its wizard (sitemap without tags and
authors, breadcrumbs on). The theme adds only what Rank Math does not:
`LocalBusiness` with `areaServed`, `FAQPage` and an `hreflang` stub. The
**"Usługa lokalna (SEO)"** template targets "service + city" jobs — copying a
page to a new city is a two-field change.

**Performance.** Critical CSS inlined, payment scripts limited to checkout
(home page went from 56 to 11 JS files), WebP images in the 4:5 ratio the grid
expects, exactly one LCP hint per view, and the variable font subset to Latin
plus Polish (352 KB → 56.6 KB).

Lighthouse on demo content **without a page cache**:

| Page | Desktop | Mobile | CLS | TBT |
|---|---|---|---|---|
| Product | 97 | 91 | 0 | 10 ms |
| Catalog | 98 | 93 | 0 | 10 ms |
| Home | 100 | 97 | 0 | 0 ms |

**Security and GDPR.** Security headers, hidden WordPress version, XML-RPC
answering 403, `DISALLOW_FILE_EDIT`, login attempt limits, and a honeypot plus
timing trap instead of reCAPTCHA (which would mean Google scripts on every page
and an extra GDPR question). GA4 and Meta Pixel are **absent from the HTML**
until consent, rather than loaded and then blocked.

**Backups.** UpdraftPlus keeps the database daily and files weekly. Restore is
proven, not assumed: a deleted product with four variations came back intact
from the archive.

## Needs the client's own accounts

Two things cannot be finished without credentials the shop owner holds:

- **Przelewy24 live keys** — even a P24 sandbox requires a production account,
  which requires a registered business.
- **InPost ShipX and Orlen tokens** — without them the parcel-point widget and
  label printing stay inactive.
The code for both is in place and starts working as soon as keys exist.

## Client documentation

Written in Polish with `{{ }}` placeholders:

| File | Covers |
|---|---|
| `docs/instrukcja-klienta.md` | manual for a non-technical owner, 9 chapters |
| `docs/handoff-checklist.md` | project handover checklist |
| `docs/platnosci.md` | switching payments to live keys |
| `docs/dostawa.md` | what to fill in for InPost and Orlen |
| `docs/seo-lokalne.md` | adding a landing page for another city |
| `docs/bezpieczenstwo-kopie.md` | security and restoring backups |
| `docs/polityka-prywatnosci.md`, `docs/regulamin.md` | legal templates for the client's lawyer to review |
| `docs/performance.md` | performance figures to quote in offers |

Build the manual as PDF with `sh scripts/make-manual.sh` (needs pandoc and a
PDF engine).

## Scripts

| Script | Purpose |
|---|---|
| `scripts/new-project.sh` | idempotent provisioning from scratch |
| `scripts/seed-demo.sh` | demo data: 12 products, categories, reviews, coupon |
| `scripts/check-contrast.js` | WCAG check for tokens and every preset |
| `scripts/check-personalization.php` | seven personalization test cases |
| `scripts/check-payments.php` | payment configuration check |
| `scripts/subset-font.sh` | rebuild the font subset for Latin and Polish |
| `scripts/make-manual.sh` | markdown to PDF |

## Settled decisions

GeneratePress with a child theme · native Gutenberg blocks, no Elementor in the
core · Przelewy24 and PayPal · InPost and Orlen Paczka · Rank Math free · a
cache layer abstracted across LiteSpeed, WP Rocket and W3TC so the choice
follows the client's hosting · UpdraftPlus · Docker and wp-cli locally. No paid
plugins.

## Development rules

- One concern per file in `inc/`, never a pile in `functions.php`
- All strings through `__()` with the `kramo` text domain
- Polish in the client-facing interface, English in code
- Never patch WooCommerce core — hooks and filters only
- Update `CHANGELOG.md` with every task

Secrets stay out of the repository: `docker/.env` is git-ignored and
`.env.example` ships with empty fields.
