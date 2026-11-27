/**
 * Page with no third-party widgets: ConsentGate own scripts must never be stamped.
 *
 * PLAYWRIGHT_MODULE=.../playwright node tests/embed-aspect/prove-self-scripts.mjs
 */
import { createRequire } from "node:module";
import path from "node:path";
import { fileURLToPath, pathToFileURL } from "node:url";
import fs from "node:fs";
import {
  wrapInlineScripts,
  productionWidgetRegistry,
  productionEmbedGates,
} from "./build-fixture.mjs";

const require = createRequire(import.meta.url);
const { chromium } = require(
  process.env.PLAYWRIGHT_MODULE || "/tmp/cg-pw/node_modules/playwright"
);

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const artifacts = process.env.CG_ARTIFACTS || "/opt/cursor/artifacts";
fs.mkdirSync(artifacts, { recursive: true });

function assert(cond, msg) {
  if (!cond) throw new Error(msg);
}

const html = wrapInlineScripts({
  configObject: {
    shop: "example.com",
    shopLocale: "de_DE",
    config: {
      languageMode: "de",
      locale: "de",
      embedGates: productionEmbedGates,
      widgetRegistry: productionWidgetRegistry,
      blockExternalEmbeds: true,
      externalEmbedAllowlist: [],
      blockRules: [
        { id: "apple_maps", cat: "marketing", h: ["apple-mapkit.com"], c: [] },
        { id: "calendly", cat: "marketing", h: ["calendly.com"], c: [] },
      ],
      purposePolicies: {
        statistics: "with_consent",
        marketing: "with_consent",
        preferences: "with_consent",
      },
      ui: { de: { openSettings: "Cookie-Einstellungen öffnen" } },
    },
  },
  bodyHtml: `
<main><p>No embeds on this page.</p></main>
<script>
window.__CG_SELF_FIXTURE__ = {
  measure: function () {
    var own = Array.prototype.map.call(document.querySelectorAll("script[id^='consentgate']"), function (s) {
      return {
        id: s.id,
        blocked: s.getAttribute("data-cg-blocked"),
        kind: s.getAttribute("data-cg-kind"),
        type: (s.getAttribute("type") || "").toLowerCase()
      };
    });
    return {
      own: own,
      stamped: own.filter(function (s) { return Boolean(s.blocked || s.kind); }),
      wraps: document.querySelectorAll(".cg-embed-wrap").length,
      blockerAlive: Boolean(window.__CONSENTGATE_BLOCKER__ && window.__CONSENTGATE_BLOCKER__.release)
    };
  },
  consent: function () {
    window.__CONSENTGATE_BLOCKER__.release({ statistics: true, marketing: true, preferences: true });
  }
};
</script>
`,
});

const fixturePath = path.join(__dirname, "_generated-self-scripts.html");
fs.writeFileSync(fixturePath, html);
fs.writeFileSync(path.join(artifacts, "self-scripts-fixture.html"), html);

const browser = await chromium.launch();
const page = await browser.newPage();
try {
  await page.goto(pathToFileURL(fixturePath).href, { waitUntil: "domcontentloaded" });
  await page.waitForTimeout(200);
  const before = await page.evaluate(() => window.__CG_SELF_FIXTURE__.measure());
  assert(before.blockerAlive, "Blocker API missing");
  assert(before.own.length >= 3, "Expected boot/config/blocker script ids");
  assert(before.stamped.length === 0, "Own scripts stamped before consent: " + JSON.stringify(before.stamped));
  assert(before.wraps === 0, "Unexpected wraps on empty page");

  await page.evaluate(() => window.__CG_SELF_FIXTURE__.consent());
  await page.waitForTimeout(300);
  const after = await page.evaluate(() => window.__CG_SELF_FIXTURE__.measure());
  assert(after.blockerAlive, "Blocker API lost after consent");
  assert(after.stamped.length === 0, "Own scripts stamped after consent: " + JSON.stringify(after.stamped));
  assert(after.wraps === 0, "Unexpected wraps after consent");

  fs.writeFileSync(
    path.join(artifacts, "self-scripts.json"),
    JSON.stringify({ before, after, ok: true }, null, 2)
  );
  console.log(JSON.stringify({ before, after, ok: true }, null, 2));
  console.log("PASS");
} finally {
  await browser.close();
}
