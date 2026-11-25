# Embed aspect-ratio proof

Reproduces the WordPress Core `::before` spacer colliding with `.cg-embed-wrap`.

```bash
npm i playwright
npx playwright install chromium
PLAYWRIGHT_MODULE="$(pwd)/node_modules/playwright" node tests/embed-aspect/prove.mjs
```

Artifacts (screenshots + JSON) go to `/opt/cursor/artifacts` or `$CG_ARTIFACTS`.
