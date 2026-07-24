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

### Changed

- Contrast checker now validates each preset's token-only structure and color pairs.
- Service is now the default preset for new installations and invalid preset values.
- Demo products now include global filterable attributes, gallery and variation images, sale prices and one unavailable combination.
- Demo bootstrap disables WooCommerce Coming soon so the seeded catalog is publicly testable.
