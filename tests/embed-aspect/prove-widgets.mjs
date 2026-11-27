/**
 * Proof for widget registry + unknown iframes using REAL inlined ConsentGate scripts.
 *
 * PLAYWRIGHT_MODULE=.../playwright node tests/embed-aspect/prove-widgets.mjs
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
const igStub = pathToRel("tests/embed-aspect/stubs/instagram-embed.js");
const twStub = pathToRel("tests/embed-aspect/stubs/twitter-widgets.js");
const ttStub = pathToRel("tests/embed-aspect/stubs/tiktok-embed.js");

const html = wrapInlineScripts({
  configObject: {
    shop: "example.com",
    shopLocale: "de_DE",
    assetBase: pathToRel("assets/logos/") + "/",
    logos: { _placeholder: pathToRel("assets/logos/logo-placeholder.svg") },
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
      ui: {
        de: {
          appleTitle: "Apple Maps ist blockiert",
          instagramTitle: "Instagram ist blockiert",
          twitterTitle: "X (Twitter) ist blockiert",
          tiktokTitle: "TikTok ist blockiert",
          youtubeTitle: "YouTube ist blockiert",
          youtubeBody: "Marketing-Cookies erlauben, um das Video zu sehen.",
          externalTitle: "Externer Inhalt ist blockiert",
          externalBody:
            "Erlauben Sie Marketing-Cookies, um Inhalte von %s zu sehen.",
          embedBody:
            "Erlauben Sie die passende Kategorie, um diesen Inhalt zu sehen.",
          openSettings: "Cookie-Einstellungen öffnen",
          mapsTitle: "Google Maps ist blockiert",
          mapsBody: "Marketing-Cookies erlauben, um die Karte zu sehen.",
        },
      },
      embedGates: productionEmbedGates,
      widgetRegistry: productionWidgetRegistry,
      blockExternalEmbeds: true,
      externalEmbedAllowlist: ["allowed.example", "*.cdn-partner.test"],
      blockRules: [
        { id: "apple_maps", cat: "marketing", h: ["apple-mapkit.com"], c: [] },
        { id: "instagram", cat: "marketing", h: ["instagram.com/embed.js"], c: [] },
        { id: "twitter", cat: "marketing", h: ["platform.twitter.com"], c: [] },
        { id: "tiktok", cat: "marketing", h: ["tiktok.com/embed.js"], c: [] },
        {
          id: "youtube",
          cat: "marketing",
          h: ["youtube.com", "youtube-nocookie.com"],
          c: [],
        },
        {
          id: "google_maps",
          cat: "marketing",
          h: ["maps.googleapis.com", "maps.google.com"],
          c: [],
        },
        { id: "spotify", cat: "marketing", h: ["open.spotify.com"], c: [] },
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
blockquote { margin: 0; min-height: 120px; background: #fff; padding: 12px; }
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
  <section aria-label="Instagram">
    <blockquote class="instagram-media" data-instgrm-permalink="https://www.instagram.com/p/demo/">IG</blockquote>
    <script type="text/plain" data-consent-gate="marketing" data-cg-blocked="marketing" data-cg-kind="instagram" data-cg-src="./${igStub}"></script>
  </section>
  <section aria-label="Twitter">
    <blockquote class="twitter-tweet">Tweet</blockquote>
    <script type="text/plain" data-consent-gate="marketing" data-cg-blocked="marketing" data-cg-kind="twitter" data-cg-src="./${twStub}"></script>
  </section>
  <section aria-label="TikTok">
    <blockquote class="tiktok-embed">TT</blockquote>
    <script type="text/plain" data-consent-gate="marketing" data-cg-blocked="marketing" data-cg-kind="tiktok" data-cg-src="./${ttStub}"></script>
  </section>
  <section aria-label="YouTube">
    <figure class="wp-block-embed is-type-video is-provider-youtube wp-block-embed-youtube wp-embed-aspect-16-9 wp-has-aspect-ratio">
      <div class="wp-block-embed__wrapper">
        <iframe title="YouTube video" width="640" height="360" src="https://www.youtube.com/embed/dQw4w9WgXcQ"></iframe>
      </div>
    </figure>
  </section>
  <section aria-label="Spotify">
    <iframe id="spotify" title="Spotify" width="600" height="152" src="https://open.spotify.com/embed/track/demo"></iframe>
  </section>
  <section aria-label="Google Maps">
    <iframe id="gmaps" title="Google Maps" width="600" height="300" src="https://www.google.com/maps/embed?pb=demo"></iframe>
  </section>
  <section aria-label="Unknown iframe">
    <iframe id="unknown" title="Unknown" width="600" height="240" src="https://weird-tracker.example/embed/player"></iframe>
  </section>
  <section aria-label="Allowlisted iframe">
    <iframe id="allowed" title="Allowed" width="600" height="240" src="https://allowed.example/player"></iframe>
  </section>
  <section aria-label="Wildcard allowlist">
    <iframe id="wild" title="Wild" width="600" height="120" src="https://media.cdn-partner.test/player"></iframe>
  </section>
  <section aria-label="Same origin">
    <iframe id="local" title="Local" width="600" height="120" src="https://www.example.com/oembed/post"></iframe>
  </section>
</div>
<script>
(function () {
  function wrapFor(sel) {
    var el = document.querySelector(sel);
    if (!el) return null;
    return el.closest ? el.closest(".cg-embed-wrap") : null;
  }
  function measure() {
    var unknown = document.getElementById("unknown");
    var allowed = document.getElementById("allowed");
    var local = document.getElementById("local");
    var wild = document.getElementById("wild");
    var yt = document.querySelector('.wp-block-embed iframe');
    var spotify = document.getElementById("spotify");
    var igFrame = document.querySelector("iframe.instagram-media-iframe, blockquote.instagram-media iframe");
    var twHelper = document.getElementById("twitter-widget-0");
    var own = Array.prototype.map.call(document.querySelectorAll("script[id^='consentgate']"), function (s) {
      return {
        id: s.id,
        blocked: s.getAttribute("data-cg-blocked"),
        kind: s.getAttribute("data-cg-kind"),
        type: s.getAttribute("type")
      };
    });
    return {
      ownScripts: own,
      ownStamped: own.some(function (s) { return Boolean(s.blocked || s.kind); }),
      mapWrap: Boolean(wrapFor("#map")),
      mapReady: document.getElementById("map") && document.getElementById("map").getAttribute("data-cg-map-ready") === "1",
      mapInit: Boolean(window.__CG_MAPKIT_INIT__),
      mapInlineType: (function () {
        var scripts = document.querySelectorAll("script");
        for (var i = 0; i < scripts.length; i++) {
          var t = scripts[i].textContent || "";
          if (/mapkit\\.Map\\s*\\(\\s*[\"']map[\"']/.test(t) || /new mapkit\\.Map\\(\"map\"\\)/.test(t)) {
            return scripts[i].getAttribute("type") || "text/javascript";
          }
        }
        return "";
      })(),
      igWrap: Boolean(wrapFor("blockquote.instagram-media")),
      twWrap: Boolean(wrapFor("blockquote.twitter-tweet")),
      ttWrap: Boolean(wrapFor("blockquote.tiktok-embed")),
      ytWrap: Boolean(document.querySelector(".wp-block-embed .cg-embed-wrap")),
      ytSrc: yt ? (yt.getAttribute("src") || yt.getAttribute("data-cg-src") || "") : "",
      ytBlocked: yt ? yt.hasAttribute("data-cg-blocked") : false,
      spotifyWrap: Boolean(wrapFor("#spotify")),
      spotifySrc: spotify ? (spotify.getAttribute("src") || spotify.getAttribute("data-cg-src") || "") : "",
      spotifyBlocked: spotify ? spotify.hasAttribute("data-cg-blocked") : false,
      gmapsWrap: Boolean(wrapFor("#gmaps")),
      unknownWrap: Boolean(wrapFor("#unknown")),
      unknownKind: unknown ? unknown.getAttribute("data-cg-kind") : "",
      unknownHost: unknown ? unknown.getAttribute("data-cg-host") : "",
      unknownBlocked: unknown ? unknown.hasAttribute("data-cg-blocked") : false,
      unknownSrc: unknown ? (unknown.getAttribute("src") || unknown.getAttribute("data-cg-src") || "") : "",
      allowedBlocked: allowed ? allowed.hasAttribute("data-cg-blocked") : false,
      wildBlocked: wild ? wild.hasAttribute("data-cg-blocked") : false,
      localBlocked: local ? local.hasAttribute("data-cg-blocked") : false,
      igProcessed: Boolean(window.__CG_IG_PROCESSED__),
      igFrameSrc: igFrame ? (igFrame.getAttribute("src") || "") : "",
      igFrameBlocked: igFrame ? igFrame.hasAttribute("data-cg-blocked") : false,
      igFrameWrap: Boolean(igFrame && igFrame.closest && igFrame.closest(".cg-embed-wrap")),
      twLoaded: Boolean(window.__CG_TW_LOADED__),
      twHelperBlocked: twHelper ? twHelper.hasAttribute("data-cg-blocked") : false,
      twHelperWrap: Boolean(twHelper && twHelper.closest && twHelper.closest(".cg-embed-wrap")),
      cgWrapCount: document.querySelectorAll(".cg-embed-wrap").length,
      ttRendered: Boolean(window.__CG_TT_RENDERED__)
    };
  }
  window.__CG_WIDGET_FIXTURE__ = {
    measure: measure,
    consentMarketing: function () {
      window.__CONSENTGATE_BLOCKER__.release({ statistics: false, marketing: true, preferences: false });
    }
  };
})();
</script>
`,
});

const fixturePath = path.join(__dirname, "_generated-widgets-live.html");
fs.writeFileSync(fixturePath, html);
fs.writeFileSync(path.join(artifacts, "widgets-live-inline.html"), html);

const browser = await chromium.launch();
const page = await browser.newPage({ viewport: { width: 900, height: 1800 } });
const aborted = [];
page.on("requestfailed", (req) => {
  const failure = req.failure();
  aborted.push({
    url: req.url(),
    errorText: failure && failure.errorText,
  });
});

try {
  await page.goto(pathToFileURL(fixturePath).href, { waitUntil: "domcontentloaded" });
  await page.waitForTimeout(250);

  const blocked = await page.evaluate(() => window.__CG_WIDGET_FIXTURE__.measure());
  fs.writeFileSync(
    path.join(artifacts, "widgets-blocked.json"),
    JSON.stringify(blocked, null, 2)
  );
  await page.screenshot({
    path: path.join(artifacts, "widgets-blocked.png"),
    fullPage: true,
  });

  assert(!blocked.ownStamped, "ConsentGate own scripts must not be stamped as widgets");
  assert(blocked.mapWrap, "Apple Maps placeholder missing");
  assert(blocked.igWrap, "Instagram placeholder missing");
  assert(blocked.twWrap, "Twitter placeholder missing");
  assert(blocked.ttWrap, "TikTok placeholder missing");
  assert(blocked.ytWrap, "YouTube placeholder missing");
  assert(blocked.spotifyWrap, "Spotify placeholder missing");
  assert(blocked.gmapsWrap, "Google Maps placeholder missing");
  assert(blocked.unknownWrap, "Unknown iframe placeholder missing");
  assert(blocked.unknownKind === "external_content", "Unknown kind wrong");
  assert(blocked.unknownHost === "weird-tracker.example", "Unknown host wrong");
  assert(blocked.unknownBlocked, "Unknown iframe should be blocked");
  assert(!blocked.allowedBlocked, "Allowlisted iframe must stay open");
  assert(!blocked.wildBlocked, "Wildcard allowlisted iframe must stay open");
  assert(!blocked.localBlocked, "Same-origin iframe must stay open");
  assert(!blocked.mapReady, "MapKit must stay deferred");

  aborted.length = 0;
  await page.evaluate(() => window.__CG_WIDGET_FIXTURE__.consentMarketing());
  await page.waitForTimeout(800);

  const consented = await page.evaluate(() => window.__CG_WIDGET_FIXTURE__.measure());
  const postConsentAborted = aborted.filter(
    (a) =>
      a.errorText === "net::ERR_ABORTED" &&
      /\/embed|weird-tracker\.example|platform\.twitter\.com|instagram\.com\/p\//i.test(a.url) &&
      !/\/api\/stats|qoe\?/i.test(a.url)
  );
  fs.writeFileSync(
    path.join(artifacts, "widgets-consented.json"),
    JSON.stringify({ consented, postConsentAborted, aborted }, null, 2)
  );
  await page.screenshot({
    path: path.join(artifacts, "widgets-consented.png"),
    fullPage: true,
  });

  assert(!consented.ownStamped, "Own scripts must stay unstamped after consent");
  assert(!consented.mapWrap, "MapKit wrap should unwrap");
  assert(consented.mapInit, "MapKit inline should run");
  assert(consented.mapReady, "MapKit map should init");
  assert(
    !consented.mapInlineType || consented.mapInlineType.indexOf("plain") === -1,
    "MapKit inline must not stay text/plain"
  );
  assert(!consented.igWrap, "Instagram wrap should unwrap");
  assert(consented.igProcessed, "instgrm.Embeds.process should run");
  assert(consented.igFrameSrc.includes("/embed"), "Instagram iframe should be inserted");
  assert(!consented.igFrameBlocked, "Instagram iframe must not be re-blocked");
  assert(!consented.igFrameWrap, "Instagram iframe must not get a new wrap");
  assert(!consented.twWrap, "Twitter wrap should unwrap");
  assert(consented.twLoaded, "twttr.widgets.load should run");
  assert(!consented.twHelperBlocked, "Twitter helper iframe must not be blocked");
  assert(!consented.twHelperWrap, "Twitter helper iframe must not be wrapped");
  assert(consented.cgWrapCount === 0, "Exactly zero ConsentGate wraps after consent");
  assert(!consented.ttWrap, "TikTok wrap should unwrap");
  assert(consented.ttRendered, "tiktokEmbed render should run");
  assert(!consented.ytWrap, "YouTube wrap should unwrap");
  assert(!consented.ytBlocked, "YouTube must not stay blocked");
  assert(consented.ytSrc.includes("youtube.com"), "YouTube src must be present");
  assert(!consented.spotifyWrap, "Spotify wrap should unwrap");
  assert(!consented.spotifyBlocked, "Spotify must not stay blocked");
  assert(consented.spotifySrc.includes("spotify.com"), "Spotify src must be present");
  assert(!consented.gmapsWrap, "Google Maps wrap should unwrap");
  assert(!consented.unknownWrap, "Unknown wrap should unwrap");
  assert(!consented.unknownBlocked, "Unknown iframe must not stay blocked");
  assert(
    consented.unknownSrc.includes("weird-tracker.example"),
    "Unknown iframe src must be restored"
  );
  assert(
    postConsentAborted.length === 0,
    "Embed requests re-aborted after consent: " + JSON.stringify(postConsentAborted)
  );

  console.log(JSON.stringify({ blocked, consented, postConsentAborted, ok: true }, null, 2));
  console.log("PASS");
} finally {
  await browser.close();
}
