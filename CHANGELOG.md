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

### Changed

- Contrast checker now validates each preset's token-only structure and color pairs.
- Service is now the default preset for new installations and invalid preset values.
