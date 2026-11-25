/**
 * Local proof for YouTube embed placeholder whitespace.
 *
 * Requires Playwright (dev): npm i playwright && npx playwright install chromium
 * Run from repo root: node --experimental-vm-modules tests/embed-aspect/prove.mjs
 * Or: NODE_PATH=…/node_modules node tests/embed-aspect/prove.mjs (CJS envs).
 */
import { createRequire } from "node:module";
import path from "node:path";
import { fileURLToPath, pathToFileURL } from "node:url";
import fs from "node:fs";

const require = createRequire(import.meta.url);
const { chromium } = require(
  process.env.PLAYWRIGHT_MODULE ||
    "/tmp/node_modules/playwright"
);

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const fixture = pathToFileURL(path.join(__dirname, "fixture.html")).href;
const artifacts = process.env.CG_ARTIFACTS || "/opt/cursor/artifacts";
fs.mkdirSync(artifacts, { recursive: true });

const REVERT_FIX = `
.wp-embed-responsive .wp-block-embed__wrapper:has(> .cg-embed-wrap)::before,
.wp-embed-responsive .wp-block-embed__wrapper.cg-embed-host::before {
  content: "" !important;
  display: block !important;
  padding-top: 56.25% !important;
}
.wp-embed-responsive .wp-has-aspect-ratio .cg-embed-wrap {
  min-height: 280px !important;
  aspect-ratio: auto !important;
  height: auto !important;
}
`;

function assert(cond, msg) {
  if (!cond) {
    throw new Error(msg);
  }
}

function near(actual, expected, tol, label) {
  assert(
    Math.abs(actual - expected) <= tol,
    `${label}: expected ~${expected} (±${tol}), got ${actual}`
  );
}

async function measure(page) {
  return page.evaluate(() => window.__CG_FIXTURE__.measure());
}

const browser = await chromium.launch();
const page = await browser.newPage({ viewport: { width: 800, height: 900 } });

try {
  // --- BEFORE (buggy Core spacer still active) ---
  await page.goto(fixture);
  await page.addStyleTag({ content: REVERT_FIX });
  await page.waitForTimeout(50);
  const before = await measure(page);
  await page.locator(".wp-block-embed").screenshot({
    path: path.join(artifacts, "youtube-embed-before.png"),
  });
  fs.writeFileSync(
    path.join(artifacts, "youtube-embed-before.json"),
    JSON.stringify(before, null, 2)
  );

  // --- AFTER (fixed CSS) ---
  await page.goto(fixture);
  await page.waitForTimeout(50);
  const blocked = await measure(page);
  await page.locator(".wp-block-embed").screenshot({
    path: path.join(artifacts, "youtube-embed-after-blocked.png"),
  });

  assert(blocked.hasWrap, "Placeholder wrap missing");
  assert(blocked.hasHostClass, "Fallback class cg-embed-host missing");
  near(
    blocked.placeholderTop - blocked.hostTop,
    0,
    2,
    "Placeholder top gap"
  );
  near(
    blocked.hostHeight - blocked.placeholderHeight,
    0,
    3,
    "Host vs placeholder height"
  );
  // At max-width 640, height for 16:9 should be 360.
  near(blocked.placeholderHeight, 360, 4, "Blocked 16:9 placeholder height");
  // Bug would be ~360 (spacer) + ~360 (wrap) ≈ 720.
  assert(
    blocked.hostHeight < 400,
    `Host still too tall (${blocked.hostHeight}); Core spacer likely still active`
  );
  assert(
    before.hostHeight > blocked.hostHeight * 1.5,
    `Before height ${before.hostHeight} should be much larger than after ${blocked.hostHeight}`
  );

  // --- AFTER CONSENT ---
  await page.evaluate(() => window.__CG_FIXTURE__.consent());
  await page.waitForTimeout(50);
  const consented = await measure(page);
  await page.locator(".wp-block-embed").screenshot({
    path: path.join(artifacts, "youtube-embed-after-consent.png"),
  });

  assert(!consented.hasWrap, "Wrap should be removed after consent");
  assert(!consented.hasHostClass, "cg-embed-host should be removed after consent");
  assert(consented.iframeVisible, "iframe src not restored");
  near(consented.iframeHeight, 360, 8, "Consented iframe height");
  near(consented.hostHeight, 360, 8, "Consented host height");
  near(consented.aspect, 16 / 9, 0.05, "Consented iframe aspect");

  const summary = { before, blocked, consented, ok: true };
  fs.writeFileSync(
    path.join(artifacts, "youtube-embed-proof.json"),
    JSON.stringify(summary, null, 2)
  );
  console.log(JSON.stringify(summary, null, 2));
  console.log("PASS");
} finally {
  await browser.close();
}
