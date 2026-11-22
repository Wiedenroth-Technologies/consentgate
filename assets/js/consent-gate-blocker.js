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
        try { node.type = "text/plain"; } catch (e) {}
        try { node.removeAttribute("src"); } catch (e2) {}
        try { if (srcDesc && srcDesc.set) srcDesc.set.call(node, ""); } catch (e3) {}
        return !node.parentNode;
      }

      function embedKind(src) {
        var value = String(src || "");
        if (/maps\.apple\.com|apple-mapkit\.com|embed\.apple\.com\/maps|embed\.apple\.com\/[^?#]*\?[^#]*type=map/i.test(value)) return "apple";
        if (/linexo\.com|linexo-site-connect|linexo-siteconnect/i.test(value)) return "linexo";
        if (/maps\.googleapis\.com|maps\.gstatic\.com|maps\.google\.|maps-api-ssl\.google\.|maps\.app\.goo\.gl|goo\.gl\/maps|google\.[a-z.]+\/maps/i.test(value)) {
          return "maps";
        }
        if (/youtube(?:-nocookie)?\.com|youtu\.be/i.test(value)) return "youtube";
        if (/player\.vimeo\.com|vimeo\.com\/video/i.test(value)) return "vimeo";
        return "";
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
        if (key === "apple_maps") return "apple";
        if (key === "google_maps") return "maps";
        if (key === "linexo") return "linexo";
        if (key === "youtube") return "youtube";
        if (key === "vimeo") return "vimeo";
        return embedKind(src);
      }

      function markEmbedKind(el, src) {
        if (!el || el.getAttribute("data-cg-kind")) return;
        var kind = embedKind(src);
        if (!kind) return;
        var key = kind === "apple" ? "apple_maps" : kind === "maps" ? "google_maps" : kind;
        nativeSetAttr.call(el, "data-cg-kind", key);
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
        var h = iframe.getAttribute("height") || iframe.style.height;
        if (h) wrap.style.minHeight = /px|%|vh|rem/.test(String(h)) ? String(h) : String(h) + "px";
        var parent = iframe.parentNode;
        if (!parent) return;
        parent.insertBefore(wrap, iframe);
        wrap.appendChild(iframe);
        var locale = embedLocale();
        var ui = (config.ui && config.ui[locale]) || {};
        var cat = (config.embedGates && config.embedGates[kind === "apple" ? "apple_maps" : kind === "linexo" ? "linexo" : kind === "maps" ? "google_maps" : ""]) || "marketing";
        var hint = ui.embedBody || (locale === "en" ? "Allow the matching category to show this content." : "Erlauben Sie die passende Kategorie, um diesen Inhalt zu sehen.");
        var copy = {
          maps: {
            title: ui.mapsTitle || (locale === "en" ? "Google Maps is blocked" : "Google Maps ist blockiert"),
            body: cat === "marketing" ? (ui.mapsBody || (locale === "en" ? "Allow marketing cookies to show the map." : "Marketing-Cookies erlauben, um die Karte zu sehen.")) : hint
          },
          apple: {
            title: ui.appleTitle || (locale === "en" ? "Apple Maps is blocked" : "Apple Maps ist blockiert"),
            body: hint
          },
          linexo: {
            title: ui.linexoTitle || (locale === "en" ? "Linexo is blocked" : "Linexo ist blockiert"),
            body: hint
          },
          youtube: {
            title: ui.youtubeTitle || (locale === "en" ? "YouTube is blocked" : "YouTube ist blockiert"),
            body: ui.youtubeBody || (locale === "en" ? "Allow marketing cookies to play this video." : "Marketing-Cookies erlauben, um das Video zu sehen.")
          },
          vimeo: {
            title: ui.vimeoTitle || (locale === "en" ? "Vimeo is blocked" : "Vimeo ist blockiert"),
            body: ui.vimeoBody || (locale === "en" ? "Allow marketing cookies to play this video." : "Marketing-Cookies erlauben, um das Video zu sehen.")
          }
        };
        var text = copy[kind] || copy.maps;
        var overlay = nativeCreate("div");
        overlay.className = "cg-embed-overlay";
        overlay.setAttribute("role", "region");
        var title = nativeCreate("strong");
        title.textContent = text.title;
        var body = nativeCreate("p");
        body.textContent = text.body;
        var btn = nativeCreate("button");
        btn.type = "button";
        btn.className = "cg-embed-open";
        btn.textContent = ui.openSettings || (locale === "en" ? "Open cookie settings" : "Cookie-Einstellungen öffnen");
        btn.addEventListener("click", function () {
          if (window.ConsentGate && typeof window.ConsentGate.open === "function") {
            window.ConsentGate.open();
          }
        });
        overlay.appendChild(title);
        overlay.appendChild(body);
        overlay.appendChild(btn);
        wrap.appendChild(overlay);
      }

      function unwrapMapsPlaceholder(iframe) {
        var wrap = iframe.parentNode;
        if (!wrap || !wrap.classList || !wrap.classList.contains("cg-embed-wrap")) return;
        var parent = wrap.parentNode;
        if (!parent) return;
        parent.insertBefore(iframe, wrap);
        parent.removeChild(wrap);
      }

      function linexoGate() {
        var gates = config.embedGates || {};
        if (Object.prototype.hasOwnProperty.call(gates, "linexo")) return gates.linexo || "";
        return "marketing";
      }

      function blockWidgets(root) {
        var gate = linexoGate();
        if (!gate || allowed[gate]) return;
        var list = [];
        if (root && root.nodeType === 1 && root.id === "linexo-site-connect") list.push(root);
        var scope = root && root.querySelectorAll ? root : document;
        if (scope.querySelectorAll) {
          var found = scope.querySelectorAll("#linexo-site-connect");
          for (var i = 0; i < found.length; i++) list.push(found[i]);
        }
        for (var n = 0; n < list.length; n++) {
          var el = list[n];
          if (el.getAttribute("data-cg-widget")) continue;
          var parent = el.parentNode;
          if (!parent) continue;
          if (parent.classList && parent.classList.contains("cg-embed-wrap")) continue;
          el.setAttribute("data-cg-widget", "linexo");
          el.setAttribute("data-cg-blocked", gate);
          var wrap = nativeCreate("div");
          wrap.className = "cg-embed-wrap cg-embed-wrap--widget";
          parent.insertBefore(wrap, el);
          wrap.appendChild(el);
          var locale = embedLocale();
          var ui = (config.ui && config.ui[locale]) || {};
          var overlay = nativeCreate("div");
          overlay.className = "cg-embed-overlay";
          overlay.setAttribute("role", "region");
          var title = nativeCreate("strong");
          title.textContent = ui.linexoTitle || (locale === "en" ? "Linexo is blocked" : "Linexo ist blockiert");
          var body = nativeCreate("p");
          body.textContent = ui.embedBody || (locale === "en" ? "Allow the matching category to show this content." : "Erlauben Sie die passende Kategorie, um diesen Inhalt zu sehen.");
          var btn = nativeCreate("button");
          btn.type = "button";
          btn.className = "cg-embed-open";
          btn.textContent = ui.openSettings || (locale === "en" ? "Open cookie settings" : "Cookie-Einstellungen öffnen");
          btn.addEventListener("click", function () {
            if (window.ConsentGate && typeof window.ConsentGate.open === "function") window.ConsentGate.open();
          });
          overlay.appendChild(title);
          overlay.appendChild(body);
          overlay.appendChild(btn);
          wrap.appendChild(overlay);
        }
      }

      function releaseWidgets() {
        var gate = linexoGate();
        if (gate && !allowed[gate]) return;
        var nodes = document.querySelectorAll("[data-cg-widget='linexo']");
        for (var i = 0; i < nodes.length; i++) {
          var el = nodes[i];
          el.removeAttribute("data-cg-blocked");
          el.removeAttribute("data-cg-widget");
          unwrapMapsPlaceholder(el);
        }
      }

      function blockMedia(el) {
        if (el.getAttribute("data-cg-blocked")) {
          if (el.nodeName === "IFRAME" || el.nodeName === "IMG") wrapMapsPlaceholder(el);
          return;
        }
        var src = el.getAttribute("src") || el.getAttribute("data-src") || el.getAttribute("data-lazy-src") || "";
        var rule = matchingRule(src);
        if (!rule) return;
        remember(src, rule.cat, el.nodeName.toLowerCase());
        nativeSetAttr.call(el, "data-cg-blocked", rule.cat);
        nativeSetAttr.call(el, "data-cg-src", src);
        markEmbedKind(el, src);
        el.removeAttribute("src");
        el.removeAttribute("data-src");
        el.removeAttribute("data-lazy-src");
        if (el.nodeName === "IFRAME" || el.nodeName === "IMG") wrapMapsPlaceholder(el);
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

      Node.prototype.appendChild = function (node) {
        if (node && node.nodeName === "SCRIPT") {
          if (neutralize(node)) return node;
          var appendSrc = node.getAttribute("src") || "";
          if (appendSrc && trackerHost(appendSrc)) nativeSetAttr.call(node, "src", replaySrc(appendSrc));
        }
        return nativeAppend.call(this, node);
      };
      Node.prototype.insertBefore = function (node, ref) {
        if (node && node.nodeName === "SCRIPT") {
          if (neutralize(node)) return node;
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
          var scripts = document.querySelectorAll('script[data-cg-blocked], script[type="text/plain"][data-consent-gate]');
          for (i = 0; i < scripts.length; i++) {
            var node = scripts[i];
            var cat = node.getAttribute("data-cg-blocked") || node.getAttribute("data-consent-gate") || "";
            if (!allowedCategory(cat)) continue;
            var src = node.getAttribute("data-cg-src") || node.getAttribute("data-src") || node.getAttribute("src") || "";
            var inline = node.textContent || "";
            if (src && seen[src]) {
              if (node.parentNode) node.parentNode.removeChild(node);
              continue;
            }
            if (!src && !inline.trim()) continue;
            reviveScript(node, src, inline);
            if (src) seen[src] = true;
          }
          for (i = 0; i < pending.length; i++) {
            var item = pending[i];
            if (item.kind !== "script" || !allowedCategory(item.cat)) continue;
            if (item.src && seen[item.src]) continue;
            if (!item.src && !item.text) continue;
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
          var nodes = document.querySelectorAll("[data-cg-blocked]");
          for (i = 0; i < nodes.length; i++) {
            var blocked = nodes[i];
            if (blocked.nodeName === "SCRIPT") continue;
            var blockedCat = blocked.getAttribute("data-cg-blocked");
            if (!allowedCategory(blockedCat)) continue;
            if (blocked.nodeName === "LINK") {
              var href = blocked.getAttribute("data-cg-href");
              if (href) nativeSetAttr.call(blocked, "href", href);
              blocked.removeAttribute("data-cg-blocked");
            } else {
              var mediaSrc = blocked.getAttribute("data-cg-src");
              if (!mediaSrc) continue;
              nativeSetAttr.call(blocked, "src", mediaSrc);
              blocked.removeAttribute("data-cg-blocked");
              if (blocked.nodeName === "IFRAME" || blocked.nodeName === "IMG") unwrapMapsPlaceholder(blocked);
            }
          }
          bypass = false;
          releaseWidgets();
        },
        purge: function (categories) {
          allowed.statistics = Boolean(categories && categories.statistics);
          allowed.marketing = Boolean(categories && categories.marketing);
          allowed.preferences = Boolean(categories && categories.preferences);
          purgeCookies();
        }
      };
    })();
