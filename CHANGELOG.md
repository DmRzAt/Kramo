# Changelog

## Unreleased

### Added

- Docker Compose environment with WordPress, MariaDB, phpMyAdmin and WP-CLI.
- Idempotent Polish WordPress and WooCommerce bootstrap script.
- Idempotent demo seed with 12 variable products, three categories, 1200x1500 placeholder images, reviews and a coupon.
- Make targets for common local-development operations.
- GeneratePress child-theme foundation with modular setup, asset, cleanup, administration, preset and WooCommerce files.
- Self-hosted Inter variable font with preload and SIL Open Font License.
- Client-focused Polish dashboard guidance and a configurable support footer.
- Lean public assets with WooCommerce bundles limited to pages that use store features.
- Three-layer design-token system for palette, typography, spacing, containers and component overrides.
- Token-driven base and WooCommerce styles with accessible focus, motion and contrast preferences.
- Dependency-free WCAG AA contrast checker for key storefront color pairs.
- Three distinct variable-only visual presets: craft, service and premium.
- Polish Appearance → Woo Starter preset selector with optional wp-config.php override.
- Portfolio screenshots of the homepage and product page for every preset.
- Responsive WooCommerce catalog cards with 4:5 media, gallery hover, sale badges, color swatches and native lazy-loading priorities.
- Shareable category, price, color and size filters with AJAX updates, reset and browser history.
- Product-page variation swatches, side thumbnails, Buy now action, responsive tabs/accordion and four related products.
- Lazy YouTube, Vimeo and MP4 product-gallery video field with a WordPress media picker.
- Plugin-free guest/customer wishlist with an Ulubione shortcode page and login synchronization.
- Required verified-purchase reviews with Polish storefront labels.
- Catalog and variable-product browser QA screenshots.
- Product-level personalization settings for text, font or thread-color choices, required fields and optional surcharges.
- Character counter plus client/server personalization validation with multibyte-safe sanitization and truncation.
- End-to-end personalization metadata in cart, checkout, order details and customer/admin email templates.
- Reproducible seven-case personalization checker and a project progress report through task 06.
- Official Przelewy24 and WooCommerce PayPal Payments installation in new project bootstrap.
- Environment-only sandbox/live payment configuration with mode-specific credentials and database-write protection.
- Payment callback readiness checker and Polish production handoff documentation.
- Early MU-plugin payment bootstrap so gateway runtime clients receive environment overrides before regular plugins load.
- Polish shipping zone with InPost Paczkomat, InPost Kurier, Orlen Paczka methods and a free-shipping threshold, created idempotently in bootstrap.
- Code-based weight tiers with a lower parcel-locker profile than courier and paid methods hidden above the free-shipping threshold.
- Pickup-point visibility in the admin order, customer/admin e-mails and the order-received page, reading a normalized key plus common InPost/Orlen keys via a filter.
- Best-effort InPost and Orlen Paczka plugin installation in the project bootstrap.
- Polish shipping handoff documentation (docs/dostawa.md).
- Rank Math base configuration without the wizard: sitemap enabled with tags and authors excluded, breadcrumbs on and the Organization knowledge graph set.
- Structured data in inc/schema.php: LocalBusiness with areaServed and FAQPage, plus Organization, Open Graph and canonical fallbacks that step aside when Rank Math is active, and an hreflang stub.
- Product FAQ field with a front-end accordion tab that also feeds FAQPage schema.
- Local-service page template (page-local-service.php) with an editor meta box for service/city landing pages.
- Polish SEO and local-page documentation (docs/seo-lokalne.md).
- Host-agnostic cache layer in inc/cache.php that detects LiteSpeed, WP Rocket or W3TC, keeps cart, checkout and account pages out of shared caches and purges product pages on stock or price changes.
- Delivery tuning in inc/performance.php: inlined critical CSS, asynchronous non-critical styles, payment assets limited to cart and checkout, and priority hints allowed through kses.
- Latin and Polish font subset script (scripts/subset-font.sh) cutting the variable font from 352 KB to 56.6 KB.
- WebP conversion through the Modern Image Formats plugin, configured in bootstrap.
- Performance measurements and cache handover notes (docs/performance.md).
- Plugin-free hardening in inc/security.php: security headers, hidden WordPress version, XML-RPC turned off behind a filter, generic login errors, user-enumeration blocking and a honeypot plus timing trap for forms.
- Limit Login Attempts Reloaded and the DISALLOW_FILE_EDIT constant in bootstrap, with UpdraftPlus scheduled for daily database and weekly file backups.
- Consent-gated analytics in inc/consent.php: GA4 and Meta Pixel identifiers are entered in Appearance, Woo Starter and the scripts reach the browser only after the visitor accepts.
- Polish privacy policy, terms and security/backup handover templates in docs/.
- Polish client manual covering login, products, variations, personalization, photos, orders, coupons, backups and first-aid troubleshooting.
- PDF build script (scripts/make-manual.sh) that detects pandoc and an available engine, adds a table of contents and an optional client logo, and explains what to install when either is missing.
- Handover checklist covering payments, shipping, legal pages, analytics consent, security, backups, SEO, performance, access and documentation.
- Screenshot plan for the manual so each project ships images from the client's own shop.
- Design system of record in DESIGN.md, grounded in research on two Polish market leaders, plus a CLAUDE.md rule that ties future UI work to it.
- Trust strip under the header carrying BLIK, InPost and Orlen pickup, the live free-delivery threshold, 14-day returns and Polish workshop.
- Scroll reveal, sticky-header shadow and hover choreography, all governed by the reduced-motion rule.
- Self-hosted Bricolage Grotesque and Instrument Sans under SIL OFL, subset to Latin and Polish, with axis pinning in the subset script.
- Live wishlist counter appended to menu links pointing at the Ulubione page, hidden while the list is empty.
- Stock and dispatch signals under the product price (inc/availability.php): availability state, low-stock count and a filterable `Wysyłka w 24 h` promise that disappears when nothing can ship.
- Product size-guide field and front-end table tab (inc/size-guide.php), following the FAQ field pattern, with a seeded example table on the demo clothing.

### Fixed

- Wishlist toggle now meets the 44x44 minimum touch target (`--wishlist-button-size` 2.5rem to 2.75rem); at 40px it was easy to mis-tap on a phone, which read as a broken feature.
- The wishlist toggle was missing from the `:active` press-scale rule that DESIGN.md requires for interactive controls.
- The saved wishlist state now switches the glyph from an outline to a filled heart instead of relying on the button background alone.
- The mobile trust strip no longer stacks into five rows; tighter gaps and smaller type bring it to three rows (90px instead of roughly 150px), so the catalog starts higher.
- The local-service landing page left the primary menu and the home-page hero, where a paving-stone service sat between linen shirts and blankets. It now lives in the footer as `Przykład strony usługowej`, so the template stays demoable without breaking the storefront story. The freed hero button points at the Dom category, resolved through `get_term_link()` rather than a hard-coded permalink.

### Changed

- Contrast checker now validates each preset's token-only structure and color pairs.
- Service is now the default preset for new installations and invalid preset values.
- Demo products now include global filterable attributes, gallery and variation images, sale prices and one unavailable combination.
- Demo bootstrap disables WooCommerce Coming soon so the seeded catalog is publicly testable.
- The first demo product now includes required font personalization with a 20-character limit and a 20 zł surcharge.
- Demo products now carry realistic per-item weights so shipping calculates correctly.
- The temporary free-shipping stopgap in the rest-of-world zone is replaced by the dedicated Polska shipping zone.
- The bootstrap script exports MSYS_NO_PATHCONV so Windows Git Bash no longer corrupts the permalink structure or container paths.
- Demo content now includes a product FAQ and an example local-service landing page for Katowice.
- Catalog and product images now use the 4:5 ratio the grid expects (400x500 and 800x1000) instead of WooCommerce's square 300px default.
- The product gallery reserves its aspect ratio before flexslider initialises, and mobile hides the thumbnail strip, removing the layout shift that followed page load.
- The service preset moves to the warm paper and amaranth palette from DESIGN.md, with Bricolage Grotesque for display type instead of Inter.
- Demo products and categories now ship real Pexels-licensed photography from `scripts/demo-assets` (a primary and a distinct gallery/hover image per product, plus a thumbnail per category) instead of generated solid-colour blocks; the generated PNG stays as a fallback when the assets are absent.
- The demo image seed is namespaced to `photo-*` keys and deletes the legacy `product-*` placeholder attachments on run, so re-seeding an existing demo database swaps in the photos and leaves no orphans.
