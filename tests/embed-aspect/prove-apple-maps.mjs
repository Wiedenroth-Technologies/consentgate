/**
 * Apple Maps MapKit + reopen contrast, with REAL inlined ConsentGate blocker.
 *
 * PLAYWRIGHT_MODULE=.../playwright node tests/embed-aspect/prove-apple-maps.mjs
 */
import { createRequire } from "node:module";
import path from "node:path";
import { fileURLToPath, pathToFileURL } from "node:url";
import fs from "node:fs";
import {
  wrapInlineScripts,
  productionWidgetRegistry,
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

const mapkitStub = pathToRel("tests/embed-aspect/mapkit-stub.js");

const html = wrapInlineScripts({
  configObject: {
    shopLocale: "de_DE",
    assetBase: pathToRel("assets/logos/") + "/",
    config: {
      languageMode: "de",
      locale: "de",
      position: "corner",
      colors: {
        primary: "#27335a",
        background: "#ffffff",
        text: "#1a1a1a",
        acceptBg: "#27335a",
        acceptText: "#ffffff",
        rejectBg: "#ffffff",
        rejectText: "#27335a",
      },
      showReopen: true,
      texts: { reopenLabel: "Cookie-Einstellungen" },
      ui: {
        de: {
          appleTitle: "Apple Maps ist blockiert",
          embedBody:
            "Erlauben Sie die passende Kategorie, um diesen Inhalt zu sehen.",
          openSettings: "Cookie-Einstellungen öffnen",
          youtubeTitle: "YouTube ist blockiert",
          youtubeBody: "Marketing-Cookies erlauben, um das Video zu sehen.",
        },
      },
      embedGates: productionEmbedGates,
      widgetRegistry: productionWidgetRegistry,
      blockExternalEmbeds: true,
      externalEmbedAllowlist: [],
      blockRules: [
        {
          id: "apple_maps",
          cat: "marketing",
          h: ["maps.apple.com", "snapshot.apple-mapkit.com", "cdn.apple-mapkit.com"],
          c: [],
        },
        {
          id: "youtube",
          cat: "marketing",
          h: ["youtube.com", "www.youtube.com", "youtube-nocookie.com", "youtu.be"],
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
body { margin: 0; padding: 24px; font-family: system-ui, sans-serif; background: #f6f7f7; }
.layout { display: grid; gap: 24px; max-width: 720px; }
#map { height: 400px; width: 100%; background: #dfe3e8; }
button { background-color: transparent; border: 0; color: inherit; }
.wp-block-embed { margin: 0; max-width: 640px; }
.wp-block-embed__wrapper { position: relative; }
.wp-embed-responsive .wp-has-aspect-ratio .wp-block-embed__wrapper::before {
  content: ""; display: block; padding-top: 56.25%;
}
.wp-embed-responsive .wp-has-aspect-ratio .wp-block-embed__wrapper iframe {
  position: absolute; inset: 0; width: 100%; height: 100%;
}
</style>`,
  bodyHtml: `
<div class="layout">
  <section aria-label="MapKit">
    <div id="map" style="height: 400px;"></div>
    <script type="text/plain" data-consent-gate="marketing" data-cg-blocked="marketing" data-cg-kind="apple_maps" data-cg-src="./${mapkitStub}"></script>
    <script type="text/plain" data-consent-gate="marketing" data-cg-blocked="marketing" data-cg-kind="apple_maps">
window.__CG_MAPKIT_INIT__ = true;
mapkit.init({ authorizationCallback: function (done) { done("test"); } });
var map = new mapkit.Map("map");
map.showItems([]);
    </script>
  </section>
  <section aria-label="YouTube regression">
    <figure class="wp-block-embed is-type-video is-provider-youtube wp-block-embed-youtube wp-embed-aspect-16-9 wp-has-aspect-ratio">
      <div class="wp-block-embed__wrapper">
        <iframe title="YouTube video" width="640" height="360" src="https://www.youtube.com/embed/dQw4w9WgXcQ" allowfullscreen></iframe>
      </div>
    </figure>
  </section>
  <section aria-label="Apple Maps iframe">
    <iframe id="apple-iframe" title="Apple Maps" width="600" height="320" src="https://maps.apple.com/embed?q=Hildesheim"></iframe>
  </section>
</div>
<script>
(function () {
  function measure() {
    var map = document.getElementById("map");
    var wrap = map && map.closest ? map.closest(".cg-embed-wrap") : null;
    var overlay = wrap ? wrap.querySelector(".cg-embed-overlay") : null;
    var yt = document.querySelector(".wp-block-embed iframe");
    var ytWrap = document.querySelector(".wp-block-embed .cg-embed-wrap");
    var appleIframe = document.getElementById("apple-iframe");
    var appleIframeWrap = appleIframe && appleIframe.closest ? appleIframe.closest(".cg-embed-wrap") : null;
    var reopen = document.getElementById("consentgate-reopen");
    var reopenStyle = reopen ? getComputedStyle(reopen) : null;
    var own = Array.prototype.map.call(document.querySelectorAll("script[id^='consentgate']"), function (s) {
      return { id: s.id, blocked: s.getAttribute("data-cg-blocked"), kind: s.getAttribute("data-cg-kind") };
    });
    return {
      ownStamped: own.some(function (s) { return Boolean(s.blocked || s.kind); }),
      mapHasWidget: Boolean(map && map.getAttribute("data-cg-widget") === "apple_maps"),
      mapWrap: Boolean(wrap),
      mapOverlayTitle: overlay && overlay.querySelector("strong") ? overlay.querySelector("strong").textContent : "",
      mapMinHeight: wrap ? wrap.style.minHeight : "",
      mapReady: Boolean(map && map.getAttribute("data-cg-map-ready") === "1"),
      mapInitFlag: Boolean(window.__CG_MAPKIT_INIT__),
      youtubeWrap: Boolean(ytWrap),
      youtubeSrc: yt ? (yt.getAttribute("src") || yt.getAttribute("data-cg-src") || "") : "",
      youtubeBlocked: yt ? yt.hasAttribute("data-cg-blocked") : false,
      appleIframeWrap: Boolean(appleIframeWrap),
      appleIframeOverlay: Boolean(appleIframeWrap && appleIframeWrap.querySelector(".cg-embed-overlay")),
      appleIframeSrc: appleIframe ? (appleIframe.getAttribute("src") || appleIframe.getAttribute("data-cg-src") || "") : "",
      appleIframeBlocked: appleIframe ? appleIframe.hasAttribute("data-cg-blocked") : false,
      cgWrapCount: document.querySelectorAll(".cg-embed-wrap").length,
      reopenBg: reopenStyle ? reopenStyle.backgroundColor : "",
      reopenColor: reopenStyle ? reopenStyle.color : "",
      reopenExists: Boolean(reopen)
    };
  }
  window.__CG_APPLE_FIXTURE__ = {
    measure: measure,
    consentMarketing: function () {
      window.__CONSENTGATE_BLOCKER__.release({ statistics: false, marketing: true, preferences: false });
    },
    showReopen: function () {
      if (document.getElementById("consentgate-reopen")) return;
      var btn = document.createElement("button");
      btn.id = "consentgate-reopen";
      btn.className = "cg-reopen cg-reopen--right";
      btn.type = "button";
      btn.setAttribute("aria-label", "Cookie-Einstellungen");
      btn.style.cssText = "--cg-bg:#ffffff;--cg-fg:#1a1a1a;--cg-primary:#27335a";
      btn.innerHTML = '<svg viewBox="0 0 24 24" width="24" height="24" aria-hidden="true"><path fill="currentColor" d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2z"/></svg>';
      document.body.appendChild(btn);
    }
  };
})();
</script>
`,
});

const fixturePath = path.join(__dirname, "_generated-apple-maps-live.html");
fs.writeFileSync(fixturePath, html);
fs.writeFileSync(path.join(artifacts, "apple-maps-live-inline.html"), html);

const browser = await chromium.launch();
const page = await browser.newPage({ viewport: { width: 900, height: 1400 } });
const aborted = [];
page.on("requestfailed", (req) => {
  const failure = req.failure();
  aborted.push({ url: req.url(), errorText: failure && failure.errorText });
});

try {
  await page.goto(pathToFileURL(fixturePath).href, { waitUntil: "domcontentloaded" });
  await page.waitForTimeout(200);
  await page.evaluate(() => window.__CG_APPLE_FIXTURE__.showReopen());
  await page.waitForTimeout(50);

  const blocked = await page.evaluate(() => window.__CG_APPLE_FIXTURE__.measure());
  fs.writeFileSync(path.join(artifacts, "apple-maps-blocked.json"), JSON.stringify(blocked, null, 2));
  await page.screenshot({ path: path.join(artifacts, "apple-maps-blocked.png"), fullPage: true });

  assert(!blocked.ownStamped, "Own ConsentGate scripts must not be stamped");
  assert(blocked.mapWrap, "MapKit placeholder missing");
  assert(blocked.mapOverlayTitle.includes("Apple Maps"), "Wrong overlay title");
  assert(blocked.youtubeWrap, "YouTube should be wrapped before consent");
  assert(blocked.appleIframeWrap, "Apple Maps iframe should be wrapped");
  assert(blocked.reopenExists, "Reopen button missing");
  assert(blocked.reopenBg === "rgb(255, 255, 255)", "Reopen bg wrong: " + blocked.reopenBg);
  assert(blocked.reopenColor === "rgb(26, 26, 26)", "Reopen color wrong: " + blocked.reopenColor);
  assert(!blocked.mapReady, "Map should not init before consent");

  aborted.length = 0;
  await page.evaluate(() => window.__CG_APPLE_FIXTURE__.consentMarketing());
  await page.waitForTimeout(600);

  const consented = await page.evaluate(() => window.__CG_APPLE_FIXTURE__.measure());
  const postConsentAborted = aborted.filter(
    (a) =>
      a.errorText === "net::ERR_ABORTED" &&
      /youtube\.com\/embed|maps\.apple\.com\/embed/i.test(a.url)
  );
  fs.writeFileSync(
    path.join(artifacts, "apple-maps-consented.json"),
    JSON.stringify({ consented, postConsentAborted, aborted }, null, 2)
  );
  await page.screenshot({ path: path.join(artifacts, "apple-maps-consented.png"), fullPage: true });

  assert(!consented.ownStamped, "Own scripts stamped after consent");
  assert(!consented.mapWrap, "MapKit wrap should unwrap");
  assert(consented.mapInitFlag, "MapKit inline should run");
  assert(consented.mapReady, "MapKit map should init");
  assert(!consented.youtubeWrap, "YouTube wrap should unwrap");
  assert(!consented.youtubeBlocked, "YouTube must not stay blocked");
  assert(consented.youtubeSrc.includes("youtube.com"), "YouTube src missing");
  assert(!consented.appleIframeWrap, "Apple iframe wrap should unwrap");
  assert(!consented.appleIframeBlocked, "Apple iframe must not stay blocked");
  assert(consented.appleIframeSrc.includes("maps.apple.com"), "Apple iframe src missing");
  assert(consented.cgWrapCount === 0, "No wraps should remain");
  assert(
    postConsentAborted.length === 0,
    "Embed requests re-aborted after consent: " + JSON.stringify(postConsentAborted)
  );

  console.log(JSON.stringify({ blocked, consented, postConsentAborted, ok: true }, null, 2));
  console.log("PASS");
} finally {
  await browser.close();
}
