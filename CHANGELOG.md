# Changelog

## Unreleased

### Changed

- Internal meta, filter query and consent cookie keys renamed from `ws_*` / `_ws_*` to `kramo_*` / `_kramo_*`, with dual-read fallbacks so existing demo data and bookmarks keep working.

### Added

- Dark four-column storefront footer (brand, Sklep, Pomoc, Kontakt) with Kramo copy, help-page stubs and a demo portfolio note.
- Home lifestyle category tiles (`[kramo_home_tiles]`) and a Rzeczy Same reading axis for the storefront home page.
- Docker Compose environment with WordPress, MariaDB, phpMyAdmin and WP-CLI.

### Fixed

- Storefront wordmark reads **Kramo** (capital K) with a simple monogram instead of a lowercase shopping-bag mark.
- Home product rails align heading, photos and captions on the same inset; catalog captions use a 16px inset and a clearer tile gap.
- Space between the last home product rail and the footer so the dark floor does not collide with the photos.
- Home campaign hero and reading surfaces no longer flush text to the viewport; inset uses a 1rem floor plus safe-area margins.
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
- Polish Appearance → Kramo preset selector with optional wp-config.php override.
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
- Consent-gated analytics in inc/consent.php: GA4 and Meta Pixel identifiers are entered in Appearance, Kramo and the scripts reach the browser only after the visitor accepts.
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
- Catalog skeleton loader replacing the dimmed grid during filtering and pagination. Skeleton card count matches the cards already on screen, so the page height does not move and CLS stays at 0.
- Reusable scroll-reveal (`window.kramo.reveal`) that also runs on AJAX-inserted cards, so filtered results animate in like the first page load instead of appearing instantly.
- Screen-reader result announcement for the catalog: a `role="status"` region reports the loading state and the number of products found.
- Own add-to-cart loading and confirmation states, replacing the stock WooCommerce icon-font spinner with a token-driven ring and a check that pops on `--ease-spring`.
- Header utility row with product search and a cart link, rendered on `generate_after_header`.
- Live product search with a debounced AJAX dropdown showing thumbnail, name and price, full arrow-key and Escape support, and an ARIA combobox contract.
- Cache-safe cart badge and "Wróć do koszyka" recovery banner driven by a first-party `kramo_cart_state` cookie holding only an item count and a formatted total, so shop and marketing pages stay in the shared page cache. The badge bumps when the count changes.
- Toast region (`wp_footer`) with polite live announcements for cart and wishlist confirmations.
- Quick view modal on catalog cards using a native `<dialog>`, with variation swatches and quick-add that posts to `?wc-ajax=add_to_cart` and keeps the visitor on the catalog.
- Recently viewed strip backed by localStorage and rendered through AJAX, shown on WooCommerce screens and the cart, excluding the product being viewed.
- Wishlist heart pop animation on toggle plus a polite announcement, and a skeleton while the Ulubione page loads its products.
- Fade-in for catalog images that are still loading when the script runs. The `fetchpriority` LCP tile is excluded so the measured LCP is untouched, and images already complete are left alone to avoid a flash.
- Cross-document View Transitions (`@view-transition { navigation: auto }`) with a per-product `view-transition-name` set on the clicked card and the matching product gallery image.
- Mobile sticky purchase bar that slides in when the main add-to-cart button leaves the viewport and submits the real product form.
- Checkout step indicator (Koszyk / Dane i dostawa / Płatność) on the cart and checkout, plus a shake on fields WooCommerce marks invalid.
- Designed empty states for a filtered catalog with no matches and for the empty cart, replacing the stock WooCommerce notices.
- Catalog stock badges for low and out-of-stock products, reusing `kramo_availability_status()` so the catalog and product page never disagree.
- Per-preset typography so the three presets no longer read as one theme recoloured: craft moves to uppercase extrabold Instrument Sans over Inter, premium to extralight Inter display over Instrument Sans. Both use fonts already self-hosted under SIL OFL.
- Customer account entry point in `inc/account.php`: an account link in the header that reads `Zaloguj się` for guests and `Moje konto` once signed in, resolved from `wc_get_page_id( 'myaccount' )` so it never renders a link to nowhere.
- Account creation enabled end to end: registration on the account page, sign-up from checkout and the returning-customer login reminder, set in both provisioning scripts and defaulted in the theme for installs that never run them.
- `--color-trust` is now a semantic token in `tokens.css` backed by a shared `--pine` primitive, so DESIGN.md's calm trust colour applies to every preset instead of only to service.

### Fixed

- The quick-view trigger covered the catalog CTA. It was absolutely positioned at the bottom-right of the card, but still matched `.woocommerce ul.products li.product .button`, so it inherited `width: 100%` and a top margin and rendered as a full-width bar across "Wybierz opcje", overflowing the card edge. It is now a quiet text action in the flow under the CTA, where it cannot overlap anything and no longer depends on a hover reveal that touch devices never get.
- The header utility row placed a single search field and the cart link in a `space-between` row, so removing either one left the survivor stranded at the wrong edge. The cart link now pins itself to the trailing edge on its own.
- The mobile `order: 3` rule on `.kramo-search` was global, so the search would have jumped below the filter form once it moved into the catalog panel. It is now scoped to the header row that needs it.
- Critical CSS inverted its own cascade. `kramo_inline_critical_css()` registered a fresh `kramo-critical` handle at priority 100, so the inlined tokens, base and preset printed *after* `woo.css`; at equal specificity the generic `button` rule in base.css then beat every single-class component rule in woo.css. The visible symptom was the quick-view close button rendering as a filled amaranth CTA. The bundle now rides on the `kramo-tokens` handle, which prints first, and woo.css is last again.
- The quick-view modal had no `div.product` wrapper, so every `.woocommerce div.product` rule missed it: variations rendered as WooCommerce's default bordered table instead of the block layout, and `Kup teraz` came out as a second filled amaranth button beside `Dodaj do koszyka`. The body now carries `wc_product_class()`.

- Wishlist toggle now meets the 44x44 minimum touch target (`--wishlist-button-size` 2.5rem to 2.75rem); at 40px it was easy to mis-tap on a phone, which read as a broken feature.
- The wishlist toggle was missing from the `:active` press-scale rule that DESIGN.md requires for interactive controls.
- The saved wishlist state now switches the glyph from an outline to a filled heart instead of relying on the button background alone.
- The mobile trust strip no longer stacks into five rows; tighter gaps and smaller type bring it to three rows (90px instead of roughly 150px), so the catalog starts higher.
- The local-service landing page left the primary menu and the home-page hero, where a paving-stone service sat between linen shirts and blankets. It now lives in the footer as `Przykład strony usługowej`, so the template stays demoable without breaking the storefront story. The freed hero button points at the Dom category, resolved through `get_term_link()` rather than a hard-coded permalink.
- `kramoFilters.loadingText` was translated, localized and written into a data attribute that no stylesheet ever read, so the loading copy was invisible. It now renders through the skeleton status region.
- Catalog cards inserted by the AJAX filter never received `.kramo-reveal`, so filtered results appeared instantly while first-load cards animated. `main.js` now listens for `kramo:catalog-updated` and reveals the new cards.
- Paginating the catalog swapped the products without moving the viewport, leaving mobile visitors in the middle of a page they had not seen start. Pagination now scrolls back to the top of the results.
- The Ulubione page replaced its container contents with no intermediate state, showing an empty area while the request was in flight.
- Toggling a wishlist heart changed only `aria-pressed` and the label, giving no visible or announced confirmation that the click registered.
- `aria-live="polite"` sat on the whole catalog results container, so every filter change queued the entire product grid for announcement. The attribute moved to a dedicated status element that reports only the result count.
- Quick-add posted the form's original `add-to-cart` field alongside `product_id`, and `WC_Form_Handler::add_to_cart_action()` on `wp_loaded` added the item a second time on top of the AJAX handler, doubling the quantity. Both `add-to-cart` and `kramo_buy_now` are now stripped from the payload.
- Prices returned by the search and recently-viewed endpoints carried raw HTML entities and WooCommerce's screen-reader price-range duplicate. Written with `textContent`, this surfaced as literal `&#122;&#322;` text; `kramo_plain_price()` now strips the duplicate and decodes entities.
- The recently-viewed strip rendered on `woocommerce_after_main_content` at priority 20, after WooCommerce closes the content wrapper at 10, which placed it in a column beside the catalog instead of under it.
- Customers had no way to log in or register. The provisioning script creates the `Moje konto` page and points `woocommerce_myaccount_page_id` at it, but the only link to it sat in the footer menu, and `woocommerce_enable_myaccount_registration` was `no` in the database, so even visitors who found the page saw a login box with no way to create an account. The header now carries the account link and the registration form is on.
- Registration asked only for an e-mail and mailed a password link, which the demo's own `wp_mail` block swallowed, so a demo sign-up could never be completed. Customers now choose a password in the form.
- The registration and checkout privacy sentences were WooCommerce's English defaults on a Polish storefront. Both are now Polish, phrased so the `[privacy_policy]` link, which renders as the page title, stands as the subject of its own sentence instead of forcing a wrong case ending.
- Demo mode promised that no payment is taken while only blocking e-mail. It now keeps offline gateways alone and removes every processor from the checkout, even when `KRAMO_PAYMENT_MODE` is set to live, and repeats the warning above the checkout form.
- `Cross-Origin-Opener-Policy: same-origin` was sent on every response, which severs `window.opener` for the popup flows PayPal and some BLIK integrations complete in. Cart, checkout and account now get `same-origin-allow-popups`, matched on the request path because the header is sent before conditional tags are usable.
- Search and filters cancelled each other: the search form dropped active filters, the filter form always posted to the shop permalink and so lost the search term, and `pre_get_posts` skipped product search results entirely, so filters silently did nothing there. Both forms now carry the other's state and the filters apply to search results.
- WooCommerce floats the login submit button and lets the remember-me checkbox flow around it, so `Zapamiętaj mnie` sat beside `Zaloguj się` with its label wrapped under its own checkbox. The checkbox takes the row above the button.
- The service preset contradicted DESIGN.md in two places: `--card-transform-hover` was `none` against the specified 4px hover lift, and `--section-gap` was a flat 48px against `clamp(4rem, 8vw, 6rem)`. Both overrides are gone.
- `--font-family-humanist` still declared Trebuchet MS in `tokens.css` with no rule referencing it, a leftover of a proprietary family in a system that ships only self-hosted OFL fonts.

### Changed

- Service catalog takes the House photography grammar without the fashion retail voice: flush edge-to-edge 3:4 grid, chrome-free tiles (no card background, border, radius or lift), quiet captions, and a hairline text toolbar (Kategoria / Kolor / Rozmiar / Cena) with result count and a 2/4 density toggle. Buying actions and quick view rise over the photo on hover/focus and stay visible on touch. Paper, amaranth, trust strip and OFL fonts stay.
- Catalog tiles split the product link so it wraps only the photo; the name is its own link and the action bar lives outside the anchor. `--catalog-*` tokens keep this chrome-free grid separate from the `--card-*` tokens that still dress panels and modals. Craft and premium keep their wider gutters and raised tiles.
- Home campaign hero is a full-bleed cover block with the headline in the photograph; product rails under it also run edge to edge. Seed content and `DESIGN.md` updated to match.
- Product search moved from the header utility row into the catalog filter panel, one field above Kategoria, so searching and narrowing happen in the same place instead of two rows apart. Both are separate forms, so a shared `.kramo-catalog-toolbar` carries them. The toolbar is now its only home: the header carries no search at all, and `search.js` is enqueued only where the field exists.
- `kramo_is_catalog_screen()` replaces the three inline `is_shop() || is_product_taxonomy()` checks, and the search script is enqueued on the catalog screens that actually render the field instead of riding on the header-utility flag it no longer belongs to.
- The header utilities moved out of their own bordered strip and into the primary menu (`inc/header-nav.php` replaces `inc/header-utility.php`), next to Sklep, so the header is one row instead of two and the cart no longer floats alone across a wide gap. The strip survives only as a fallback for installs with no primary menu assigned. The menu carries the account link and the cart only; the search stays in the catalog toolbar.
- The header cart carries a real label element instead of a `screen-reader-text` span, because GeneratePress hides that class with `!important`. The label is visually hidden on desktop and becomes visible copy in the collapsed mobile menu, where an icon-only row read as an empty list item.
- `kramo_dynamic_page_paths()` in `inc/woocommerce.php` resolves the cart, checkout and account paths for callers that run before the main query exists; `kramo_cache_excluded_paths()` now delegates to it instead of repeating the lookup.

- Contrast checker now validates each preset's token-only structure and color pairs.
- Service is now the default preset for new installations and invalid preset values.
- Demo products now include global filterable attributes, gallery and variation images, sale prices and one unavailable combination.
- Demo bootstrap disables WooCommerce Coming soon so the seeded catalog is publicly testable.
- The first demo product now includes required font personalization with a 20-character limit and a 20 zł surcharge.
- Demo products now carry realistic per-item weights so shipping calculates correctly.
- The temporary free-shipping stopgap in the rest-of-world zone is replaced by the dedicated Polska shipping zone.
- The bootstrap script exports MSYS_NO_PATHCONV so Windows Git Bash no longer corrupts the permalink structure or container paths.
- Demo content now includes a product FAQ and an example local-service landing page for Katowice.
- Catalog and product images now use the 3:4 portrait ratio the grid expects instead of WooCommerce's square 300px default.
- The product gallery reserves its aspect ratio before flexslider initialises, and mobile hides the thumbnail strip, removing the layout shift that followed page load.
- The service preset moves to the warm paper and amaranth palette from DESIGN.md, with Bricolage Grotesque for display type instead of Inter.
- Demo products and categories now ship real Pexels-licensed photography from `scripts/demo-assets` (a primary and a distinct gallery/hover image per product, plus a thumbnail per category) instead of generated solid-colour blocks; the generated PNG stays as a fallback when the assets are absent.
- The demo image seed is namespaced to `photo-*` keys and deletes the legacy `product-*` placeholder attachments on run, so re-seeding an existing demo database swaps in the photos and leaves no orphans.
