(function installBlocker() {
      var boot = window.__CONSENTGATE__ || {};
      var restored = window.__CONSENTGATE_RESTORED__ || null;
      var config = boot.config || {};
      var policies = config.purposePolicies || {};
      var allowed = {
        statistics: Boolean(restored && restored.categories && restored.categories.statistics) || policies.statistics === "without_consent",
        marketing: Boolean(restored && restored.categories && restored.categories.marketing) || policies.marketing === "without_consent",
        preferences: Boolean(restored && restored.categories && restored.categories.preferences) || policies.preferences === "without_consent"
      };
      var rules = config.blockRules && config.blockRules.length
        ? config.blockRules
        : [
            { id: "google_ads", cat: "marketing", h: ["googleadservices.com", "googlesyndication.com", "doubleclick.net", "googletagservices.com", "google.com/pagead"], c: ["_gcl_au", "_gcl_aw", "_gac_"] },
            { id: "google_analytics", cat: "statistics", h: ["google-analytics.com", "analytics.google.com"], c: ["_ga", "_gid", "_gat"] },
            { id: "gtm", cat: "marketing", h: ["googletagmanager.com/gtm.js"], c: ["_ga", "_gid"] },
            { id: "google_maps", cat: "marketing", h: ["maps.googleapis.com", "maps.gstatic.com", "maps.google.com", "maps-api-ssl.google.com", "maps.app.goo.gl", "goo.gl/maps", "google.com/maps", "google.de/maps", "google.at/maps", "google.ch/maps"], c: ["NID"] },
            { id: "meta_pixel", cat: "marketing", h: ["connect.facebook.net", "facebook.com/tr", "facebook.net"], c: ["_fbp", "_fbc", "fr"] },
            { id: "tiktok", cat: "marketing", h: ["analytics.tiktok.com", "ads.tiktok.com", "tiktok.com/i18n/pixel"], c: ["_ttp"] },
            { id: "pinterest", cat: "marketing", h: ["s.pinimg.com", "ct.pinterest.com"], c: ["_pin_unauth", "_pinterest_ct"] },
            { id: "bing", cat: "marketing", h: ["bat.bing.com"], c: ["_uetsid", "_uetvid"] },
            { id: "clarity", cat: "statistics", h: ["clarity.ms"], c: ["_clck", "_clsk"] },
            { id: "klaviyo", cat: "marketing", h: ["static.klaviyo.com", "static-tracking.klaviyo.com", "a.klaviyo.com", "klaviyo.com"], c: ["__kla_id"] },
            { id: "snapchat", cat: "marketing", h: ["sc-static.net", "tr.snapchat.com"], c: ["_scid", "sc_at"] },
            { id: "linkedin", cat: "marketing", h: ["snap.licdn.com", "px.ads.linkedin.com", "ads.linkedin.com"], c: ["li_sugr"] },
            { id: "adcell", cat: "marketing", h: ["*.adcell.*", "t.adcell.com"], c: ["ADCELL", "adcell"] }
          ];
      var advancedGoogle = Boolean(config.googleAdvancedConsentMode);
      var bypass = false;
      var cookieBypass = false;
      var pending = [];
      var nativeCreate = document.createElement.bind(document);
      var nativeFetch = window.fetch && window.fetch.bind(window);
      var nativeOpen = XMLHttpRequest && XMLHttpRequest.prototype.open;
      var nativeSend = XMLHttpRequest && XMLHttpRequest.prototype.send;
      var nativeBeacon = navigator.sendBeacon && navigator.sendBeacon.bind(navigator);
      var nativeLsSet = Storage && Storage.prototype.setItem;
      var proto = window.HTMLScriptElement && HTMLScriptElement.prototype;
      var srcDesc = proto && Object.getOwnPropertyDescriptor(proto, "src");
      var nativeSetAttr = Element.prototype.setAttribute;
      var nativeAppend = Node.prototype.appendChild;
      var nativeInsert = Node.prototype.insertBefore;

      function tokenHit(src, token) {
        if (!src || !token) return false;
        var value = String(src).toLowerCase();
        var raw = String(token).toLowerCase();
        var host = "";
        try {
          host = new URL(src, location.href).hostname.toLowerCase();
        } catch (e) {}
        if (raw.indexOf("*") !== -1) {
          var brand = raw.replace(/^\*\./, "").replace(/\.\*$/, "").replace(/\*/g, "");
          if (!brand) return false;
          var label = new RegExp("(^|\\.)" + brand.replace(/[.*+?^${}()|[\]\\]/g, "\\$&") + "(\\.|$)");
          if (host && label.test(host)) return true;
          return value.indexOf("/apps/" + brand) !== -1 || value.indexOf("." + brand + ".") !== -1;
        }
        if (raw.indexOf("/") !== -1 || raw.indexOf(".") === -1) {
          return value.indexOf(raw) !== -1;
        }
        if (host) return host === raw || host.slice(-(raw.length + 1)) === "." + raw;
        return value.indexOf(raw) !== -1;
      }

      var HOST_BLOCKS = [
        { re: /(?:^|\.)googletagmanager\.com$/i, need: "google" },
        { re: /(?:^|\.)google-analytics\.com$/i, need: "statistics" },
        { re: /(?:^|\.)analytics\.google\.com$/i, need: "statistics" },
        { re: /(?:^|\.)googleadservices\.com$/i, need: "marketing" },
        { re: /(?:^|\.)googletagservices\.com$/i, need: "marketing" },
        { re: /(?:^|\.)doubleclick\.net$/i, need: "marketing" },
        { re: /(?:^|\.)googlesyndication\.com$/i, need: "marketing" },
        { re: /(?:^|\.)google\.com$/i, path: /\/pagead\b/i, need: "marketing" },
        { re: /(?:^|\.)maps\.googleapis\.com$/i, id: "google_maps", need: "marketing" },
        { re: /(?:^|\.)maps\.gstatic\.com$/i, id: "google_maps", need: "marketing" },
        { re: /(?:^|\.)maps\.google\.[a-z.]+$/i, id: "google_maps", need: "marketing" },
        { re: /(?:^|\.)maps-api-ssl\.google\.[a-z.]+$/i, id: "google_maps", need: "marketing" },
        { re: /(?:^|\.)maps\.app\.goo\.gl$/i, id: "google_maps", need: "marketing" },
        { re: /(?:^|\.)goo\.gl$/i, path: /\/maps\b/i, id: "google_maps", need: "marketing" },
        { re: /(?:^|\.)google\.[a-z.]+$/i, path: /\/maps\b/i, id: "google_maps", need: "marketing" },
        { re: /(?:^|\.)maps\.apple\.com$/i, id: "apple_maps", need: "marketing" },
        { re: /(?:^|\.)apple-mapkit\.com$/i, id: "apple_maps", need: "marketing" },
        { re: /(?:^|\.)embed\.apple\.com$/i, path: /\/maps\b|type=map/i, id: "apple_maps", need: "marketing" },
        { re: /(?:^|\.)site-connect\.services\.linexo\.com$/i, id: "linexo", need: "marketing" },
        { re: /(?:^|\.)adcell\./i, need: "marketing" },
        { re: /(?:^|\.)connect\.facebook\.net$/i, need: "marketing" },
        { re: /(?:^|\.)facebook\.net$/i, need: "marketing" },
        { re: /(?:^|\.)facebook\.com$/i, path: /\/tr\b/i, need: "marketing" },
        { re: /(?:^|\.)bat\.bing\.com$/i, need: "marketing" },
        { re: /(?:^|\.)analytics(?:-sg)?\.tiktok\.com$/i, need: "marketing" },
        { re: /(?:^|\.)ads\.tiktok\.com$/i, need: "marketing" },
        { re: /(?:^|\.)tiktok\.com$/i, path: /\/i18n\/pixel|\/pixel\//i, need: "marketing" },
        { re: /(?:^|\.)clarity\.ms$/i, need: "statistics" },
        { re: /(?:^|\.)pinimg\.com$/i, need: "marketing" },
        { re: /(?:^|\.)ct\.pinterest\.com$/i, need: "marketing" },
        { re: /(?:^|\.)klaviyo\.com$/i, need: "marketing" },
        { re: /(?:^|\.)sc-static\.net$/i, need: "marketing" },
        { re: /(?:^|\.)tr\.snapchat\.com$/i, need: "marketing" },
        { re: /(?:^|\.)licdn\.com$/i, need: "marketing" },
        { re: /(?:^|\.)linkedin\.com$/i, path: /\/px\/|\/li\/track/i, need: "marketing" },
        { re: /(?:^|\.)youtube(?:-nocookie)?\.com$/i, need: "marketing" },
        { re: /(?:^|\.)youtu\.be$/i, need: "marketing" },
        { re: /(?:^|\.)vimeo\.com$/i, need: "marketing" },
        { re: /(?:^|\.)google\.com$/i, path: /\/recaptcha\b/i, need: "marketing" },
        { re: /(?:^|\.)recaptcha\.net$/i, need: "marketing" },
        { re: /(?:^|\.)gstatic\.com$/i, path: /\/recaptcha\b/i, need: "marketing" },
        { re: /(?:^|\.)hs-scripts\.com$/i, need: "marketing" },
        { re: /(?:^|\.)hs-analytics\.net$/i, need: "marketing" },
        { re: /(?:^|\.)hscollectedforms\.net$/i, need: "marketing" },
        { re: /(?:^|\.)hubspot\.com$/i, need: "marketing" }
      ];

      function googleTrackerUrl(src) {
        var value = String(src || "");
        if (/gtag\/js|googletagmanager|google-analytics|googleadservices|doubleclick|googlesyndication/i.test(value)) {
          return true;
        }
        try {
          var host = new URL(value, location.href).hostname.replace(/^www\./, "");
          return /(?:^|\.)(?:googletagmanager|google-analytics|analytics\.google|googleadservices|doubleclick|googlesyndication)\./i.test(host)
            || /(?:^|\.)(?:googletagmanager|google-analytics|googleadservices)\.com$/i.test(host)
            || /(?:^|\.)doubleclick\.net$/i.test(host);
        } catch (e) {
          return false;
        }
      }

      function hostBlocked(src) {
        var value = String(src || "");
        if (advancedGoogle && googleTrackerUrl(value)) return "";
        if (/\/apps\/adcell(?:\/|\?|$)/i.test(value) && !allowed.marketing) return "marketing";
        var host = "";
        var path = "";
        try {
          var parsed = new URL(value, location.href);
          host = parsed.hostname.replace(/^www\./, "");
          path = parsed.pathname + parsed.search;
        } catch (e) {
          if (/(?:^|[\/.])adcell(?:[\/.?#]|$)/i.test(value) && !allowed.marketing) return "marketing";
          return "";
        }
        for (var i = 0; i < HOST_BLOCKS.length; i++) {
          var item = HOST_BLOCKS[i];
          if (!item.re.test(host)) continue;
          if (item.path && !item.path.test(path)) continue;
          if (item.need === "google") {
            if (!allowed.statistics && !allowed.marketing) return "marketing";
            continue;
          }
          var need = item.need;
          var gates = config.embedGates || {};
          if (item.id && Object.prototype.hasOwnProperty.call(gates, item.id)) {
            need = gates[item.id];
            if (!need) continue;
          }
          if (!allowed[need]) return need;
        }
        return "";
      }

      function matchingRule(src) {
        if (!src || bypass) return null;
        var value = String(src);
        if (advancedGoogle && googleTrackerUrl(value)) return null;
        var blocked = hostBlocked(value);
        if (blocked) return { id: "builtin", cat: blocked, h: [], c: [] };
        for (var i = 0; i < rules.length; i++) {
          var rule = rules[i];
          if (allowed[rule.cat]) continue;
          var tokens = rule.h || [];
          for (var j = 0; j < tokens.length; j++) {
            if (tokenHit(value, tokens[j])) return rule;
          }
        }
        return null;
      }

      function remember(src, cat, kind, extra) {
        extra = extra || {};
        if (!src && !extra.text) return;
        for (var i = 0; i < pending.length; i++) {
          if (src && pending[i].src === src) {
            if (!pending[i].node && extra.node) pending[i].node = extra.node;
            return;
          }
        }
        pending.push({
          src: src || "",
          cat: cat || "marketing",
          kind: kind || "script",
          text: extra.text || "",
          async: Boolean(extra.async),
          type: extra.type || "",
          node: extra.node || null
        });
      }

      function replaySrc(src) {
        return src;
      }

      function trackerHost(src) {
        if (!src || /[?&]cg_src=/.test(String(src))) return false;
        if (advancedGoogle && googleTrackerUrl(src)) return false;
        if (/\/apps\/adcell(?:\/|\?|$)/i.test(src)) return true;
        try {
          var host = new URL(src, location.href).hostname.replace(/^www\./, "");
          if (host === location.hostname.replace(/^www\./, "")) return false;
          for (var i = 0; i < HOST_BLOCKS.length; i++) {
            if (HOST_BLOCKS[i].re.test(host)) return true;
          }
        } catch (e) {}
        return false;
      }

      function safeScriptSrc(src) {
        return trackerHost(src) ? replaySrc(src) : src;
      }

      function stampScript(node) {
        if (!node.getAttribute("data-cg-type")) {
          var type = node.getAttribute("type") || "";
          if (type && type.toLowerCase() !== "text/plain") nativeSetAttr.call(node, "data-cg-type", type);
        }
        if (node.hasAttribute("async")) nativeSetAttr.call(node, "data-cg-async", "1");
      }

      function neutralize(node) {
        if (!node || node.nodeName !== "SCRIPT" || node.getAttribute("data-cg-blocked")) return false;
        if (isOwnConsentGateScript(node)) return false;
        var src = node.getAttribute("src") || "";
        var rule = src ? matchingRule(src) : null;
        if (!rule) return false;
        stampScript(node);
        remember(src, rule.cat, "script", {
          text: node.textContent || "",
          async: node.hasAttribute("async"),
          type: node.getAttribute("data-cg-type") || "",
          node: node.parentNode ? null : node
        });
        nativeSetAttr.call(node, "data-cg-blocked", rule.cat);
        nativeSetAttr.call(node, "data-cg-src", src);
        markEmbedKind(node, src);
        var wlib = widgetForLibrarySrc(src);
        if (wlib) nativeSetAttr.call(node, "data-cg-kind", wlib.id);
        try { node.type = "text/plain"; } catch (e) {}
        try { node.removeAttribute("src"); } catch (e2) {}
        try { if (srcDesc && srcDesc.set) srcDesc.set.call(node, ""); } catch (e3) {}
        return !node.parentNode;
      }

      function embedKind(src) {
        var value = String(src || "");
        if (/maps\.apple\.com|apple-mapkit\.com|embed\.apple\.com\/maps|embed\.apple\.com\/[^?#]*\?[^#]*type=map/i.test(value)) return "apple_maps";
        if (/linexo\.com|linexo-site-connect|linexo-siteconnect/i.test(value)) return "linexo";
        if (/maps\.googleapis\.com|maps\.gstatic\.com|maps\.google\.|maps-api-ssl\.google\.|maps\.app\.goo\.gl|goo\.gl\/maps|google\.[a-z.]+\/maps/i.test(value)) {
          return "google_maps";
        }
        if (/youtube(?:-nocookie)?\.com|youtu\.be/i.test(value)) return "youtube";
        if (/player\.vimeo\.com|vimeo\.com\/video/i.test(value)) return "vimeo";
        if (/instagram\.com\/(?:[^/?#]+\/)*embed(?:\/|\?|#|$)|platform\.instagram\.com/i.test(value)) return "instagram";
        if (/platform\.twitter\.com|platform\.x\.com|syndication\.twitter\.com|cdn\.syndication\.twimg\.com/i.test(value)) return "twitter";
        if (/facebook\.com\/plugins|connect\.facebook\.net\/[^"'\s]*sdk\.js|\.facebook\.com\/(?:plugins|v\d[\w.]*\/plugins)\//i.test(value)) return "facebook";
        if (/tiktok\.com\/(?:[^/?#]+\/)*embed/i.test(value)) return "tiktok";
        if (/assets\.pinterest\.com|pinterest\.com\/pin\/create/i.test(value)) return "pinterest";
        if (/linkedin\.com\/embed|platform\.linkedin\.com/i.test(value)) return "linkedin";
        if (/calendly\.com/i.test(value)) return "calendly";
        if (/open\.spotify\.com\/embed|spotify\.com\/embed/i.test(value)) return "spotify";
        if (/w\.soundcloud\.com\/player|soundcloud\.com\/player/i.test(value)) return "soundcloud";
        return "";
      }

      function isOwnConsentGateScript(node) {
        if (!node || node.nodeName !== "SCRIPT") return false;
        var id = String(node.id || node.getAttribute("id") || "").toLowerCase();
        if (id.indexOf("consentgate") === 0) return true;
        var src = String(node.getAttribute("src") || node.getAttribute("data-cg-src") || "").toLowerCase();
        if (!src) return false;
        if (/consent-gate(?:-[a-z0-9]+)?\.js(?:[?#]|$)/i.test(src)) return true;
        var base = String(boot.assetBase || "").toLowerCase();
        if (base) {
          var pluginRoot = base.replace(/assets\/logos\/?$/, "").replace(/\/$/, "");
          if (pluginRoot && src.indexOf(pluginRoot) !== -1) return true;
        }
        if (/\/wiedenroth-technologies-consentgate\//i.test(src)) return true;
        return false;
      }

      function embedLocale() {
        var mode = config.languageMode || "auto";
        if (mode === "de" || mode === "en") return mode;
        var html = String((document.documentElement && document.documentElement.lang) || "").toLowerCase().replace("_", "-");
        if (html.indexOf("en") === 0) return "en";
        if (html.indexOf("de") === 0) return "de";
        var site = String(boot.shopLocale || config.locale || "").toLowerCase().replace("_", "-");
        if (site.indexOf("en") === 0) return "en";
        if (site.indexOf("de") === 0) return "de";
        return "en";
      }

      function placeholderKind(el, src) {
        var key = (el && el.getAttribute && el.getAttribute("data-cg-kind")) || "";
        if (key) return key;
        return embedKind(src);
      }

      function markEmbedKind(el, src) {
        if (!el || el.getAttribute("data-cg-kind")) return;
        var kind = embedKind(src);
        if (!kind) return;
        nativeSetAttr.call(el, "data-cg-kind", kind);
      }

      function markEmbedHost(parent) {
        if (!parent || !parent.classList) return;
        if (parent.classList.contains("wp-block-embed__wrapper")) {
          parent.classList.add("cg-embed-host");
        }
      }

      function clearEmbedHost(parent) {
        if (!parent || !parent.classList) return;
        parent.classList.remove("cg-embed-host");
      }

      function serviceGate(key) {
        var gates = config.embedGates || {};
        if (Object.prototype.hasOwnProperty.call(gates, key)) return gates[key] || "";
        return "marketing";
      }

      function widgetRegistry() {
        return Array.isArray(config.widgetRegistry) ? config.widgetRegistry : [];
      }

      function widgetById(id) {
        var list = widgetRegistry();
        for (var i = 0; i < list.length; i++) {
          if (list[i] && list[i].id === id) return list[i];
        }
        return null;
      }

      function widgetForLibrarySrc(src) {
        var value = String(src || "").toLowerCase();
        if (!value) return null;
        var list = widgetRegistry();
        for (var i = 0; i < list.length; i++) {
          var lib = list[i].library || [];
          for (var j = 0; j < lib.length; j++) {
            if (lib[j] && value.indexOf(String(lib[j]).toLowerCase()) !== -1) return list[i];
          }
        }
        return null;
      }

      function widgetForInline(text, node) {
        if (isOwnConsentGateScript(node)) return null;
        var value = String(text || "");
        if (!value) return null;
        // Config JSON / boot payloads mention library APIs without being widget bootstraps.
        if (/window\.__CONSENTGATE__|__CONSENTGATE_BLOCKER__|__CONSENTGATE_RESTORED__/i.test(value)) return null;
        var list = widgetRegistry();
        for (var i = 0; i < list.length; i++) {
          var pattern = list[i].inline || "";
          if (!pattern) continue;
          try {
            if (new RegExp(pattern, "i").test(value)) return list[i];
          } catch (e) {}
        }
        return null;
      }

      function serviceShortName(kind, locale, host) {
        var map = {
          google_maps: "Google Maps",
          apple_maps: "Apple Maps",
          linexo: "Linexo",
          youtube: "YouTube",
          vimeo: "Vimeo",
          instagram: "Instagram",
          twitter: "X",
          facebook: "Facebook",
          tiktok: "TikTok",
          pinterest: "Pinterest",
          calendly: "Calendly",
          spotify: "Spotify",
          soundcloud: "SoundCloud",
          linkedin: "LinkedIn",
          external_content: host || (locale === "en" ? "External content" : "Externer Inhalt")
        };
        return map[kind] || host || kind || (locale === "en" ? "Content" : "Inhalt");
      }

      function serviceTitle(kind, locale, ui, host) {
        var map = {
          google_maps: ui.mapsTitle || (locale === "en" ? "Google Maps is blocked" : "Google Maps ist blockiert"),
          apple_maps: ui.appleTitle || (locale === "en" ? "Apple Maps is blocked" : "Apple Maps ist blockiert"),
          linexo: ui.linexoTitle || (locale === "en" ? "Linexo is blocked" : "Linexo ist blockiert"),
          youtube: ui.youtubeTitle || (locale === "en" ? "YouTube is blocked" : "YouTube ist blockiert"),
          vimeo: ui.vimeoTitle || (locale === "en" ? "Vimeo is blocked" : "Vimeo ist blockiert"),
          instagram: ui.instagramTitle || (locale === "en" ? "Instagram is blocked" : "Instagram ist blockiert"),
          twitter: ui.twitterTitle || (locale === "en" ? "X (Twitter) is blocked" : "X (Twitter) ist blockiert"),
          facebook: ui.facebookTitle || (locale === "en" ? "Facebook is blocked" : "Facebook ist blockiert"),
          tiktok: ui.tiktokTitle || (locale === "en" ? "TikTok is blocked" : "TikTok ist blockiert"),
          pinterest: ui.pinterestTitle || (locale === "en" ? "Pinterest is blocked" : "Pinterest ist blockiert"),
          calendly: ui.calendlyTitle || (locale === "en" ? "Calendly is blocked" : "Calendly ist blockiert"),
          spotify: ui.spotifyTitle || (locale === "en" ? "Spotify is blocked" : "Spotify ist blockiert"),
          soundcloud: ui.soundcloudTitle || (locale === "en" ? "SoundCloud is blocked" : "SoundCloud ist blockiert"),
          linkedin: ui.linkedinTitle || (locale === "en" ? "LinkedIn is blocked" : "LinkedIn ist blockiert"),
          external_content: ui.externalTitle || (locale === "en" ? "External content is blocked" : "Externer Inhalt ist blockiert")
        };
        if (map[kind]) return map[kind];
        var tpl = ui.blockedService || (locale === "en" ? "%s is blocked" : "%s ist blockiert");
        var name = host || kind || (locale === "en" ? "External content" : "Externer Inhalt");
        return String(tpl).replace("%s", name);
      }

      function serviceBody(kind, locale, ui, host) {
        if (kind === "google_maps") {
          return ui.mapsBody || (locale === "en" ? "Allow marketing cookies to show the map." : "Marketing-Cookies erlauben, um die Karte zu sehen.");
        }
        if (kind === "youtube") {
          return ui.youtubeBody || (locale === "en" ? "Allow marketing cookies to play this video." : "Marketing-Cookies erlauben, um das Video zu sehen.");
        }
        if (kind === "vimeo") {
          return ui.vimeoBody || (locale === "en" ? "Allow marketing cookies to play this video." : "Marketing-Cookies erlauben, um das Video zu sehen.");
        }
        if (kind === "external_content") {
          var tpl = ui.externalBody || (locale === "en" ? "Allow marketing cookies to show content from %s." : "Erlauben Sie Marketing-Cookies, um Inhalte von %s zu sehen.");
          return String(tpl).replace("%s", host || (locale === "en" ? "this provider" : "diesem Anbieter"));
        }
        return ui.embedBody || (locale === "en" ? "Allow the matching category to show this content." : "Erlauben Sie die passende Kategorie, um diesen Inhalt zu sehen.");
      }

      function logoForKind(kind) {
        var logos = boot.logos || {};
        return logos[kind] || logos._placeholder || "";
      }

      function fitEmbedWrap(wrap) {
        if (!wrap || !wrap.getBoundingClientRect) return;
        var rect = wrap.getBoundingClientRect();
        var w = rect.width || wrap.clientWidth || 0;
        var h = rect.height || wrap.clientHeight || 0;
        var fit = "roomy";
        if (w > 0 && w < 160) fit = "minimal";
        else if ((w > 0 && w < 220) || (h > 0 && h < 140)) fit = "tight";
        else if (w > 0 && w < 320) fit = "compact";
        if (h > 0 && h < 100) fit = "minimal";
        if (wrap.getAttribute("data-cg-fit") !== fit) {
          nativeSetAttr.call(wrap, "data-cg-fit", fit);
        }
      }

      function observeEmbedFit(wrap) {
        fitEmbedWrap(wrap);
        if (typeof ResizeObserver === "function") {
          if (!wrap.__cgFitObs) {
            wrap.__cgFitObs = new ResizeObserver(function () {
              fitEmbedWrap(wrap);
            });
            try { wrap.__cgFitObs.observe(wrap); } catch (e) {}
          }
        } else if (!wrap.__cgFitTimer) {
          wrap.__cgFitTimer = setInterval(function () {
            if (!wrap.isConnected) {
              clearInterval(wrap.__cgFitTimer);
              return;
            }
            fitEmbedWrap(wrap);
          }, 500);
        }
      }

      function appendEmbedOverlay(wrap, kind, host) {
        var locale = embedLocale();
        var ui = (config.ui && config.ui[locale]) || {};
        var shortName = serviceShortName(kind, locale, host);
        var openLabel = ui.openSettings || (locale === "en" ? "Open cookie settings" : "Cookie-Einstellungen öffnen");
        var a11y = openLabel + " — " + shortName;
        var overlay = nativeCreate("div");
        overlay.className = "cg-embed-overlay";
        overlay.setAttribute("role", "region");
        overlay.setAttribute("aria-label", shortName);
        var logo = logoForKind(kind);
        if (logo) {
          var img = nativeCreate("img");
          img.className = "cg-embed-logo";
          img.src = logo;
          img.alt = "";
          img.width = 28;
          img.height = 28;
          overlay.appendChild(img);
        }
        var title = nativeCreate("strong");
        title.className = "cg-embed-title";
        var titleFull = nativeCreate("span");
        titleFull.className = "cg-embed-title-full";
        titleFull.textContent = serviceTitle(kind, locale, ui, host);
        var titleShort = nativeCreate("span");
        titleShort.className = "cg-embed-title-short";
        titleShort.textContent = shortName;
        title.appendChild(titleFull);
        title.appendChild(titleShort);
        var body = nativeCreate("p");
        body.className = "cg-embed-body";
        body.textContent = serviceBody(kind, locale, ui, host);
        var btn = nativeCreate("button");
        btn.type = "button";
        btn.className = "cg-embed-open";
        btn.setAttribute("aria-label", a11y);
        btn.setAttribute("title", a11y);
        var btnIcon = nativeCreate("span");
        btnIcon.className = "cg-embed-open-icon";
        btnIcon.setAttribute("aria-hidden", "true");
        btnIcon.innerHTML = '<svg viewBox="0 0 24 24" width="18" height="18" focusable="false"><path fill="currentColor" d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2zm0 3.2a2.1 2.1 0 1 1-2.1 2.1A2.1 2.1 0 0 1 12 5.2zm0 13.1a6.4 6.4 0 0 1-5.1-2.6 1 1 0 0 1 .2-1.3 5.7 5.7 0 0 1 9.8 0 1 1 0 0 1 .2 1.3A6.4 6.4 0 0 1 12 18.3z"/></svg>';
        var btnLabel = nativeCreate("span");
        btnLabel.className = "cg-embed-open-label";
        btnLabel.textContent = openLabel;
        btn.appendChild(btnIcon);
        btn.appendChild(btnLabel);
        btn.addEventListener("click", function () {
          if (window.ConsentGate && typeof window.ConsentGate.open === "function") window.ConsentGate.open();
        });
        overlay.appendChild(title);
        overlay.appendChild(body);
        overlay.appendChild(btn);
        wrap.appendChild(overlay);
        observeEmbedFit(wrap);
      }

      function wrapMapsPlaceholder(iframe) {
        var src = iframe.getAttribute("data-cg-src") || "";
        var kind = placeholderKind(iframe, src);
        if (!kind) return;
        if (iframe.parentNode && iframe.parentNode.classList && iframe.parentNode.classList.contains("cg-embed-wrap")) {
          return;
        }
        var wrap = nativeCreate("div");
        wrap.className = "cg-embed-wrap";
        var parent = iframe.parentNode;
        if (!parent) return;
        var responsiveHost =
          parent.classList && parent.classList.contains("wp-block-embed__wrapper");
        var h = iframe.getAttribute("height") || iframe.style.height;
        if (h && !responsiveHost) {
          wrap.style.minHeight = /px|%|vh|rem/.test(String(h)) ? String(h) : String(h) + "px";
        }
        markEmbedHost(parent);
        parent.insertBefore(wrap, iframe);
        wrap.appendChild(iframe);
        var host = iframe.getAttribute("data-cg-host") || "";
        if (!host) {
          try { host = new URL(src, location.href).hostname.replace(/^www\./, ""); } catch (e) {}
        }
        appendEmbedOverlay(wrap, kind, host);
      }

      function unwrapMapsPlaceholder(el) {
        var wrap = el && el.parentNode;
        if (!wrap || !wrap.classList || !wrap.classList.contains("cg-embed-wrap")) return;
        var parent = wrap.parentNode;
        if (!parent) return;
        // Evacuate every non-overlay child first. Embed SDKs (Instagram) may
        // insert iframes as siblings inside the wrap; destroying the wrap
        // would delete those nodes with it.
        while (wrap.firstChild) {
          var child = wrap.firstChild;
          if (child.nodeType === 1 && child.classList && child.classList.contains("cg-embed-overlay")) {
            wrap.removeChild(child);
            continue;
          }
          parent.insertBefore(child, wrap);
        }
        parent.removeChild(wrap);
        clearEmbedHost(parent);
      }

      function clearWidgetMarks(el) {
        if (!el || !el.removeAttribute) return;
        el.removeAttribute("data-cg-blocked");
        el.removeAttribute("data-cg-widget");
        el.removeAttribute("data-cg-kind");
        el.removeAttribute("data-cg-host");
      }

      function wrapWidgetElement(el, widgetKey, gate) {
        if (!el || el.getAttribute("data-cg-widget")) return;
        var parent = el.parentNode;
        if (!parent) return;
        if (parent.classList && parent.classList.contains("cg-embed-wrap")) return;
        el.setAttribute("data-cg-widget", widgetKey);
        el.setAttribute("data-cg-blocked", gate);
        nativeSetAttr.call(el, "data-cg-kind", widgetKey);
        var wrap = nativeCreate("div");
        wrap.className = "cg-embed-wrap cg-embed-wrap--widget";
        var h = el.getAttribute("height") || (el.style && el.style.height) || "";
        if (h) {
          wrap.style.minHeight = /px|%|vh|rem/.test(String(h)) ? String(h) : String(h) + "px";
        } else if (!wrap.style.minHeight) {
          wrap.style.minHeight = "220px";
        }
        parent.insertBefore(wrap, el);
        wrap.appendChild(el);
        appendEmbedOverlay(wrap, widgetKey, "");
        observeEmbedFit(wrap);
      }

      function deferWidgetInlineScripts(root) {
        var scope = root && root.querySelectorAll ? root : document;
        var list = [];
        if (root && root.nodeType === 1 && root.nodeName === "SCRIPT") list.push(root);
        if (scope.querySelectorAll) {
          var found = scope.querySelectorAll("script");
          for (var i = 0; i < found.length; i++) list.push(found[i]);
        }
        for (var n = 0; n < list.length; n++) {
          var node = list[n];
          if (!node || node.nodeName !== "SCRIPT") continue;
          if (isOwnConsentGateScript(node)) continue;
          if (node.getAttribute("src") || node.getAttribute("data-cg-src")) continue;
          if (node.getAttribute("data-cg-blocked")) continue;
          var type = String(node.getAttribute("type") || "").toLowerCase();
          if (type && type.indexOf("json") !== -1) continue;
          var widget = widgetForInline(node.textContent || "", node);
          if (!widget) continue;
          var gate = serviceGate(widget.id);
          if (!gate || allowed[gate]) continue;
          stampScript(node);
          nativeSetAttr.call(node, "data-cg-blocked", gate);
          nativeSetAttr.call(node, "data-cg-kind", widget.id);
          nativeSetAttr.call(node, "data-consent-gate", gate);
          try { node.type = "text/plain"; } catch (e) {}
        }
      }

      function mapTargetsForWidget(widget) {
        var ids = {};
        if (!widget || !widget.mapTarget) return [];
        var re;
        try { re = new RegExp(widget.mapTarget, "g"); } catch (e) { return []; }
        var scripts = document.querySelectorAll("script");
        for (var i = 0; i < scripts.length; i++) {
          if (isOwnConsentGateScript(scripts[i])) continue;
          var text = scripts[i].textContent || "";
          if (/window\.__CONSENTGATE__/i.test(text)) continue;
          var match;
          re.lastIndex = 0;
          while ((match = re.exec(text))) {
            if (match[1]) ids[match[1]] = true;
          }
        }
        return Object.keys(ids);
      }

      function blockRegisteredWidgets(root) {
        deferWidgetInlineScripts(root);
        var list = widgetRegistry();
        var scope = root && root.querySelectorAll ? root : document;
        for (var i = 0; i < list.length; i++) {
          var widget = list[i];
          var gate = serviceGate(widget.id);
          if (!gate || allowed[gate]) continue;
          var selectors = widget.containers || [];
          for (var s = 0; s < selectors.length; s++) {
            var sel = selectors[s];
            if (!sel || !scope.querySelectorAll) continue;
            var nodes = [];
            if (root && root.nodeType === 1 && root.matches && root.matches(sel)) nodes.push(root);
            try {
              var found = scope.querySelectorAll(sel);
              for (var f = 0; f < found.length; f++) nodes.push(found[f]);
            } catch (e2) {}
            for (var n = 0; n < nodes.length; n++) wrapWidgetElement(nodes[n], widget.id, gate);
          }
          var ids = mapTargetsForWidget(widget);
          for (var t = 0; t < ids.length; t++) {
            var el = document.getElementById(ids[t]);
            if (el) wrapWidgetElement(el, widget.id, gate);
          }
        }
      }

      function blockWidgets(root) {
        blockRegisteredWidgets(root);
      }

      function instagramEmbedPending(el) {
        if (!el || !el.parentNode) return false;
        var sib = el.previousElementSibling;
        if (sib && sib.tagName === "IFRAME") {
          var mark = (sib.className || "") + " " + (sib.id || "") + " " + (sib.getAttribute("src") || "");
          if (/instagram\.com\/(?:[^/?#]+\/)*embed|instagram-media|instagram-embed-/i.test(mark)) return true;
        }
        if (el.id) {
          var byPayload = el.parentNode.querySelector('iframe[data-instgrm-payload-id="' + el.id.replace(/"/g, "") + '"]');
          if (byPayload) return true;
        }
        return false;
      }

      function prepareInstagramForProcess() {
        // Instagram's embed.js strips class "instagram-media" after a first
        // attempt and only re-processes nodes that still have that class.
        var nodes = document.querySelectorAll(
          "blockquote.instagram-media, blockquote.instagram-media-registered, blockquote[data-instgrm-permalink]"
        );
        for (var i = 0; i < nodes.length; i++) {
          var el = nodes[i];
          if (!el || el.tagName !== "BLOCKQUOTE") continue;
          if (!el.getAttribute("data-instgrm-permalink") && !el.querySelector("a[href*='instagram.com']")) continue;
          // Already has a sibling iframe from a successful/in-flight process — leave it.
          if (instagramEmbedPending(el)) continue;
          var cls = String(el.className || "");
          cls = cls.replace(/\binstagram-media-registered\b/g, " ").replace(/\binstagram-media-rendered\b/g, " ");
          if (!/\binstagram-media\b/.test(cls)) cls += " instagram-media";
          el.className = cls.replace(/\s+/g, " ").trim();
        }
      }

      function runReinitHooks(kinds) {
        var seen = {};
        var list = kinds || [];
        for (var i = 0; i < list.length; i++) {
          var id = list[i];
          if (!id || seen[id]) continue;
          seen[id] = true;
          var widget = widgetById(id);
          var hook = widget && widget.reinit ? widget.reinit : id;
          try {
            if (hook === "instagram" && window.instgrm && instgrm.Embeds && typeof instgrm.Embeds.process === "function") {
              prepareInstagramForProcess();
              // Only invoke when something still needs processing (avoid double-init).
              if (document.querySelector("blockquote.instagram-media")) {
                instgrm.Embeds.process();
              }
            } else if (hook === "twitter" && window.twttr && twttr.widgets && typeof twttr.widgets.load === "function") {
              twttr.widgets.load();
            } else if (hook === "facebook" && window.FB && FB.XFBML && typeof FB.XFBML.parse === "function") {
              FB.XFBML.parse();
            } else if (hook === "tiktok" && window.tiktokEmbed && typeof window.tiktokEmbed.lib.render === "function") {
              window.tiktokEmbed.lib.render();
            } else if (hook === "pinterest" && window.PinUtils && typeof window.PinUtils.build === "function") {
              window.PinUtils.build();
            } else if (hook === "calendly" && window.Calendly && typeof window.Calendly.initInlineWidgets === "function") {
              window.Calendly.initInlineWidgets();
            }
          } catch (e) {}
        }
      }

      function releaseWidgetPlaceholders() {
        var released = [];
        var nodes = document.querySelectorAll("[data-cg-widget]");
        for (var i = 0; i < nodes.length; i++) {
          var el = nodes[i];
          var key = el.getAttribute("data-cg-widget") || "";
          var gate = serviceGate(key) || el.getAttribute("data-cg-blocked") || "";
          if (gate && !allowed[gate] && !allowedCategory(gate)) continue;
          if (key) released.push(key);
          clearWidgetMarks(el);
          unwrapMapsPlaceholder(el);
        }
        // Also remove orphan wraps that lost their marked child (SDK may have
        // moved/removed the original node during a partial process).
        var wraps = document.querySelectorAll(".cg-embed-wrap");
        for (var w = 0; w < wraps.length; w++) {
          var wrap = wraps[w];
          if (wrap.querySelector("[data-cg-blocked], [data-cg-widget]")) continue;
          var parent = wrap.parentNode;
          if (!parent) continue;
          while (wrap.firstChild) {
            var child = wrap.firstChild;
            if (child.nodeType === 1 && child.classList && child.classList.contains("cg-embed-overlay")) {
              wrap.removeChild(child);
              continue;
            }
            parent.insertBefore(child, wrap);
          }
          parent.removeChild(wrap);
          clearEmbedHost(parent);
        }
        return released;
      }

      function siteHost() {
        return String(boot.shop || location.hostname || "").replace(/^www\./, "").toLowerCase();
      }

      function iframeHost(src) {
        try {
          return new URL(src, location.href).hostname.replace(/^www\./, "").toLowerCase();
        } catch (e) {
          return "";
        }
      }

      function allowlistedExternal(host) {
        var list = config.externalEmbedAllowlist || [];
        for (var i = 0; i < list.length; i++) {
          var entry = String(list[i] || "").trim().toLowerCase();
          if (!entry) continue;
          if (entry.indexOf("*.") === 0) {
            var base = entry.slice(2).replace(/^www\./, "");
            if (!base) continue;
            if (host === base || host.slice(-(base.length + 1)) === "." + base) return true;
            continue;
          }
          var allowedHost = entry.replace(/^www\./, "");
          if (!allowedHost) continue;
          if (host === allowedHost || host.slice(-(allowedHost.length + 1)) === "." + allowedHost) return true;
        }
        return false;
      }

      function externalIframeDecision(src) {
        if (!src) return null;
        if (/^(?:about:|javascript:|data:|#)/i.test(src)) return null;
        var host = iframeHost(src);
        if (!host) return null;
        var own = siteHost();
        if (own && (host === own || host.slice(-(own.length + 1)) === "." + own)) return null;
        var kind = embedKind(src);
        if (kind) {
          var knownGate = serviceGate(kind) || "marketing";
          if (!knownGate || allowed[knownGate]) return null;
          return { cat: knownGate, kind: kind, host: host };
        }
        var rule = matchingRule(src);
        if (rule) {
          if (allowed[rule.cat]) return null;
          var keyed = (rule.id && rule.id !== "builtin") ? rule.id : "";
          return { cat: rule.cat, kind: keyed || "external_content", host: host };
        }
        if (config.blockExternalEmbeds === false) return null;
        if (allowlistedExternal(host)) return null;
        var extGate = serviceGate("external_content") || "marketing";
        if (!extGate || allowed[extGate]) return null;
        return { cat: extGate, kind: "external_content", host: host };
      }

      function blockMedia(el) {
        if (el.getAttribute("data-cg-blocked")) {
          if (el.nodeName === "IFRAME" || el.nodeName === "IMG") wrapMapsPlaceholder(el);
          return;
        }
        var src = el.getAttribute("src") || el.getAttribute("data-src") || el.getAttribute("data-lazy-src") || "";
        if (el.nodeName === "IFRAME") {
          var decision = externalIframeDecision(src);
          if (!decision) return;
          remember(src, decision.cat, "iframe");
          nativeSetAttr.call(el, "data-cg-blocked", decision.cat);
          nativeSetAttr.call(el, "data-cg-src", src);
          nativeSetAttr.call(el, "data-cg-kind", decision.kind);
          if (decision.host) nativeSetAttr.call(el, "data-cg-host", decision.host);
          el.removeAttribute("src");
          el.removeAttribute("data-src");
          el.removeAttribute("data-lazy-src");
          wrapMapsPlaceholder(el);
          return;
        }
        var rule = matchingRule(src);
        if (!rule) return;
        remember(src, rule.cat, el.nodeName.toLowerCase());
        nativeSetAttr.call(el, "data-cg-blocked", rule.cat);
        nativeSetAttr.call(el, "data-cg-src", src);
        markEmbedKind(el, src);
        el.removeAttribute("src");
        el.removeAttribute("data-src");
        el.removeAttribute("data-lazy-src");
        if (el.nodeName === "IMG") wrapMapsPlaceholder(el);
      }

      function neutralizeTree(root) {
        if (!root) return;
        if (root.nodeName === "SCRIPT") neutralize(root);
        if (root.nodeName === "IFRAME" || root.nodeName === "IMG") blockMedia(root);
        if (!root.querySelectorAll) return;
        var scripts = root.querySelectorAll("script[src]");
        for (var i = 0; i < scripts.length; i++) neutralize(scripts[i]);
        var media = root.querySelectorAll("iframe[src], iframe[data-src], iframe[data-lazy-src], iframe[data-cg-blocked], img[src], img[data-cg-blocked]");
        for (var k = 0; k < media.length; k++) blockMedia(media[k]);
        blockWidgets(root);
        var links = root.querySelectorAll("link[href]");
        for (var n = 0; n < links.length; n++) {
          var rel = (links[n].getAttribute("rel") || "").toLowerCase();
          var as = (links[n].getAttribute("as") || "").toLowerCase();
          var href = links[n].getAttribute("href") || "";
          var isSheet = rel.indexOf("stylesheet") !== -1;
          var isHint = rel.indexOf("preload") !== -1 || rel.indexOf("prefetch") !== -1;
          if (!isSheet && !isHint) continue;
          if (isHint && as && as !== "script" && as !== "fetch" && as !== "style" && as !== "font") continue;
          if (matchingRule(href)) {
            var linkRule = matchingRule(href);
            nativeSetAttr.call(links[n], "data-cg-blocked", linkRule.cat);
            nativeSetAttr.call(links[n], "data-cg-href", href);
            links[n].removeAttribute("href");
          }
        }
      }

      new MutationObserver(function (mutations) {
        for (var i = 0; i < mutations.length; i++) {
          var nodes = mutations[i].addedNodes;
          for (var j = 0; j < nodes.length; j++) neutralizeTree(nodes[j]);
        }
      }).observe(document.documentElement, { childList: true, subtree: true });

      function deferInlineWidgetNode(node) {
        if (!node || node.nodeName !== "SCRIPT") return false;
        if (isOwnConsentGateScript(node)) return false;
        if (node.getAttribute("src") || node.getAttribute("data-cg-src")) return false;
        if (node.getAttribute("data-cg-blocked")) return false;
        var widget = widgetForInline(node.textContent || "", node);
        if (!widget) return false;
        var gate = serviceGate(widget.id);
        if (!gate || allowed[gate]) return false;
        stampScript(node);
        nativeSetAttr.call(node, "data-cg-blocked", gate);
        nativeSetAttr.call(node, "data-cg-kind", widget.id);
        nativeSetAttr.call(node, "data-consent-gate", gate);
        try { node.type = "text/plain"; } catch (e) {}
        return false;
      }

      Node.prototype.appendChild = function (node) {
        if (node && node.nodeName === "SCRIPT") {
          if (neutralize(node)) return node;
          deferInlineWidgetNode(node);
          var appendSrc = node.getAttribute("src") || "";
          if (appendSrc && trackerHost(appendSrc)) nativeSetAttr.call(node, "src", replaySrc(appendSrc));
        }
        return nativeAppend.call(this, node);
      };
      Node.prototype.insertBefore = function (node, ref) {
        if (node && node.nodeName === "SCRIPT") {
          if (neutralize(node)) return node;
          deferInlineWidgetNode(node);
          var insertSrc = node.getAttribute("src") || "";
          if (insertSrc && trackerHost(insertSrc)) nativeSetAttr.call(node, "src", replaySrc(insertSrc));
        }
        return nativeInsert.call(this, node, ref);
      };

      if (srcDesc && srcDesc.set) {
        Object.defineProperty(proto, "src", {
          configurable: true,
          enumerable: true,
          get: function () {
            return this.getAttribute("data-cg-src") || (srcDesc.get && srcDesc.get.call(this)) || this.getAttribute("src") || "";
          },
          set: function (value) {
            var rule = matchingRule(value);
            if (rule) {
              stampScript(this);
              remember(value, rule.cat, "script", {
                text: this.textContent || "",
                async: this.hasAttribute("async"),
                type: this.getAttribute("data-cg-type") || "",
                node: this.parentNode ? null : this
              });
              nativeSetAttr.call(this, "data-cg-blocked", rule.cat);
              nativeSetAttr.call(this, "data-cg-src", value);
              this.type = "text/plain";
              return;
            }
            srcDesc.set.call(this, safeScriptSrc(value));
          }
        });
      }

      Element.prototype.setAttribute = function (name, value) {
        if (!bypass && this.nodeName === "SCRIPT" && String(name).toLowerCase() === "src") {
          var rule = matchingRule(value);
          if (rule) {
            stampScript(this);
            remember(value, rule.cat, "script", {
              text: this.textContent || "",
              async: this.hasAttribute("async"),
              type: this.getAttribute("data-cg-type") || "",
              node: this.parentNode ? null : this
            });
            nativeSetAttr.call(this, "data-cg-blocked", rule.cat);
            nativeSetAttr.call(this, "data-cg-src", value);
            nativeSetAttr.call(this, "type", "text/plain");
            return;
          }
        }
        if (!bypass && (this.nodeName === "IFRAME" || this.nodeName === "IMG") && String(name).toLowerCase() === "src") {
          var mediaRule = matchingRule(value);
          if (mediaRule) {
            remember(value, mediaRule.cat, this.nodeName.toLowerCase());
            nativeSetAttr.call(this, "data-cg-blocked", mediaRule.cat);
            nativeSetAttr.call(this, "data-cg-src", value);
            markEmbedKind(this, value);
            wrapMapsPlaceholder(this);
            return;
          }
        }
        if (this.nodeName === "SCRIPT" && String(name).toLowerCase() === "src") {
          return nativeSetAttr.call(this, name, safeScriptSrc(value));
        }
        return nativeSetAttr.call(this, name, value);
      };

      var iframeProto = window.HTMLIFrameElement && HTMLIFrameElement.prototype;
      var iframeSrcDesc = iframeProto && Object.getOwnPropertyDescriptor(iframeProto, "src");
      if (iframeSrcDesc && iframeSrcDesc.set) {
        Object.defineProperty(iframeProto, "src", {
          configurable: true,
          enumerable: true,
          get: function () {
            return this.getAttribute("data-cg-src") || (iframeSrcDesc.get && iframeSrcDesc.get.call(this)) || this.getAttribute("src") || "";
          },
          set: function (value) {
            var iframeRule = matchingRule(value);
            if (iframeRule && !bypass) {
            remember(value, iframeRule.cat, "iframe");
            nativeSetAttr.call(this, "data-cg-blocked", iframeRule.cat);
            nativeSetAttr.call(this, "data-cg-src", value);
            markEmbedKind(this, value);
            wrapMapsPlaceholder(this);
              return;
            }
            iframeSrcDesc.set.call(this, value);
          }
        });
      }

      function blockedRequest(url, body) {
        if (matchingRule(url)) return true;
        return Boolean(body && /_adcell_bid/i.test(String(body)) && /\/cart\/update/i.test(String(url || "")));
      }

      if (nativeFetch) {
        window.fetch = function (input, init) {
          var url = typeof input === "string" ? input : input && input.url;
          var body = init && init.body;
          if (blockedRequest(url, body)) {
            return Promise.resolve(new Response("{}", { status: 200, headers: { "Content-Type": "application/json" } }));
          }
          return nativeFetch(input, init);
        };
      }
      if (nativeOpen && nativeSend) {
        XMLHttpRequest.prototype.open = function (method, url) {
          this.__cgUrl = url;
          return nativeOpen.apply(this, arguments);
        };
        XMLHttpRequest.prototype.send = function (body) {
          if (blockedRequest(this.__cgUrl, body)) {
            this.abort();
            return;
          }
          return nativeSend.apply(this, arguments);
        };
      }
      if (nativeBeacon) {
        navigator.sendBeacon = function (url, data) {
          if (blockedRequest(url, data)) return true;
          return nativeBeacon(url, data);
        };
      }
      if (nativeLsSet) {
        Storage.prototype.setItem = function (key, value) {
          if (!allowed.marketing && /^adcell/i.test(String(key))) return;
          return nativeLsSet.call(this, key, value);
        };
      }

      function cookieTokenHit(name, token) {
        var n = String(name || "").toLowerCase();
        var t = String(token || "").toLowerCase();
        if (!n || !t) return false;
        if (t.length < 4 && t.charAt(0) !== "_") return n === t;
        return n.indexOf(t) === 0;
      }

      function cookieBlocked(name) {
        if (!name || /^(?:_shopify|cart|secure_session|_cmp|_tracking|localization|keep_alive|consentgate|_secure|checkout|wordpress_|wp-settings|wp_lang|wp-saving|woocommerce_|wp_woocommerce|PHPSESSID|wordpress_logged_in|wordpress_test_cookie)/i.test(name)) {
          return false;
        }
        if (!allowed.marketing && /^adcell/i.test(name)) return true;
        for (var i = 0; i < rules.length; i++) {
          var rule = rules[i];
          if (allowed[rule.cat]) continue;
          var prefixes = rule.c || [];
          for (var j = 0; j < prefixes.length; j++) {
            if (cookieTokenHit(name, prefixes[j])) return true;
          }
        }
        return false;
      }

      function purgeCookies() {
        cookieBypass = true;
        try {
          var parts = (document.cookie || "").split(";");
          for (var i = 0; i < parts.length; i++) {
            var name = parts[i].split("=")[0].trim();
            if (!cookieBlocked(name)) continue;
            document.cookie = name + "=; Max-Age=0; path=/";
            document.cookie = name + "=; Max-Age=0; path=/; domain=" + location.hostname;
          }
          try {
            Object.keys(localStorage).forEach(function (key) {
              if (!allowed.marketing && /^adcell/i.test(key)) localStorage.removeItem(key);
            });
          } catch (e2) {}
        } catch (e) {}
        cookieBypass = false;
      }

      var cookieDesc = Object.getOwnPropertyDescriptor(Document.prototype, "cookie") ||
        Object.getOwnPropertyDescriptor(HTMLDocument.prototype, "cookie");
      if (cookieDesc && cookieDesc.set && cookieDesc.get) {
        Object.defineProperty(document, "cookie", {
          configurable: true,
          get: function () { return cookieDesc.get.call(document); },
          set: function (value) {
            if (cookieBypass) return cookieDesc.set.call(document, value);
            var cookieName = String(value || "").split("=")[0].trim();
            if (cookieBlocked(cookieName)) return;
            return cookieDesc.set.call(document, value);
          }
        });
      }

      function sweep() {
        neutralizeTree(document);
        purgeCookies();
      }
      sweep();
      if (typeof queueMicrotask === "function") queueMicrotask(sweep);
      setTimeout(sweep, 0);
      document.addEventListener("DOMContentLoaded", sweep);

      function allowedCategory(cat) {
        return cat === "necessary" || Boolean(allowed[cat]);
      }

      function reviveScript(node, src, inline, wasAsync, moduleType) {
        var next = nativeCreate("script");
        var type = moduleType || (node && node.getAttribute("data-cg-type")) || "";
        if (type && type.toLowerCase() !== "text/plain") next.type = type;
        if (node) {
          var attrs = node.attributes;
          for (var a = 0; a < attrs.length; a++) {
            var name = attrs[a].name.toLowerCase();
            if (name === "src" || name === "type" || name === "data-cg-src" || name === "data-cg-blocked" || name === "data-cg-type" || name === "data-cg-async" || name === "data-consent-gate") continue;
            if (name === "id" || name === "nonce" || name === "crossorigin" || name === "integrity" || name === "referrerpolicy" || name === "nomodule" || name.indexOf("data-") === 0) {
              nativeSetAttr.call(next, attrs[a].name, attrs[a].value);
            }
          }
        }
        var asyncFlag = wasAsync || (node && (node.hasAttribute("async") || node.getAttribute("data-cg-async") === "1"));
        if (src) {
          next.async = Boolean(asyncFlag);
          next.src = replaySrc(src);
        } else if (inline) {
          next.text = inline;
        }
        if (node && node.parentNode) {
          node.parentNode.insertBefore(next, node);
          node.parentNode.removeChild(node);
        } else {
          (document.head || document.body).appendChild(next);
        }
        return src || "";
      }

      window.__CONSENTGATE_BLOCKER__ = {
        release: function (categories) {
          allowed.statistics = Boolean(categories.statistics) || policies.statistics === "without_consent";
          allowed.marketing = Boolean(categories.marketing) || policies.marketing === "without_consent";
          allowed.preferences = Boolean(categories.preferences) || policies.preferences === "without_consent";
          bypass = true;
          var seen = {};
          var i;
          var widgetInlines = [];
          var widgetLibs = 0;
          var widgetDone = 0;
          var releasedKinds = [];
          // Unwrap placeholders BEFORE loading widget SDKs. Instagram embed.js
          // auto-runs process() on load; if the blockquote is still inside
          // .cg-embed-wrap, the SDK inserts an iframe sibling that a naive
          // unwrap would destroy, and it strips class "instagram-media".
          var unwrappedKinds = releaseWidgetPlaceholders();
          for (i = 0; i < unwrappedKinds.length; i++) releasedKinds.push(unwrappedKinds[i]);

          function finishRelease() {
            var n;
            for (n = 0; n < widgetInlines.length; n++) {
              var item = widgetInlines[n];
              reviveScript(item.node, "", item.inline);
              if (item.kind) releasedKinds.push(item.kind);
            }
            widgetInlines = [];
            var nodes = document.querySelectorAll("[data-cg-blocked]");
            for (n = 0; n < nodes.length; n++) {
              var blocked = nodes[n];
              if (blocked.nodeName === "SCRIPT") continue;
              var blockedCat = blocked.getAttribute("data-cg-blocked");
              if (!allowedCategory(blockedCat)) continue;
              if (blocked.nodeName === "LINK") {
                var href = blocked.getAttribute("data-cg-href");
                if (href) nativeSetAttr.call(blocked, "href", href);
                blocked.removeAttribute("data-cg-blocked");
              } else if (blocked.getAttribute("data-cg-widget")) {
                continue;
              } else {
                var mediaSrc = blocked.getAttribute("data-cg-src");
                if (!mediaSrc) continue;
                // Unwrap before restoring src so moving the node does not abort the load.
                if (blocked.nodeName === "IFRAME" || blocked.nodeName === "IMG") unwrapMapsPlaceholder(blocked);
                nativeSetAttr.call(blocked, "src", mediaSrc);
                blocked.removeAttribute("data-cg-blocked");
                clearWidgetMarks(blocked);
              }
            }
            // Catch any wraps that appeared while libraries loaded.
            var late = releaseWidgetPlaceholders();
            for (n = 0; n < late.length; n++) releasedKinds.push(late[n]);
            bypass = false;
            // Single reinit pass after libraries are loaded and DOM is unwrapped.
            runReinitHooks(releasedKinds);
          }

          function onWidgetLibSettled() {
            widgetDone += 1;
            if (widgetDone >= widgetLibs) finishRelease();
          }

          function reviveWidgetLibrary(node, src) {
            var next = nativeCreate("script");
            var type = (node && node.getAttribute("data-cg-type")) || "";
            if (type && type.toLowerCase() !== "text/plain") next.type = type;
            if (node) {
              var attrs = node.attributes;
              for (var a = 0; a < attrs.length; a++) {
                var name = attrs[a].name.toLowerCase();
                if (name === "src" || name === "type" || name === "data-cg-src" || name === "data-cg-blocked" || name === "data-cg-type" || name === "data-cg-async" || name === "data-consent-gate" || name === "data-cg-kind") continue;
                if (name === "id" || name === "nonce" || name === "crossorigin" || name === "integrity" || name === "referrerpolicy" || name === "nomodule" || name.indexOf("data-") === 0) {
                  nativeSetAttr.call(next, attrs[a].name, attrs[a].value);
                }
              }
            }
            next.async = false;
            next.onload = onWidgetLibSettled;
            next.onerror = onWidgetLibSettled;
            next.src = replaySrc(src);
            if (node && node.parentNode) {
              node.parentNode.insertBefore(next, node);
              node.parentNode.removeChild(node);
            } else {
              (document.head || document.body).appendChild(next);
            }
          }

          function isWidgetLibraryNode(node, src) {
            if (isOwnConsentGateScript(node)) return false;
            if (widgetForLibrarySrc(src)) return true;
            var kind = node && node.getAttribute && node.getAttribute("data-cg-kind");
            return Boolean(kind && widgetById(kind));
          }

          var scripts = document.querySelectorAll('script[data-cg-blocked], script[type="text/plain"][data-consent-gate]');
          for (i = 0; i < scripts.length; i++) {
            var node = scripts[i];
            if (isOwnConsentGateScript(node)) continue;
            var cat = node.getAttribute("data-cg-blocked") || node.getAttribute("data-consent-gate") || "";
            if (!allowedCategory(cat)) continue;
            var src = node.getAttribute("data-cg-src") || node.getAttribute("data-src") || node.getAttribute("src") || "";
            var inline = node.textContent || "";
            if (src && seen[src]) {
              if (node.parentNode) node.parentNode.removeChild(node);
              continue;
            }
            if (!src && !inline.trim()) continue;
            var inlineWidget = !src ? widgetForInline(inline, node) : null;
            if (inlineWidget) {
              widgetInlines.push({ node: node, inline: inline, kind: inlineWidget.id });
              continue;
            }
            if (src && isWidgetLibraryNode(node, src)) {
              widgetLibs += 1;
              seen[src] = true;
              var libKind = (node.getAttribute("data-cg-kind") || (widgetForLibrarySrc(src) || {}).id || "");
              if (libKind) releasedKinds.push(libKind);
              reviveWidgetLibrary(node, src);
              continue;
            }
            reviveScript(node, src, inline);
            if (src) seen[src] = true;
          }
          for (i = 0; i < pending.length; i++) {
            var item = pending[i];
            if (item.kind !== "script" || !allowedCategory(item.cat)) continue;
            if (item.src && seen[item.src]) continue;
            if (!item.src && !item.text) continue;
            if (item.node && isOwnConsentGateScript(item.node)) continue;
            var pendingInlineWidget = !item.src ? widgetForInline(item.text, item.node || null) : null;
            if (pendingInlineWidget) {
              widgetInlines.push({ node: item.node || null, inline: item.text, kind: pendingInlineWidget.id });
              continue;
            }
            if (item.src && widgetForLibrarySrc(item.src)) {
              widgetLibs += 1;
              seen[item.src] = true;
              var pendingKind = (widgetForLibrarySrc(item.src) || {}).id || "";
              if (pendingKind) releasedKinds.push(pendingKind);
              reviveWidgetLibrary(item.node || null, item.src);
              continue;
            }
            var held = item.node;
            if (held && held.nodeName === "SCRIPT" && !held.parentNode) {
              if (item.type && item.type.toLowerCase() !== "text/plain") held.type = item.type;
              else held.removeAttribute("type");
              held.async = Boolean(item.async);
              held.removeAttribute("data-cg-blocked");
              if (item.src) held.src = replaySrc(item.src);
              else held.text = item.text;
              (document.head || document.body).appendChild(held);
            } else {
              reviveScript(null, item.src, item.text, item.async, item.type);
            }
            if (item.src) seen[item.src] = true;
          }
          pending = [];
          if (widgetLibs === 0) finishRelease();
        },
        purge: function (categories) {
          allowed.statistics = Boolean(categories && categories.statistics);
          allowed.marketing = Boolean(categories && categories.marketing);
          allowed.preferences = Boolean(categories && categories.preferences);
          purgeCookies();
        }
      };
    })();
