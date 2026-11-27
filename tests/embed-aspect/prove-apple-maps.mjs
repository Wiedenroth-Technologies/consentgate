/**
 * Proof for Apple Maps MapKit placeholder + reopen button contrast.
 *
 * Requires Playwright (dev): npm i playwright && npx playwright install chromium
 * Run from repo root:
 *   PLAYWRIGHT_MODULE="$(pwd)/node_modules/playwright" node tests/embed-aspect/prove-apple-maps.mjs
 */
import { createRequire } from "node:module";
import path from "node:path";
import { fileURLToPath, pathToFileURL } from "node:url";
import fs from "node:fs";

const require = createRequire(import.meta.url);
const { chromium } = require(
  process.env.PLAYWRIGHT_MODULE || "/tmp/node_modules/playwright"
);

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const fixture = pathToFileURL(path.join(__dirname, "apple-maps-fixture.html")).href;
const artifacts = process.env.CG_ARTIFACTS || "/opt/cursor/artifacts";
fs.mkdirSync(artifacts, { recursive: true });

function assert(cond, msg) {
  if (!cond) {
    throw new Error(msg);
  }
}

function parseRgb(value) {
  const m = String(value || "").match(/rgba?\((\d+),\s*(\d+),\s*(\d+)/i);
  if (!m) return null;
  return { r: Number(m[1]), g: Number(m[2]), b: Number(m[3]) };
}

function isLight(rgb) {
  if (!rgb) return false;
  const luma = (0.2126 * rgb.r + 0.7152 * rgb.g + 0.0722 * rgb.b) / 255;
  return luma > 0.7;
}

function isDark(rgb) {
  if (!rgb) return false;
  const luma = (0.2126 * rgb.r + 0.7152 * rgb.g + 0.0722 * rgb.b) / 255;
  return luma < 0.35;
}

const browser = await chromium.launch();
const page = await browser.newPage({ viewport: { width: 900, height: 1100 } });

try {
  await page.goto(fixture);
  await page.waitForTimeout(120);

  const blocked = await page.evaluate(() => window.__CG_APPLE_FIXTURE__.measure());
  await page.locator("section[aria-label='MapKit']").screenshot({
    path: path.join(artifacts, "apple-maps-blocked.png"),
  });
  fs.writeFileSync(
    path.join(artifacts, "apple-maps-blocked.json"),
    JSON.stringify(blocked, null, 2)
  );

  assert(blocked.mapWrap, "MapKit #map placeholder wrap missing");
  assert(blocked.mapHasWidget, "MapKit widget mark missing");
  assert(
    blocked.mapOverlayTitle === "Apple Maps ist blockiert",
    `Unexpected MapKit overlay title: ${blocked.mapOverlayTitle}`
  );
  assert(blocked.mapMinHeight === "400px", `Expected minHeight 400px, got ${blocked.mapMinHeight}`);
  assert(!blocked.mapReady, "Map should not initialize before consent");
  assert(!blocked.mapInitFlag, "MapKit inline init should stay deferred before consent");
  assert(blocked.youtubeWrap, "YouTube placeholder regression: wrap missing");
  assert(blocked.appleIframeWrap, "Apple Maps iframe placeholder wrap missing");
  assert(blocked.appleIframeOverlay, "Apple Maps iframe overlay missing");

  await page.evaluate(() => window.__CG_APPLE_FIXTURE__.showReopen());
  await page.waitForTimeout(50);
  const withReopen = await page.evaluate(() => window.__CG_APPLE_FIXTURE__.measure());
  await page.locator("#consentgate-reopen").screenshot({
    path: path.join(artifacts, "cookie-reopen-button.png"),
  });

  assert(withReopen.reopenExists, "Reopen button missing");
  const bg = parseRgb(withReopen.reopenBg);
  const fg = parseRgb(withReopen.reopenColor);
  assert(isLight(bg), `Reopen background should use banner bg (light), got ${withReopen.reopenBg}`);
  assert(isDark(fg), `Reopen icon color should contrast on banner bg, got ${withReopen.reopenColor}`);

  await page.evaluate(() => window.__CG_APPLE_FIXTURE__.consentMarketing());
  await page.waitForTimeout(400);

  const consented = await page.evaluate(() => window.__CG_APPLE_FIXTURE__.measure());
  await page.locator("section[aria-label='MapKit']").screenshot({
    path: path.join(artifacts, "apple-maps-after-consent.png"),
  });
  fs.writeFileSync(
    path.join(artifacts, "apple-maps-after-consent.json"),
    JSON.stringify(consented, null, 2)
  );

  assert(!consented.mapWrap, "MapKit placeholder should unwrap after consent");
  assert(consented.mapInitFlag, "MapKit inline init did not run after consent");
  assert(consented.mapReady, "mapkit.Map did not mark #map ready after consent");
  assert(!consented.youtubeWrap, "YouTube wrap should unwrap after marketing consent");
  assert(!consented.appleIframeWrap, "Apple iframe wrap should unwrap after consent");

  const summary = { blocked, withReopen, consented, ok: true };
  fs.writeFileSync(
    path.join(artifacts, "apple-maps-proof.json"),
    JSON.stringify(summary, null, 2)
  );
  console.log(JSON.stringify(summary, null, 2));
  console.log("PASS");
} finally {
  await browser.close();
}
