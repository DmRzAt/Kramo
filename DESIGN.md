# Design System — Kramo

## Product Context

- **What this is:** a WooCommerce starter kit for the Polish market, sold as adaptation work to Useme clients and demoed publicly at kramo-production.up.railway.app.
- **Who it's for:** Polish shop owners (handmade, home goods, small services) and their customers, who shop on mobile and decide fast.
- **Space:** Polish e-commerce, competing against stores built by freelancers.
- **Project type:** storefront with catalog, product, cart and checkout.

## The Memorable Thing

**"Widać, że zrobione ręką — i widać, że kupię bez ryzyka."**

Craft and safety in the same glance. Every decision below serves that sentence: warmth and materiality prove the craft, the trust strip and restraint prove the safety.

## Research (2026-07-26)

| Site | Strength | Gap |
|---|---|---|
| rzeczysame.pl | calm, white, letterspaced caps, editorial photo tiles | 13-item nav, thin type, zero trust signals above the fold, no motion |
| pakamera.pl | warm, human, 53 936 reviews as social proof | banner chaos, no hierarchy, reads like a bazaar |
| housebrand.com | photography-led flush grid, text filters, campaign cover hero | fashion retail scale; cold white, Montserrat, no craft/trust layer |

One is cold and tidy, the other warm and loud. Nobody in this space is **warm, calm and trust-forward at once** — that is the opening Kramo takes. House supplies the catalog grammar (flush tiles, quiet captions, filters as caps) without taking the brand voice.

## Aesthetic Direction

- **Direction:** warm material craft with editorial, photography-led hierarchy.
- **Decoration level:** intentional — warmth comes from paper-toned surfaces and photography, not ornament. Catalog tiles carry no chrome of their own.
- **Mood:** a well-lit workshop. Confident, unhurried, nothing shouting.

## Typography

- **Display:** Bricolage Grotesque (variable, SIL OFL, self-hosted) — a grotesque with slightly irregular terminals. Character without costume; avoids the "high-contrast serif" cliché of the handmade category.
- **Body / UI:** Instrument Sans (variable, SIL OFL, self-hosted) — quiet, legible, tabular numerals for prices.
- **Fallback:** Inter Variable stays in the theme as a fallback only.
- **Loading:** self-hosted, subset to Latin + Polish, `font-display: swap`, both preloaded. No Google Fonts — external font requests before consent are a RODO problem.
- **Weights:** Bricolage 200–800 (wdth and opsz pinned), Instrument 400–700 (wdth pinned).
- **Scale:** `--text-xs .75rem` → `--text-4xl 3rem`, hero `clamp(2rem, 5vw + 1rem, 4rem)`.

Why not Inter: everyone ships Inter. It is the typographic equivalent of saying nothing.

## Color

- **Approach:** restrained. One accent, used only for primary CTA and active state.
- **Paper:** `#F7F5F2` — warm off-white. Deliberately not `#F4F1EA` cream, which reads as AI-generated when paired with a serif.
- **Surface:** `#FFFFFF`
- **Ink:** `#1C1A17` — warm near-black, never blue-black.
- **Muted ink:** `#5D5850`
- **Line:** `#E2DDD6`
- **Accent (amaranth):** `#A32D4F` — a Polish heraldic colour. Local without folk cliché, and nobody in the category uses it. Hover `#86213F`.
- **Trust (pine):** `#2F4F3F` — carries the trust strip icons so safety signals read as calm, not promotional.
- **Contrast:** enforced by `scripts/check-contrast.js`; amaranth on white is 6.90:1.

## Spacing

- **Base unit:** 8px (`--space-unit: .5rem`).
- **Density:** comfortable, generous on marketing sections.
- **Section rhythm:** `--section-gap: clamp(4rem, 8vw, 6rem)`.

## Footer

- **Structure:** four columns — brand + tagline, Sklep, Pomoc, Kontakt — then a thin copyright bar.
- **Floor:** dark ink under the paper storefront (`--color-footer-*`), so the page ends with a clear close rather than fading into the background.
- **Copy:** Polish only; demo installs show a portfolio disclaimer on the bar.

## Layout

- **Approach:** Rzeczy Same axis on the home page (one reading column, gallery rails, lifestyle tiles) plus a photography-led shop catalog.
- **Grid:** 2 columns mobile, 3 tablet, 4 desktop on the shop (density toggle 2 / 4 on tablet+). Home product rails lock to 3 columns from tablet up.
- **Home:** campaign cover may bleed; everything else shares `--container-max` and `--inset-inline`. Section titles are quiet letterspaced caps. Three lifestyle category tiles (`[kramo_home_tiles]`) sit between the product rails.
- **Catalog:** photography-led grid with a hairline column gap (`--space-1`). Captions sit under the photo with a 16px inset so copy never kisses the viewport. No card background, border, radius or shadow on tiles.
- **Media ratio:** 3:4 in the catalog (portrait photography). Home lifestyle tiles use 4:3 mobile / 3:4 desktop. Product gallery inherits the catalog ratio.
- **Max width:** 1200px for reading surfaces (header, filters, product summary, trust strip, home rails).
- **Reading inset:** never flush text to the viewport. `--container-pad` floors at 1rem (Apple HIG / Material 16pt layout margin); `--inset-inline` also respects `safe-area-inset-*`.
- **Radius:** buttons and panels 4px (`--radius-sharp`). Media and catalog tiles 0. Never pill-shaped — pills read as consumer-app, not workshop.
- **Filters:** a single hairline row of letterspaced caps (Kategoria, Kolor, Rozmiar, Cena), plus result count and density. No boxed panel.

## Motion

- **Approach:** intentional. Motion confirms actions and reveals structure; it never performs.
- **Entrance:** scroll reveal, 14px rise + fade, 250ms, 60ms stagger.
- **Hover:** catalog tiles do not lift — there is no gap for a raised card to sit in. The photo scales 1.04 over 420ms and an action bar (add to cart / choose options, quick view) rises from the bottom of the frame. Touch devices keep the bar always visible.
- **Press:** 0.97 scale.
- **Header:** shadow appears once scrolled past 24px.
- **Easing:** `--ease-standard cubic-bezier(.2,0,0,1)`, `--ease-out cubic-bezier(.16,1,.3,1)`, `--ease-spring cubic-bezier(.34,1.4,.64,1)` for confirmations only.
- **Loading:** never dim a region to signal work. Show a skeleton whose card count matches what is already on screen, so nothing moves and CLS stays 0. Loading copy is visible text, not an invisible attribute.
- **Confirmation:** every action that changes cart or wishlist state answers in three places — the control itself, the header badge, and a toast. Spinner 640ms linear, check and badge bump on `--ease-spring`.
- **Navigation:** cross-document View Transitions carry the product photo from card to product page. Progressive: browsers without support simply navigate.
- **Reduced motion:** every transition and animation is disabled under `prefers-reduced-motion: reduce`, including view transitions. Scroll reveal is never applied at all under reduce, so no element can be left at opacity 0.

## Polish Market Layer

Non-negotiable, because these are the signals local shoppers check before they browse:

- Trust strip directly under the header: BLIK, Paczkomat InPost + Orlen Paczka, free-delivery threshold, 14-day returns, Polish workshop.
- Prices formatted `1 234,56 zł` with tabular numerals.
- Copy in Polish; English only in code.

Competitors bury these in the footer. Kramo puts them where the decision happens.

## Presets

The system ships as the `service` preset. `craft` and `premium` keep their own palettes **and their own type pairing**, and inherit the same tokens, motion and spacing.

| Preset | Display | Body | Voice |
|---|---|---|---|
| service | Bricolage Grotesque, bold | Instrument Sans | workshop, confident |
| craft | Instrument Sans 800, uppercase, `--tracking-caps` | Inter Variable | stencilled, workshop-floor |
| premium | Inter Variable 200, `--tracking-wide` | Instrument Sans | editorial, quiet |

All three families are already self-hosted under SIL OFL. No new font files, no new licence surface.

## Decisions Log

| Date | Decision | Rationale |
|------|----------|-----------|
| 2026-07-26 | Design system created | /design-consultation, grounded in research on rzeczysame.pl and pakamera.pl |
| 2026-07-26 | Cabinet Grotesk + General Sans rejected after download | Fontshare Free EULA §02 forbids modification and public redistribution, so subsetting and shipping them in a public repo would breach it |
| 2026-07-26 | Bricolage Grotesque + Instrument Sans adopted | SIL OFL allows subsetting, bundling and client delivery; both cover Polish diacritics |
| 2026-07-26 | Amaranth `#A32D4F` as the single accent | Locally legible, unused by competitors, passes AA at 6.90:1 |
| 2026-07-27 | Skeletons replace `opacity: .38` for every loading region | A dimmed grid reads as broken; a skeleton reads as working. Card count is cloned from the live grid so CLS stays 0 |
| 2026-07-27 | Own add-to-cart spinner instead of the WooCommerce icon font | The most-seen animation in the shop was a grey stock glyph that ignored this document |
| 2026-07-27 | Cart state travels in a first-party cookie, never in HTML | Shop and marketing pages sit in a shared page cache; a server-rendered count would show one visitor's cart to everyone |
| 2026-07-27 | Per-preset typography; craft drops Trebuchet MS | Three presets that differ only in colour are one theme sold three times. Trebuchet was also the last non-self-hosted family in the system |
| 2026-07-27 | Cross-document View Transitions adopted | One CSS at-rule plus two names buys an SPA-grade transition; both researched competitors ship no motion at all |
| 2026-07-27 | Hybrid House catalog grammar for service | Flush 3:4 grid, chrome-free tiles, text filters, campaign cover hero — kept paper, amaranth, trust strip and OFL fonts so Kramo stays warm craft, not fashion retail |
| 2026-07-27 | Home adopts Rzeczy Same axis | One reading column, gallery rails, three lifestyle tiles; trust strip stays Kramo’s differentiator |
