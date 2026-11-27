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

Uses the real inlined `#consentgate-boot` / `#consentgate-config` / `#consentgate-blocker` scripts (as WordPress emits them) and asserts post-consent unwrap + no re-blocking.

```bash
node tests/embed-aspect/prove-widgets.mjs
```

## Own scripts never stamped

```bash
node tests/embed-aspect/prove-self-scripts.mjs
```

## PHP proofs

```bash
php tests/php/prove-mapkit-inline.php
php tests/php/prove-external-iframes.php
```

Artifacts go to `/opt/cursor/artifacts` or `$CG_ARTIFACTS`.
Generated HTML fixtures are written under `tests/embed-aspect/_generated-*.html` (gitignored).
