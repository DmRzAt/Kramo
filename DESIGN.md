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

One is cold and tidy, the other warm and loud. Nobody in this space is **warm, calm and trust-forward at once** — that is the opening Kramo takes.

## Aesthetic Direction

- **Direction:** warm material craft with editorial hierarchy.
- **Decoration level:** intentional — warmth comes from paper-toned surfaces and photography, not ornament.
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

## Layout

- **Approach:** hybrid — grid-disciplined catalog, editorial home and product pages.
- **Grid:** 2 columns mobile, 3 tablet, 4 desktop.
- **Max width:** 1200px (service preset).
- **Media ratio:** 4:5 everywhere.
- **Radius:** cards and buttons 10px, large surfaces 16px. Never pill-shaped — pills read as consumer-app, not workshop.

## Motion

- **Approach:** intentional. Motion confirms actions and reveals structure; it never performs.
- **Entrance:** scroll reveal, 14px rise + fade, 250ms, 60ms stagger.
- **Hover:** card lifts 4px with a layered shadow, product photo scales 1.04 over 420ms.
- **Press:** 0.97 scale.
- **Header:** shadow appears once scrolled past 24px.
- **Easing:** `--ease-standard cubic-bezier(.2,0,0,1)`, `--ease-out cubic-bezier(.16,1,.3,1)`.
- **Reduced motion:** every transition and animation is disabled under `prefers-reduced-motion: reduce`.

## Polish Market Layer

Non-negotiable, because these are the signals local shoppers check before they browse:

- Trust strip directly under the header: BLIK, Paczkomat InPost + Orlen Paczka, free-delivery threshold, 14-day returns, Polish workshop.
- Prices formatted `1 234,56 zł` with tabular numerals.
- Copy in Polish; English only in code.

Competitors bury these in the footer. Kramo puts them where the decision happens.

## Presets

The system ships as the `service` preset. `craft` and `premium` keep their own palettes and inherit the same tokens, motion and spacing.

## Decisions Log

| Date | Decision | Rationale |
|------|----------|-----------|
| 2026-07-26 | Design system created | /design-consultation, grounded in research on rzeczysame.pl and pakamera.pl |
| 2026-07-26 | Cabinet Grotesk + General Sans rejected after download | Fontshare Free EULA §02 forbids modification and public redistribution, so subsetting and shipping them in a public repo would breach it |
| 2026-07-26 | Bricolage Grotesque + Instrument Sans adopted | SIL OFL allows subsetting, bundling and client delivery; both cover Polish diacritics |
| 2026-07-26 | Amaranth `#A32D4F` as the single accent | Locally legible, unused by competitors, passes AA at 6.90:1 |
