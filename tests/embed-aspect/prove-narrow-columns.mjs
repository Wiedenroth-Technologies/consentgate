/**
 * Narrow-column placeholder overlay must keep the consent button fully inside
 * the wrap and clickable (mobile side-by-side video columns).
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
.row { display: flex; flex-direction: column; gap: 24px; align-items: flex-start; }
.col-170 { width: 170px; }
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
<div class="row">
  <div class="col-170" data-case="flex-170">
    <label>flex ~170×315</label>
    <div class="tall">
      <iframe title="YouTube A" width="170" height="315" src="https://www.youtube.com/embed/aqz-KE-bpKQ" data-cg-kind="youtube"></iframe>
    </div>
  </div>
  <div class="col-113" data-case="grid-113">
    <label>grid ~113×315</label>
    <div class="tall">
      <iframe title="YouTube B" width="113" height="315" src="https://www.youtube.com/embed/dQw4w9WgXcQ" data-cg-kind="youtube"></iframe>
    </div>
  </div>
  <div class="col-173" data-case="aspect-173">
    <label>16:9 ~173×97</label>
    <figure class="wp-block-embed is-type-video is-provider-youtube wp-block-embed-youtube wp-embed-aspect-16-9 wp-has-aspect-ratio">
      <div class="wp-block-embed__wrapper">
        <iframe title="YouTube C" width="173" height="97" src="https://www.youtube-nocookie.com/embed/aqz-KE-bpKQ" data-cg-kind="youtube"></iframe>
      </div>
    </figure>
  </div>
</div>
<script>
window.__CG_NARROW_FIXTURE__ = {
  measureCase: function (name) {
    var host = document.querySelector('[data-case="' + name + '"]');
    if (host) host.scrollIntoView({ block: "center", inline: "nearest" });
    var wrap = host && host.querySelector(".cg-embed-wrap");
    var btn = wrap && wrap.querySelector("button.cg-embed-open");
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
    return {
      ok: inside && hit && br.width >= 36 && br.height >= 36,
      inside: inside,
      hit: hit,
      fit: wrap.getAttribute("data-cg-fit") || "",
      aria: btn.getAttribute("aria-label") || "",
      topTag: topEl ? topEl.tagName + (topEl.className ? "." + String(topEl.className).split(" ").join(".") : "") : "null",
      wrap: { w: Math.round(wr.width), h: Math.round(wr.height) },
      btn: { w: Math.round(br.width), h: Math.round(br.height), left: Math.round(br.left), top: Math.round(br.top) }
    };
  }
};
</script>
`,
});

const fixturePath = path.join(__dirname, "_generated-narrow-columns.html");
fs.writeFileSync(fixturePath, html);
fs.writeFileSync(path.join(artifacts, "narrow-columns.html"), html);

const browser = await chromium.launch();
const page = await browser.newPage({ viewport: { width: 390, height: 844 } });
try {
  await page.goto(pathToFileURL(fixturePath).href, { waitUntil: "domcontentloaded" });
  await page.waitForTimeout(250);
  await page.waitForFunction(() => {
    const wraps = document.querySelectorAll(".cg-embed-wrap[data-cg-fit]");
    return wraps.length >= 3;
  }, null, { timeout: 3000 }).catch(() => {});

  const measured = {};
  for (const [key, name] of [
    ["flex170", "flex-170"],
    ["grid113", "grid-113"],
    ["aspect173", "aspect-173"],
  ]) {
    await page.locator(`[data-case="${name}"]`).scrollIntoViewIfNeeded();
    await page.waitForTimeout(80);
    measured[key] = await page.evaluate(
      (caseName) => window.__CG_NARROW_FIXTURE__.measureCase(caseName),
      name
    );
  }
  fs.writeFileSync(
    path.join(artifacts, "narrow-columns.json"),
    JSON.stringify(measured, null, 2)
  );
  await page.screenshot({
    path: path.join(artifacts, "narrow-columns.png"),
    fullPage: true,
  });

  assert(measured.flex170.ok, "170px column button clipped/unusable: " + JSON.stringify(measured.flex170));
  assert(measured.grid113.ok, "113px column button clipped/unusable: " + JSON.stringify(measured.grid113));
  assert(measured.aspect173.ok, "16:9 173x97 button clipped/unusable: " + JSON.stringify(measured.aspect173));
  assert(/YouTube/i.test(measured.flex170.aria), "aria-label should include service name");
  assert(
    measured.aspect173.fit === "minimal" || measured.aspect173.btn.h <= 48,
    "short 16:9 box should use compact/minimal control"
  );

  console.log(JSON.stringify({ measured, ok: true }, null, 2));
  console.log("PASS");
} finally {
  await browser.close();
}
