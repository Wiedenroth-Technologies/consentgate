(function () {
  var STORAGE_KEY = "consentgate.v1";
  var restored = window.__CONSENTGATE_RESTORED__ || null;

  function getBoot() {
    return window.__CONSENTGATE__ || {};
  }

  var defaults = {
    version: 1,
    locale: "de",
    position: "bottom",
    bannerSize: "normal",
    colors: {
      primary: "#111111",
      background: "#ffffff",
      text: "#1a1a1a",
      acceptBg: "#111111",
      acceptText: "#ffffff",
      rejectBg: "#ffffff",
      rejectText: "#111111",
    },
    logoUrl: null,
    logoHeight: 28,
    logoAlign: "left",
    privacyUrl: "/datenschutz",
    showReopen: true,
    showBranding: false,
    regionMode: "always",
    urlPassthrough: true,
    purposePolicies: {
      statistics: "with_consent",
      marketing: "with_consent",
      preferences: "with_consent",
    },
    analyticsFollowsMarketing: false,
    googleAdvancedConsentMode: false,
    brandUrl: "https://wiedenroth.tech/software/consentgate",
    brandIcon: null,
    googleIds: { ads: [], analytics: [], tags: [] },
    services: [],
    categoriesEnabled: {
      necessary: true,
      statistics: true,
      marketing: true,
      preferences: false,
    },
    texts: {
      title: "Wir respektieren Ihre Privatsphäre",
      description:
        "Wir verwenden Cookies und ähnliche Technologien, um die Website bereitzustellen (notwendig) sowie – nur mit Ihrer ausdrücklichen Einwilligung – für Statistik und Marketing. Nicht notwendige Cookies werden erst nach Ihrer Zustimmung geladen.",
      acceptAll: "Alle akzeptieren",
      rejectAll: "Alle ablehnen",
      customize: "Einstellungen",
      save: "Auswahl speichern",
      close: "Schließen",
      privacyLink: "Datenschutzerklärung",
      necessaryTitle: "Notwendig",
      necessaryDescription:
        "Erforderlich für Grundfunktionen, Sicherheit und das Speichern Ihrer Einwilligung.",
      statisticsTitle: "Statistik",
      statisticsDescription:
        "Hilft uns zu verstehen, wie die Website genutzt wird. Wird erst nach Einwilligung geladen.",
      marketingTitle: "Marketing",
      marketingDescription:
        "Wird für Werbung und Conversion-Messung verwendet. Wird erst nach Einwilligung geladen.",
      preferencesTitle: "Präferenzen",
      preferencesDescription:
        "Speichert Ihre Einstellungen. Wird erst nach Einwilligung geladen.",
      reopenLabel: "Cookie-Einstellungen",
    },
  };

  var defaultTextsEn = {
    title: "We respect your privacy",
    description:
      "We use cookies and similar technologies to run this website (necessary) and — only with your explicit consent — for statistics and marketing. Non-essential cookies load only after you opt in.",
    acceptAll: "Accept all",
    rejectAll: "Reject all",
    customize: "Customize",
    save: "Save selection",
    close: "Close",
    privacyLink: "Privacy policy",
    necessaryTitle: "Necessary",
    necessaryDescription:
      "Required for basic website functions, security, and storing your consent.",
    statisticsTitle: "Statistics",
    statisticsDescription:
      "Helps us understand how the website is used. Loaded only after consent.",
    marketingTitle: "Marketing",
    marketingDescription:
      "Used for advertising and conversion measurement. Loaded only after consent.",
    preferencesTitle: "Preferences",
    preferencesDescription:
      "Stores your settings. Loaded only after consent.",
    reopenLabel: "Cookie settings",
  };

  var config = merge(defaults, {});
  var privacyReady = false;
  var privacyQueue = [];
  var ui = { root: null, customize: false };

  function readBootConfig(raw) {
    if (!raw) return {};
    if (typeof raw === "string") {
      try {
        return JSON.parse(raw);
      } catch (e) {
        return {};
      }
    }
    return raw;
  }

  function normalizePosition(value) {
    var next = String(value || "")
      .trim()
      .toLowerCase();
    if (
      next === "corner-left" ||
      next === "bottom-left" ||
      next === "bottom_left" ||
      next === "left" ||
      next.indexOf("links") !== -1
    ) {
      return "corner-left";
    }
    if (
      next === "corner" ||
      next === "bottom-right" ||
      next === "bottom_right" ||
      next === "right" ||
      next.indexOf("ecke") !== -1 ||
      next.indexOf("rechts") !== -1
    ) {
      return "corner";
    }
    if (
      next === "modal" ||
      next === "center" ||
      next === "centre" ||
      next.indexOf("modal") !== -1 ||
      next.indexOf("zentriert") !== -1
    ) {
      return "modal";
    }
    return "bottom";
  }

  function bannerSizeClass(value) {
    var size = String(value || "normal");
    if (size !== "compact" && size !== "wide") size = "normal";
    return "cg-size-" + size;
  }

  function merge(base, extra) {
    var out = {};
    var key;
    for (key in base) out[key] = base[key];
    if (!extra) return out;
    for (key in extra) {
      if (
        extra[key] &&
        typeof extra[key] === "object" &&
        !Array.isArray(extra[key])
      ) {
        out[key] = merge(base[key] || {}, extra[key]);
      } else if (extra[key] !== undefined && extra[key] !== null) {
        out[key] = extra[key];
      }
    }
    return out;
  }

  function visitorId() {
    try {
      var existing = localStorage.getItem("consentgate.vid");
      if (existing) return existing;
      var id =
        (crypto.randomUUID && crypto.randomUUID()) ||
        "cg-" + Math.random().toString(36).slice(2) + Date.now().toString(36);
      localStorage.setItem("consentgate.vid", id);
      return id;
    } catch (e) {
      return "cg-session";
    }
  }

  function gtagProxy() {
    window.dataLayer = window.dataLayer || [];
    if (typeof window.gtag !== "function") {
      window.gtag = function () {
        window.dataLayer.push(arguments);
      };
    }
    return window.gtag;
  }

  function consentUpdate(categories) {
    var granted = function (flag) {
      return flag ? "granted" : "denied";
    };
    gtagProxy()("consent", "update", {
      ad_storage: granted(categories.marketing),
      ad_user_data: granted(categories.marketing),
      ad_personalization: granted(categories.marketing),
      analytics_storage: granted(categories.statistics),
      functionality_storage: "granted",
      personalization_storage: granted(categories.preferences),
      security_storage: "granted",
    });
    gtagProxy()("set", "url_passthrough", Boolean(config.urlPassthrough));
    gtagProxy()("set", "ads_data_redaction", !categories.marketing);
  }

  function googleIdList() {
    var ids = config.googleIds || {};
    return [].concat(ids.tags || [], ids.ads || [], ids.analytics || []);
  }

  function loadGtagLibrary(firstId) {
    if (!firstId) return;
    if (document.querySelector('script[src*="googletagmanager.com/gtag/js"]')) return;
    var script = document.createElement("script");
    script.async = true;
    script.src =
      "https://www.googletagmanager.com/gtag/js?id=" + encodeURIComponent(firstId);
    (document.head || document.documentElement).appendChild(script);
  }

  function activateGoogleMeasurement(categories) {
    var list = googleIdList();
    if (!list.length) return;
    var marketing = Boolean(categories.marketing);
    var statistics = Boolean(categories.statistics);
    if (!config.googleAdvancedConsentMode && !marketing && !statistics) return;
    loadGtagLibrary(list[0]);
    for (var i = 0; i < list.length; i++) {
      gtagProxy()("config", list[i], {
        allow_google_signals: marketing,
        allow_ad_personalization_signals: marketing,
        send_page_view: true,
      });
    }
  }

  function loadPrivacy(cb) {
    privacyReady = true;
    cb();
  }

  function setWpConsent(categories) {
    var map = {
      functional: "allow",
      statistics: categories.statistics ? "allow" : "deny",
      marketing: categories.marketing ? "allow" : "deny",
      preferences: categories.preferences ? "allow" : "deny",
    };
    try {
      window.wp_consent_type = window.wp_consent_type || "optin";
      if (typeof window.wp_set_consent === "function") {
        Object.keys(map).forEach(function (key) {
          window.wp_set_consent(key, map[key]);
        });
      }
      document.dispatchEvent(
        new CustomEvent("wp_consent_update", { detail: map }),
      );
    } catch (e) {}
  }

  function setShopifyConsent(categories) {
    setWpConsent(categories);
  }

  function activateScripts(categories) {
    if (window.__CONSENTGATE_BLOCKER__) return;
    var nodes = document.querySelectorAll(
      'script[type="text/plain"][data-consent-gate]',
    );
    Array.prototype.forEach.call(nodes, function (node) {
      var category = node.getAttribute("data-consent-gate");
      if (category !== "necessary" && !categories[category]) return;
      var script = document.createElement("script");
      var src = node.getAttribute("data-cg-src") || node.getAttribute("data-src") || node.getAttribute("src") || "";
      if (src) {
        script.async = node.hasAttribute("async");
        script.src = src;
      } else script.text = node.textContent || "";
      var placement = node.getAttribute("data-placement") || "head";
      var parent =
        placement === "body" ? document.body : document.head || document.body;
      parent.appendChild(script);
      node.parentNode && node.parentNode.removeChild(node);
    });
  }

  function persist(record) {
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(record));
    } catch (e) {}
  }

  function logDecision(record) {
    var url = (getBoot().logUrl || "").replace(/\/$/, "");
    if (!url) return;
    var payload = {
      visitorId: visitorId(),
      decision: record.decision,
      categories: record.categories,
      bannerVersion: String(config.version || 1),
      locale: config.locale || "de",
      ipCountry: getBoot().ipCountry || null,
    };
    var headers = { "Content-Type": "application/json" };
    if (getBoot().restNonce) headers["X-WP-Nonce"] = getBoot().restNonce;
    try {
      fetch(url, {
        method: "POST",
        headers: headers,
        body: JSON.stringify(payload),
        keepalive: true,
        credentials: "same-origin",
      }).catch(function () {});
    } catch (e) {}
  }

  function applyConsent(categories, decision, options) {
    var record = {
      categories: {
        necessary: true,
        statistics: Boolean(categories.statistics),
        marketing: Boolean(categories.marketing),
        preferences: Boolean(categories.preferences),
      },
      decision: decision,
      version: config.version,
      urlPassthrough: config.urlPassthrough,
      at: Date.now(),
    };
    persist(record);
    consentUpdate(record.categories);
    activateGoogleMeasurement(record.categories);
    setShopifyConsent(record.categories);
    if (window.__CONSENTGATE_BLOCKER__) {
      window.__CONSENTGATE_BLOCKER__.release(record.categories);
      window.__CONSENTGATE_BLOCKER__.purge(record.categories);
    }
    activateScripts(record.categories);
    if (!options || !options.silent) logDecision(record);
    hideBanner();
    showReopen();
  }

  function acceptAll() {
    applyConsent(
      {
        necessary: true,
        statistics: Boolean(config.categoriesEnabled.statistics),
        marketing: Boolean(config.categoriesEnabled.marketing),
        preferences: Boolean(config.categoriesEnabled.preferences),
      },
      "accept_all",
    );
  }

  function rejectAll() {
    applyConsent(
      {
        necessary: true,
        statistics: false,
        marketing: false,
        preferences: false,
      },
      "reject_all",
    );
  }

  function saveCustom() {
    applyConsent(readToggles(), "custom");
  }

  function readToggles() {
    var root = ui.root;
    return {
      necessary: true,
      statistics: toggleValue(root, "statistics"),
      marketing: toggleValue(root, "marketing"),
      preferences: toggleValue(root, "preferences"),
    };
  }

  function toggleValue(root, name) {
    if (!config.categoriesEnabled[name]) return false;
    var input = root.querySelector('[data-cg-toggle="' + name + '"]');
    return Boolean(input && input.checked);
  }

  function el(tag, attrs, children) {
    var node = document.createElement(tag);
    Object.keys(attrs || {}).forEach(function (key) {
      if (key === "class") node.className = attrs[key];
      else if (key === "style") node.setAttribute("style", attrs[key]);
      else if (key.indexOf("on") === 0) node.addEventListener(key.slice(2), attrs[key]);
      else if (attrs[key] !== undefined && attrs[key] !== null) {
        node.setAttribute(key, attrs[key]);
      }
    });
    (children || []).forEach(function (child) {
      if (typeof child === "string") node.appendChild(document.createTextNode(child));
      else if (child) node.appendChild(child);
    });
    return node;
  }

  var LOGO_FILE = {
    wordpress_essential: "logo-placeholder.svg",
    woocommerce_essential: "logo-placeholder.svg",
    matomo: "logo-placeholder.svg",
    hotjar: "logo-hotjar.svg",
    shopify_essential: "logo-shopify.svg",
    google_ads: "logo-google-ads.svg",
    google_analytics: "logo-google-analytics.svg",
    gtm: "logo-google-tag-manager.svg",
    google_maps: "logo-google-maps.svg",
    meta_pixel: "logo-meta.svg",
    tiktok: "logo-tiktok.svg",
    pinterest: "logo-pinterest.svg",
    bing: "logo-bing.svg",
    clarity: "logo-clarity.svg",
    klaviyo: "logo-klaviyo.svg",
    snapchat: "logo-snapchat.svg",
    linkedin: "logo-linkedin.svg",
    adcell: "logo-adcell.svg",
    _placeholder: "logo-placeholder.svg",
  };

  function assetBase() {
    if (getBoot().assetBase) return getBoot().assetBase;
    var nodes = document.querySelectorAll("script[src], link[rel='stylesheet'][href]");
    for (var i = 0; i < nodes.length; i++) {
      var src =
        nodes[i].getAttribute("src") || nodes[i].getAttribute("href") || "";
      if (src.indexOf("consent-gate") === -1) continue;
      return src.replace(/consent-gate\.(?:js|css)(?:\?.*)?$/, "");
    }
    return "";
  }

  function fileFromName(name) {
    var value = String(name || "").toLowerCase();
    if (value.indexOf("adcell") !== -1) return LOGO_FILE.adcell;
    if (value.indexOf("google ads") !== -1) return LOGO_FILE.google_ads;
    if (value.indexOf("google maps") !== -1 || value.indexOf("maps") !== -1 && value.indexOf("google") !== -1) {
      return LOGO_FILE.google_maps;
    }
    if (value.indexOf("analytics") !== -1) return LOGO_FILE.google_analytics;
    if (value.indexOf("tag manager") !== -1 || value.indexOf("gtm") !== -1) {
      return LOGO_FILE.gtm;
    }
    if (value.indexOf("meta") !== -1 || value.indexOf("facebook") !== -1) {
      return LOGO_FILE.meta_pixel;
    }
    if (value.indexOf("tiktok") !== -1) return LOGO_FILE.tiktok;
    if (value.indexOf("pinterest") !== -1) return LOGO_FILE.pinterest;
    if (value.indexOf("bing") !== -1) return LOGO_FILE.bing;
    if (value.indexOf("clarity") !== -1) return LOGO_FILE.clarity;
    if (value.indexOf("klaviyo") !== -1) return LOGO_FILE.klaviyo;
    if (value.indexOf("snap") !== -1) return LOGO_FILE.snapchat;
    if (value.indexOf("linkedin") !== -1) return LOGO_FILE.linkedin;
    if (value.indexOf("woocommerce") !== -1) return LOGO_FILE.woocommerce_essential;
    if (value.indexOf("wordpress") !== -1) return LOGO_FILE.wordpress_essential;
    if (value.indexOf("matomo") !== -1 || value.indexOf("piwik") !== -1) return LOGO_FILE.matomo;
    if (value.indexOf("hotjar") !== -1) return LOGO_FILE.hotjar;
    if (value.indexOf("shopify") !== -1) return LOGO_FILE.shopify_essential;
    return "";
  }

  function logoUrl(item) {
    if (!item) return "";
    var logos = getBoot().logos || {};
    if (item.id && logos[item.id]) return logos[item.id];
    var file =
      item.file || LOGO_FILE[item.id] || fileFromName(item.name) || LOGO_FILE._placeholder;
    var base = assetBase();
    if (file && base) return base + file;
    return "";
  }

  function servicesFor(category) {
    return (config.services || []).filter(function (item) {
      return item && item.cat === category;
    });
  }

  function categoryRow(name, title, description, locked, checked) {
    var input = el("input", {
      type: "checkbox",
      "data-cg-toggle": name,
      checked: checked ? "" : undefined,
      disabled: locked ? "" : undefined,
    });
    if (checked) input.checked = true;
    if (locked) input.disabled = true;

    var items = servicesFor(name);
    var uiCopy = uiStrings();
    var emptyLabel = uiCopy.emptyCategory || (config.locale === "en" ? "No services in this category." : "Keine Dienste in dieser Kategorie.");
    var listChildren = items.length
      ? items.map(function (item) {
          var src = logoUrl(item);
          var kids = [
            el("img", {
              class: "cg-service-icon",
              src: src,
              alt: "",
              width: "16",
              height: "16",
            }),
            el("span", { class: "cg-service-name" }, [serviceName(item)]),
          ];
          return el("div", { class: "cg-service" }, kids);
        })
      : [el("div", { class: "cg-service cg-service--empty" }, [emptyLabel])];
    var list = el("div", { class: "cg-cat-services cg-hidden" }, listChildren);

    var toggle = el(
      "button",
      {
        type: "button",
        class: "cg-cat-toggle",
        "aria-expanded": "false",
      },
      [
        el("span", { class: "cg-cat-chevron", "aria-hidden": "true" }),
        el("span", { class: "cg-cat-title" }, [title]),
      ],
    );
    toggle.addEventListener("click", function (event) {
      event.preventDefault();
      var open = toggle.getAttribute("aria-expanded") === "true";
      toggle.setAttribute("aria-expanded", open ? "false" : "true");
      toggle.classList.toggle("cg-cat-toggle--open", !open);
      if (open) list.classList.add("cg-hidden");
      else list.classList.remove("cg-hidden");
    });

    return el("div", { class: "cg-cat" }, [
      el("div", { class: "cg-cat-head" }, [
        toggle,
        el("label", { class: "cg-switch" }, [input, el("span")]),
      ]),
      el("div", { class: "cg-cat-desc" }, [description]),
      list,
    ]);
  }

  function renderBanner(existing, options) {
    destroy();
    var dismissible = Boolean(options && options.dismissible);
    var texts = config.texts;
    var colors = config.colors;
    var style =
      "--cg-bg:" +
      colors.background +
      ";--cg-fg:" +
      colors.text +
      ";--cg-accept-bg:" +
      colors.acceptBg +
      ";--cg-accept-fg:" +
      colors.acceptText +
      ";--cg-reject-bg:" +
      colors.rejectBg +
      ";--cg-reject-fg:" +
      colors.rejectText +
      ";--cg-primary:" +
      colors.primary +
      ";--cg-logo-height:" +
      logoHeightPx() +
      "px";

    var position = normalizePosition(config.position);
    var root = el("div", {
      class: "cg-root cg-root--" + position + " " + bannerSizeClass(config.bannerSize),
      style: style,
      id: "consentgate-root",
    });

    if (position === "modal") {
      root.appendChild(el("div", { class: "cg-overlay" }));
    }

    var cats = el("div", {
      class: "cg-cats" + (ui.customize ? "" : " cg-hidden"),
    });
    cats.appendChild(
      categoryRow(
        "necessary",
        texts.necessaryTitle,
        texts.necessaryDescription,
        true,
        true,
      ),
    );
    if (config.categoriesEnabled.statistics) {
      cats.appendChild(
        categoryRow(
          "statistics",
          texts.statisticsTitle,
          texts.statisticsDescription,
          false,
          Boolean(existing && existing.statistics),
        ),
      );
    }
    if (config.categoriesEnabled.marketing) {
      cats.appendChild(
        categoryRow(
          "marketing",
          texts.marketingTitle,
          texts.marketingDescription,
          false,
          Boolean(existing && existing.marketing),
        ),
      );
    }
    if (config.categoriesEnabled.preferences) {
      cats.appendChild(
        categoryRow(
          "preferences",
          texts.preferencesTitle,
          texts.preferencesDescription,
          false,
          Boolean(existing && existing.preferences),
        ),
      );
    }

    var actions = el("div", { class: "cg-actions" }, [
      el(
        "button",
        { class: "cg-btn cg-btn--accept", type: "button", onclick: acceptAll },
        [texts.acceptAll],
      ),
      el(
        "button",
        { class: "cg-btn cg-btn--reject", type: "button", onclick: rejectAll },
        [texts.rejectAll],
      ),
      el(
        "button",
        {
          class: "cg-btn cg-btn--ghost",
          type: "button",
          onclick: function () {
            if (ui.customize) saveCustom();
            else {
              ui.customize = true;
              renderBanner(existing || readToggles(), { dismissible: dismissible });
            }
          },
        },
        [ui.customize ? texts.save : texts.customize],
      ),
    ]);

    var panelChildren = [];
    if (config.logoUrl) {
      var logo = el("img", {
        class: "cg-logo",
        src: config.logoUrl,
        alt: "",
      });
      logo.style.setProperty("height", logoHeightPx() + "px", "important");
      logo.style.setProperty("width", "auto", "important");
      logo.style.setProperty("max-width", "100%", "important");
      logo.style.setProperty("max-height", "none", "important");
      panelChildren.push(
        el("div", { class: "cg-logo-wrap cg-logo-wrap--" + logoAlign() }, [logo]),
      );
    }
    panelChildren.push(
      el("p", { class: "cg-title", id: "cg-title", role: "heading", "aria-level": "2" }, [
        texts.title,
      ]),
    );
    panelChildren.push(el("p", { class: "cg-desc" }, [texts.description]));
    if (config.privacyUrl) {
      panelChildren.push(
        el(
          "a",
          { class: "cg-privacy", href: config.privacyUrl },
          [texts.privacyLink],
        ),
      );
    }
    panelChildren.push(cats);
    panelChildren.push(actions);
    var brand = brandLink();
    if (brand) panelChildren.push(brand);

    var panel = el(
      "div",
      {
        class: "cg-panel cg-panel--" + position,
        role: "dialog",
        "aria-modal": "true",
        "aria-labelledby": "cg-title",
      },
      [el("div", { class: "cg-panel-inner" }, panelChildren)],
    );
    root.appendChild(panel);

    document.body.appendChild(root);
    ui.root = root;
    hideReopen();
    bindDialogA11y(root, panel, dismissible);
  }

  function focusables(root) {
    return Array.prototype.slice.call(
      root.querySelectorAll(
        'button:not([disabled]), a[href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])',
      ),
    ).filter(function (node) {
      return node.offsetParent !== null || node === document.activeElement;
    });
  }

  function bindDialogA11y(root, panel, dismissible) {
    var accept = root.querySelector(".cg-btn--accept");
    if (accept && typeof accept.focus === "function") {
      try {
        accept.focus();
      } catch (e) {}
    }
    root.addEventListener("keydown", function (event) {
      if (event.key === "Escape" && dismissible) {
        event.preventDefault();
        hideBanner();
        showReopen();
        return;
      }
      if (event.key !== "Tab") return;
      var list = focusables(panel);
      if (!list.length) return;
      var first = list[0];
      var last = list[list.length - 1];
      if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
      } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
      }
    });
  }

  function showReopen() {
    if (config.showReopen === false) return;
    if (document.getElementById("consentgate-reopen")) return;
    var btn = el(
      "button",
      {
        id: "consentgate-reopen",
        class:
          "cg-reopen" +
          (config.position === "corner" ? " cg-reopen--right" : ""),
        type: "button",
        style: "--cg-primary:" + config.colors.primary,
        "aria-label": config.texts.reopenLabel,
        onclick: function () {
          openBanner();
        },
      },
      [],
    );
    btn.innerHTML =
      '<svg viewBox="0 0 24 24" width="24" height="24" aria-hidden="true"><path fill="currentColor" d="M21.3 11.1a1.1 1.1 0 0 0-1.2-.9 2.7 2.7 0 0 1-2.6-2.6 1.1 1.1 0 0 0-1.2-1 3.5 3.5 0 0 1-3.3-3.4A1.1 1.1 0 0 0 11.8 2 10 10 0 1 0 22 12.4a1.1 1.1 0 0 0-.7-1.3zM8.3 9.5a1.4 1.4 0 1 1 0-2.8 1.4 1.4 0 0 1 0 2.8zm1.7 6.3a1.4 1.4 0 1 1 0-2.8 1.4 1.4 0 0 1 0 2.8zm5.6-1.9a1.2 1.2 0 1 1 0-2.4 1.2 1.2 0 0 1 0 2.4zM11.9 12a1 1 0 1 1 0-2 1 1 0 0 1 0 2z"/></svg>';
    document.body.appendChild(btn);
  }

  function hideReopen() {
    var btn = document.getElementById("consentgate-reopen");
    if (btn) btn.parentNode.removeChild(btn);
  }

  function brandUrl() {
    return config.brandUrl || "https://wiedenroth.tech/software/consentgate";
  }

  function logoHeightPx() {
    var height = parseInt(config.logoHeight, 10);
    if (!height || height < 16) height = 28;
    if (height > 200) height = 200;
    return height;
  }

  function logoAlign() {
    return config.logoAlign === "center" ? "center" : "left";
  }

  function brandMark() {
    var markup = String(config.brandIconSvg || "");
    if (!markup) {
      markup =
        '<svg class="cg-brand-mark" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 512 512" aria-hidden="true" focusable="false"><path fill="#1C2542" d="M256 72s-86 28-160 48v152c0 96 70 146 160 166 90-20 160-70 160-166V120C342 100 256 72 256 72z"/><path fill="#F9873E" d="M216 217v-35a40 40 0 0 1 80 0v35"/><rect x="196" y="242" width="120" height="90" rx="18" fill="#F9873E"/><circle cx="256" cy="282" r="11" fill="#1C2542"/></svg>';
    }
    var holder = document.createElement("template");
    holder.innerHTML = markup.trim();
    var svg = holder.content.querySelector("svg");
    if (!svg) return null;
    svg.classList.add("cg-brand-mark");
    svg.setAttribute("aria-hidden", "true");
    svg.setAttribute("focusable", "false");
    svg.setAttribute("width", "16");
    svg.setAttribute("height", "16");
    return svg;
  }

  function brandLink() {
    if (!config.showBranding) return null;
    var kids = [];
    var icon = brandMark();
    if (icon) kids.push(icon);
    kids.push(el("span", {}, ["ConsentGate"]));
    return el(
      "a",
      {
        class: "cg-brand",
        href: brandUrl(),
        target: "_blank",
        rel: "noopener noreferrer",
        "aria-label": "ConsentGate",
      },
      kids,
    );
  }

  function hideBanner() {
    destroy();
  }

  function destroy() {
    var root = document.getElementById("consentgate-root");
    if (root) root.parentNode.removeChild(root);
    ui.root = null;
  }

  function readStored() {
    if (restored) return restored;
    try {
      var raw = localStorage.getItem(STORAGE_KEY);
      return raw ? JSON.parse(raw) : null;
    } catch (e) {
      return null;
    }
  }

  function storedVersionOk(stored) {
    if (!stored || !stored.categories) return false;
    return Number(stored.version || 0) === Number(config.version || 1);
  }

  function shouldShow(stored, done) {
    if (storedVersionOk(stored)) return done(false);
    if (config.regionMode === "eu") {
      var boot = getBoot();
      if (boot.visitorInEu === false) return done(false);
    }
    done(true);
  }

  function preGrantedCategories() {
    var policies = config.purposePolicies || {};
    return {
      necessary: true,
      statistics: policies.statistics === "without_consent",
      marketing: policies.marketing === "without_consent",
      preferences: policies.preferences === "without_consent",
    };
  }

  function start() {
    var stored = readStored();
    var bootNow = getBoot();
    var fresh = storedVersionOk(stored);
    if (
      !stored &&
      config.regionMode === "eu" &&
      bootNow.visitorInEu === false
    ) {
      applyConsent(
        {
          necessary: true,
          statistics: Boolean(config.categoriesEnabled.statistics),
          marketing: Boolean(config.categoriesEnabled.marketing),
          preferences: Boolean(config.categoriesEnabled.preferences),
        },
        "accept_all",
        { silent: true }
      );
      showReopen();
      return;
    }
    if (fresh) {
      applyConsent(stored.categories, stored.decision || "custom", { silent: true });
    } else {
      var pre = preGrantedCategories();
      if (pre.statistics || pre.marketing || pre.preferences) {
        consentUpdate(pre);
        setShopifyConsent(pre);
        if (window.__CONSENTGATE_BLOCKER__) {
          window.__CONSENTGATE_BLOCKER__.release(pre);
        }
        activateScripts(pre);
      }
    }
    shouldShow(stored, function (show) {
      if (show) {
        ui.customize = false;
        renderBanner(stored && stored.categories, { dismissible: false });
      } else {
        showReopen();
        if (window.location.hash === "#consentgate") {
          openBanner();
        }
      }
    });
  }

  function openBanner() {
    ui.customize = true;
    var stored = readStored();
    renderBanner((stored || {}).categories, { dismissible: storedVersionOk(stored) });
  }

  function isOpenHref(href) {
    if (!href) return false;
    var raw = String(href);
    if (raw === "#consentgate" || raw === "/#consentgate") return true;
    try {
      var url = new URL(raw, window.location.href);
      return url.hash === "#consentgate";
    } catch (e) {
      return raw.indexOf("#consentgate") !== -1;
    }
  }

  function bindOpenLinks() {
    document.addEventListener(
      "click",
      function (event) {
        var target = event.target;
        if (!target || !target.closest) return;
        var link = target.closest("a");
        if (!link || !isOpenHref(link.getAttribute("href"))) return;
        event.preventDefault();
        event.stopPropagation();
        openBanner();
      },
      true
    );
    window.addEventListener("hashchange", function () {
      if (window.location.hash === "#consentgate") {
        openBanner();
      }
    });
  }

  window.ConsentGate = {
    open: openBanner,
    acceptAll: acceptAll,
    rejectAll: rejectAll,
  };

  function languageTag(value) {
    var raw = String(value || "").toLowerCase().replace("_", "-");
    if (raw.indexOf("en") === 0) return "en";
    if (raw.indexOf("de") === 0) return "de";
    return "";
  }

  function detectVisitorLocale(cfg) {
    var boot = getBoot();
    var mode = (cfg && cfg.languageMode) || boot.languageMode || "auto";
    if (mode === "de" || mode === "en") return mode;
    var html = languageTag(document.documentElement && document.documentElement.lang);
    if (html) return html;
    var site = languageTag(boot.shopLocale || (cfg && cfg.locale));
    return site || "en";
  }

  function uiStrings() {
    var pack = (config.ui && config.ui[config.locale]) || {};
    return pack;
  }

  function serviceName(item) {
    if (item && item.names && item.names[config.locale]) return item.names[config.locale];
    return (item && item.name) || "";
  }

  function applyConfig(raw) {
    config = merge(defaults, readBootConfig(raw));
    config.position = normalizePosition(config.position);
    var loc = detectVisitorLocale(config);
    var pack =
      (config.textsByLocale && (config.textsByLocale[loc] || config.textsByLocale.de)) ||
      null;
    if (pack) {
      config.texts = merge(loc === "en" ? defaultTextsEn : defaults.texts, pack);
    } else if (loc === "en") {
      config.texts = defaultTextsEn;
    }
    config.locale = loc;
  }

  function whenReady(fn) {
    if (document.body) fn();
    else document.addEventListener("DOMContentLoaded", fn);
  }

  function bootAndStart() {
    bindOpenLinks();
    var boot = getBoot();
    if (boot.config) {
      applyConfig(boot.config);
    }
    whenReady(start);
  }

  bootAndStart();
})();
