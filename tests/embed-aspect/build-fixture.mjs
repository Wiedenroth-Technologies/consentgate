/**
 * Shared helpers: inline the real ConsentGate blocker/config like WordPress does.
 */
import fs from "node:fs";
import path from "node:path";
import { fileURLToPath } from "node:url";

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const root = path.resolve(__dirname, "../..");

export function readBlockerSource() {
  return fs.readFileSync(
    path.join(root, "assets/js/consent-gate-blocker.js"),
    "utf8"
  );
}

export function readCssHref() {
  return pathToRel("assets/css/consent-gate.css");
}

export function pathToRel(rel) {
  return path.relative(__dirname, path.join(root, rel)).split(path.sep).join("/");
}

export const productionWidgetRegistry = [
  {
    id: "apple_maps",
    library: ["apple-mapkit.com", "cdn.apple-mapkit.com", "mapkit.js", "mapkit-stub.js"],
    containers: [],
    inline: "\\bmapkit\\s*\\.\\s*(?:init|Map)\\b",
    mapTarget: "mapkit\\.Map\\s*\\(\\s*[\"']([^\"']+)[\"']",
    reinit: "",
  },
  {
    id: "instagram",
    library: ["instagram.com/embed.js", "platform.instagram.com"],
    containers: ["blockquote.instagram-media"],
    inline: "",
    mapTarget: "",
    reinit: "instagram",
  },
  {
    id: "twitter",
    library: ["platform.twitter.com/widgets.js", "platform.x.com/widgets.js"],
    containers: ["blockquote.twitter-tweet", "blockquote.twitter-video"],
    inline: "",
    mapTarget: "",
    reinit: "twitter",
  },
  {
    id: "facebook",
    library: ["connect.facebook.net", "facebook.com/plugins"],
    containers: [".fb-post", ".fb-page", ".fb-video", ".fb-comment-embed"],
    inline: "",
    mapTarget: "",
    reinit: "facebook",
  },
  {
    id: "tiktok",
    library: ["tiktok.com/embed.js", "www.tiktok.com/embed.js"],
    containers: ["blockquote.tiktok-embed"],
    inline: "",
    mapTarget: "",
    reinit: "tiktok",
  },
  {
    id: "calendly",
    library: [
      "assets.calendly.com/assets/external/widget.js",
      "calendly.com/assets/external/widget.js",
    ],
    containers: [
      ".calendly-inline-widget",
      ".calendly-badge-widget",
      ".calendly-inline-widget-container",
    ],
    inline: "\\bCalendly\\.init(?:InlineWidget|PopupWidget)\\b",
    mapTarget: "",
    reinit: "calendly",
  },
  {
    id: "spotify",
    library: ["open.spotify.com/embed", "spotify.com/embed"],
    containers: [],
    inline: "",
    mapTarget: "",
    reinit: "",
  },
];

export const productionEmbedGates = {
  apple_maps: "marketing",
  linexo: "marketing",
  google_maps: "marketing",
  youtube: "marketing",
  vimeo: "marketing",
  instagram: "marketing",
  twitter: "marketing",
  facebook: "marketing",
  tiktok: "marketing",
  pinterest: "marketing",
  linkedin: "marketing",
  calendly: "marketing",
  spotify: "marketing",
  soundcloud: "marketing",
  external_content: "marketing",
};

export function wrapInlineScripts({ boot = "", configObject, bodyHtml, extraHead = "" }) {
  const blocker = readBlockerSource();
  const configJson = JSON.stringify(configObject);
  return `<!DOCTYPE html>
<html lang="de" class="wp-embed-responsive">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>ConsentGate live-inline fixture</title>
<link rel="stylesheet" href="${readCssHref()}" />
${extraHead}
<script id="consentgate-boot">${boot || "window.dataLayer=window.dataLayer||[];"}</script>
<script id="consentgate-config">window.__CONSENTGATE__=${configJson};</script>
<script id="consentgate-blocker">${blocker}</script>
</head>
<body>
${bodyHtml}
</body>
</html>`;
}
