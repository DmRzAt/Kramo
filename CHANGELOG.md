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
