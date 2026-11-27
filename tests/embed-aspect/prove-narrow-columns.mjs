/**
 * Placeholder overlay fit tiers must match the painted UI (not only data-cg-fit).
 * Covers min-height wraps (raw iframes) and aspect-ratio Gutenberg wraps.
 *
 * PLAYWRIGHT_MODULE=.../playwright node tests/embed-aspect/prove-narrow-columns.mjs
 */
import { createRequire } from "node:module";
import path from "node:path";
import { fileURLToPath, pathToFileURL } from "node:url";
import fs from "node:fs";
import {
  wrapInlineScripts,
  productionEmbedGates,
  pathToRel,
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
    assetBase: pathToRel("assets/logos/") + "/",
    logos: {
      youtube: pathToRel("assets/logos/logo-placeholder.svg"),
      _placeholder: pathToRel("assets/logos/logo-placeholder.svg"),
    },
    config: {
      languageMode: "de",
      locale: "de",
      colors: {
        primary: "#27335a",
        background: "#ffffff",
        text: "#1a1a1a",
        acceptBg: "#27335a",
        acceptText: "#ffffff",
        rejectBg: "#ffffff",
        rejectText: "#27335a",
      },
      ui: {
        de: {
          youtubeTitle: "YouTube ist blockiert",
          youtubeBody: "Marketing-Cookies erlauben, um das Video zu sehen.",
          openSettings: "Cookie-Einstellungen öffnen",
          embedBody:
            "Erlauben Sie die passende Kategorie, um diesen Inhalt zu sehen.",
        },
      },
      embedGates: productionEmbedGates,
      widgetRegistry: [],
      blockExternalEmbeds: true,
      externalEmbedAllowlist: [],
      blockRules: [
        {
          id: "youtube",
          cat: "marketing",
          h: ["youtube.com", "youtube-nocookie.com"],
          c: [],
        },
      ],
      purposePolicies: {
        statistics: "with_consent",
        marketing: "with_consent",
        preferences: "with_consent",
      },
    },
  },
  extraHead: `<style>
body { margin: 0; padding: 16px; font-family: system-ui, sans-serif; background: #f6f7f7; }
.section { margin-bottom: 32px; }
.section h2 { font-size: 14px; margin: 0 0 12px; }
.row { display: flex; flex-direction: column; gap: 24px; align-items: flex-start; }
.desk-row { display: flex; flex-wrap: wrap; gap: 24px; align-items: flex-start; }
.col-562 { width: 562px; }
.col-369 { width: 369px; }
.col-177 { width: 177px; }
.col-113 { width: 113px; }
.col-173 { width: 173px; }
.wp-block-embed { margin: 0; }
.wp-block-embed__wrapper { position: relative; width: 100%; }
.wp-embed-responsive .wp-has-aspect-ratio .wp-block-embed__wrapper::before {
  content: ""; display: block; padding-top: 56.25%;
}
.wp-embed-responsive .wp-has-aspect-ratio .wp-block-embed__wrapper iframe {
  position: absolute; inset: 0; width: 100%; height: 100%;
}
.tall iframe { width: 100%; height: 315px; }
label { display: block; font-size: 12px; margin-bottom: 6px; color: #333; }
</style>`,
  bodyHtml: `
<div class="section" data-viewport="desktop">
  <h2>Desktop min-height wraps</h2>
  <div class="desk-row">
    <div class="col-562" data-case="desk-562">
      <label>min-height ~562×315 (roomy)</label>
      <div class="tall">
        <iframe title="YT desk B" width="560" height="315" src="https://www.youtube.com/embed/aqz-KE-bpKQ" data-cg-kind="youtube"></iframe>
      </div>
    </div>
    <div class="col-369" data-case="desk-369">
      <label>min-height ~369×315 (roomy)</label>
      <div class="tall">
        <iframe title="YT desk C" width="369" height="315" src="https://www.youtube.com/embed/dQw4w9WgXcQ" data-cg-kind="youtube"></iframe>
      </div>
    </div>
  </div>
</div>
<div class="section" data-viewport="mobile">
  <h2>Mobile / narrow</h2>
  <div class="row">
    <div class="col-177" data-case="flex-177">
      <label>flex ~177×315 (tight)</label>
      <div class="tall">
        <iframe title="YouTube A" width="177" height="315" src="https://www.youtube.com/embed/aqz-KE-bpKQ" data-cg-kind="youtube"></iframe>
      </div>
    </div>
    <div class="col-113" data-case="grid-113">
      <label>grid ~113×315 (minimal)</label>
      <div class="tall">
        <iframe title="YouTube B" width="113" height="315" src="https://www.youtube.com/embed/dQw4w9WgXcQ" data-cg-kind="youtube"></iframe>
      </div>
    </div>
    <div class="col-173" data-case="aspect-173">
      <label>16:9 ~173×97 (minimal)</label>
      <figure class="wp-block-embed is-type-video is-provider-youtube wp-block-embed-youtube wp-embed-aspect-16-9 wp-has-aspect-ratio">
        <div class="wp-block-embed__wrapper">
          <iframe title="YouTube C" width="173" height="97" src="https://www.youtube-nocookie.com/embed/aqz-KE-bpKQ" data-cg-kind="youtube"></iframe>
        </div>
      </figure>
    </div>
  </div>
</div>
<script>
window.__CG_NARROW_FIXTURE__ = {
  painted: function (el) {
    if (!el) return { display: "none", visible: false };
    var cs = window.getComputedStyle(el);
    var visible =
      cs.display !== "none" &&
      cs.visibility !== "hidden" &&
      Number(cs.opacity) > 0 &&
      el.getClientRects().length > 0;
    return { display: cs.display, visible: visible };
  },
  measureCase: function (name) {
    var host = document.querySelector('[data-case="' + name + '"]');
    if (host) host.scrollIntoView({ block: "center", inline: "nearest" });
    var wrap = host && host.querySelector(".cg-embed-wrap");
    var overlay = wrap && wrap.querySelector(".cg-embed-overlay");
    var logo = wrap && wrap.querySelector(".cg-embed-logo");
    var titleFull = wrap && wrap.querySelector(".cg-embed-title-full");
    var titleShort = wrap && wrap.querySelector(".cg-embed-title-short");
    var body = wrap && wrap.querySelector(".cg-embed-body");
    var btn = wrap && wrap.querySelector("button.cg-embed-open");
    var label = btn && btn.querySelector(".cg-embed-open-label");
    var icon = btn && btn.querySelector(".cg-embed-open-icon");
    if (!wrap || !btn) {
      return { ok: false, reason: "missing wrap/button", fit: "", wrap: null, btn: null };
    }
    var wr = wrap.getBoundingClientRect();
    var br = btn.getBoundingClientRect();
    var inside =
      br.left >= wr.left - 0.5 &&
      br.right <= wr.right + 0.5 &&
      br.top >= wr.top - 0.5 &&
      br.bottom <= wr.bottom + 0.5;
    var cx = br.left + br.width / 2;
    var cy = br.top + br.height / 2;
    var topEl = document.elementFromPoint(cx, cy);
    var hit = Boolean(topEl && (topEl === btn || btn.contains(topEl)));
    var ui = {
      logo: this.painted(logo),
      titleFull: this.painted(titleFull),
      titleShort: this.painted(titleShort),
      body: this.painted(body),
      label: this.painted(label),
      icon: this.painted(icon)
    };
    return {
      inside: inside,
      hit: hit,
      fit: wrap.getAttribute("data-cg-fit") || "",
      aria: btn.getAttribute("aria-label") || "",
      wrap: { w: Math.round(wr.width), h: Math.round(wr.height) },
      btn: { w: Math.round(br.width), h: Math.round(br.height) },
      ui: ui
    };
  }
};
</script>
`,
});

const fixturePath = path.join(__dirname, "_generated-narrow-columns.html");
fs.writeFileSync(fixturePath, html);
fs.writeFileSync(path.join(artifacts, "narrow-columns.html"), html);

function expectRoomy(m, label) {
  assert(m.fit === "roomy", label + " fit: " + JSON.stringify(m));
  assert(m.ui.logo.visible, label + " logo should be visible: " + JSON.stringify(m.ui));
  assert(m.ui.titleFull.visible, label + " full title should be visible: " + JSON.stringify(m.ui));
  assert(!m.ui.titleShort.visible, label + " short title should be hidden: " + JSON.stringify(m.ui));
  assert(m.ui.body.visible, label + " body should be visible: " + JSON.stringify(m.ui));
  assert(m.ui.label.visible, label + " button label should be visible: " + JSON.stringify(m.ui));
  assert(!m.ui.icon.visible, label + " icon-only should NOT paint: " + JSON.stringify(m.ui));
  assert(m.inside && m.hit, label + " button clipped/unusable: " + JSON.stringify(m));
}

function expectTight(m, label) {
  assert(m.fit === "tight", label + " fit: " + JSON.stringify(m));
  assert(m.ui.logo.visible, label + " logo should be visible: " + JSON.stringify(m.ui));
  assert(!m.ui.titleFull.visible, label + " full title hidden: " + JSON.stringify(m.ui));
  assert(m.ui.titleShort.visible, label + " short title visible: " + JSON.stringify(m.ui));
  assert(!m.ui.body.visible, label + " body hidden: " + JSON.stringify(m.ui));
  assert(m.ui.label.visible, label + " text button label visible: " + JSON.stringify(m.ui));
  assert(!m.ui.icon.visible, label + " icon should stay hidden: " + JSON.stringify(m.ui));
  assert(m.inside && m.hit, label + " button clipped/unusable: " + JSON.stringify(m));
}

function expectMinimal(m, label) {
  assert(m.fit === "minimal", label + " fit: " + JSON.stringify(m));
  assert(!m.ui.logo.visible, label + " logo hidden: " + JSON.stringify(m.ui));
  assert(!m.ui.titleFull.visible && !m.ui.titleShort.visible, label + " titles hidden: " + JSON.stringify(m.ui));
  assert(!m.ui.body.visible, label + " body hidden: " + JSON.stringify(m.ui));
  assert(!m.ui.label.visible, label + " label hidden: " + JSON.stringify(m.ui));
  assert(m.ui.icon.visible, label + " icon should paint: " + JSON.stringify(m.ui));
  assert(m.inside && m.hit && m.btn.w >= 36 && m.btn.h >= 36, label + " icon button unusable: " + JSON.stringify(m));
}

async function measureAll(page, keys) {
  const measured = {};
  for (const [key, name] of keys) {
    await page.locator(`[data-case="${name}"]`).scrollIntoViewIfNeeded();
    await page.waitForTimeout(80);
    measured[key] = await page.evaluate(
      (caseName) => window.__CG_NARROW_FIXTURE__.measureCase(caseName),
      name
    );
  }
  return measured;
}

const browser = await chromium.launch();
try {
  // Desktop: min-height wraps must paint roomy (regression for container-type:size bug).
  const desk = await browser.newPage({ viewport: { width: 1280, height: 900 } });
  await desk.goto(pathToFileURL(fixturePath).href, { waitUntil: "domcontentloaded" });
  await desk.waitForTimeout(250);
  await desk.waitForFunction(() => {
    return document.querySelectorAll(".cg-embed-wrap[data-cg-fit]").length >= 5;
  }, null, { timeout: 3000 }).catch(() => {});

  const desktop = await measureAll(desk, [
    ["desk562", "desk-562"],
    ["desk369", "desk-369"],
  ]);
  await desk.screenshot({
    path: path.join(artifacts, "narrow-columns-desktop.png"),
    fullPage: true,
  });
  await desk.close();

  expectRoomy(desktop.desk562, "desktop 562×315");
  expectRoomy(desktop.desk369, "desktop 369×315");
  assert(desktop.desk562.wrap.w >= 500 && desktop.desk562.wrap.h >= 280, "desk562 size: " + JSON.stringify(desktop.desk562.wrap));
  assert(desktop.desk369.wrap.w >= 300 && desktop.desk369.wrap.h >= 280, "desk369 size: " + JSON.stringify(desktop.desk369.wrap));

  // Mobile / narrow columns.
  const page = await browser.newPage({ viewport: { width: 390, height: 844 } });
  await page.goto(pathToFileURL(fixturePath).href, { waitUntil: "domcontentloaded" });
  await page.waitForTimeout(250);
  await page.waitForFunction(() => {
    return document.querySelectorAll(".cg-embed-wrap[data-cg-fit]").length >= 5;
  }, null, { timeout: 3000 }).catch(() => {});

  const mobile = await measureAll(page, [
    ["flex177", "flex-177"],
    ["grid113", "grid-113"],
    ["aspect173", "aspect-173"],
  ]);
  await page.screenshot({
    path: path.join(artifacts, "narrow-columns.png"),
    fullPage: true,
  });
  await page.close();

  expectTight(mobile.flex177, "177×315");
  expectMinimal(mobile.grid113, "113×315");
  expectMinimal(mobile.aspect173, "173×97");
  assert(/YouTube/i.test(mobile.flex177.aria), "aria-label should include service name");

  const measured = { desktop, mobile };
  fs.writeFileSync(
    path.join(artifacts, "narrow-columns.json"),
    JSON.stringify(measured, null, 2)
  );

  console.log(JSON.stringify({ measured, ok: true }, null, 2));
  console.log("PASS");
} finally {
  await browser.close();
}
