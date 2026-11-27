/**
 * Proof for generic widget registry + unknown iframe catch-all.
 *
 * PLAYWRIGHT_MODULE=.../playwright node tests/embed-aspect/prove-widgets.mjs
 */
import { createRequire } from "node:module";
import path from "node:path";
import { fileURLToPath, pathToFileURL } from "node:url";
import fs from "node:fs";

const require = createRequire(import.meta.url);
const { chromium } = require(
  process.env.PLAYWRIGHT_MODULE || "/tmp/cg-pw/node_modules/playwright"
);

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const fixture = pathToFileURL(path.join(__dirname, "widgets-fixture.html")).href;
const artifacts = process.env.CG_ARTIFACTS || "/opt/cursor/artifacts";
fs.mkdirSync(artifacts, { recursive: true });

function assert(cond, msg) {
  if (!cond) throw new Error(msg);
}

const browser = await chromium.launch();
const page = await browser.newPage({ viewport: { width: 900, height: 1600 } });

try {
  await page.goto(fixture);
  await page.waitForTimeout(150);
  const blocked = await page.evaluate(() => window.__CG_WIDGET_FIXTURE__.measure());
  fs.writeFileSync(
    path.join(artifacts, "widgets-blocked.json"),
    JSON.stringify(blocked, null, 2)
  );
  await page.screenshot({
    path: path.join(artifacts, "widgets-blocked.png"),
    fullPage: true,
  });

  assert(blocked.mapWrap, "Apple Maps placeholder missing");
  assert(blocked.igWrap, "Instagram placeholder missing");
  assert(blocked.twWrap, "Twitter placeholder missing");
  assert(blocked.ttWrap, "TikTok placeholder missing");
  assert(blocked.ytWrap, "YouTube placeholder missing");
  assert(blocked.gmapsWrap, "Google Maps placeholder missing");
  assert(blocked.unknownWrap, "Unknown iframe placeholder missing");
  assert(blocked.unknownKind === "external_content", "Unknown kind wrong");
  assert(blocked.unknownHost === "weird-tracker.example", "Unknown host wrong");
  assert(blocked.unknownBlocked, "Unknown iframe should be blocked");
  assert(!blocked.allowedBlocked, "Allowlisted iframe must stay open");
  assert(!blocked.localBlocked, "Same-origin iframe must stay open");
  assert(!blocked.mapReady, "MapKit must stay deferred");

  await page.evaluate(() => window.__CG_WIDGET_FIXTURE__.consentMarketing());
  await page.waitForTimeout(500);
  const consented = await page.evaluate(() => window.__CG_WIDGET_FIXTURE__.measure());
  fs.writeFileSync(
    path.join(artifacts, "widgets-consented.json"),
    JSON.stringify(consented, null, 2)
  );
  await page.screenshot({
    path: path.join(artifacts, "widgets-consented.png"),
    fullPage: true,
  });

  assert(!consented.mapWrap, "MapKit wrap should unwrap");
  assert(consented.mapInit, "MapKit inline should run");
  assert(consented.mapReady, "MapKit map should init");
  assert(!consented.igWrap, "Instagram wrap should unwrap");
  assert(consented.igProcessed, "instgrm.Embeds.process should run");
  assert(!consented.twWrap, "Twitter wrap should unwrap");
  assert(consented.twLoaded, "twttr.widgets.load should run");
  assert(!consented.ttWrap, "TikTok wrap should unwrap");
  assert(consented.ttRendered, "tiktokEmbed render should run");
  assert(!consented.ytWrap, "YouTube wrap should unwrap");
  assert(!consented.gmapsWrap, "Google Maps wrap should unwrap");
  assert(!consented.unknownWrap, "Unknown wrap should unwrap");

  console.log(JSON.stringify({ blocked, consented, ok: true }, null, 2));
  console.log("PASS");
} finally {
  await browser.close();
}
