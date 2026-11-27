# Embed proofs (Playwright)

## YouTube aspect-ratio

Reproduces the WordPress Core `::before` spacer colliding with `.cg-embed-wrap`.

```bash
npm i playwright
npx playwright install chromium
PLAYWRIGHT_MODULE="$(pwd)/node_modules/playwright" node tests/embed-aspect/prove.mjs
```

## Apple Maps (MapKit) placeholder

Covers MapKit JS `#map` containers (no iframe), Apple Maps iframes, YouTube
placeholder still wrapping, and reopen-button banner background contrast.

```bash
PLAYWRIGHT_MODULE="$(pwd)/node_modules/playwright" node tests/embed-aspect/prove-apple-maps.mjs
```

## PHP MapKit inline gating

```bash
php tests/php/prove-mapkit-inline.php
```

Artifacts (screenshots + JSON) go to `/opt/cursor/artifacts` or `$CG_ARTIFACTS`.
