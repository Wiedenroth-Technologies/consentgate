# Embed proofs (Playwright)

```bash
npm i playwright
npx playwright install chromium
export PLAYWRIGHT_MODULE="$(pwd)/node_modules/playwright"
```

## YouTube aspect-ratio

```bash
node tests/embed-aspect/prove.mjs
```

## Apple Maps (MapKit) + reopen contrast

```bash
node tests/embed-aspect/prove-apple-maps.mjs
```

## Widget registry (Instagram / X / TikTok / unknown iframe)

```bash
node tests/embed-aspect/prove-widgets.mjs
```

## PHP proofs

```bash
php tests/php/prove-mapkit-inline.php
php tests/php/prove-external-iframes.php
```

Artifacts go to `/opt/cursor/artifacts` or `$CG_ARTIFACTS`.
