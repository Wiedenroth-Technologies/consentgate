/**
 * Minimal Instagram embed.js stub.
 * Mirrors real SDK behaviour:
 * - process() requires blockquote.instagram-media + data-instgrm-permalink
 * - strips instagram-media / adds instagram-media-registered
 * - inserts a sibling iframe (height starts at 0)
 * - resizes via postMessage MEASURE
 */
(function () {
  var processed = {};

  function permalinkOf(el) {
    var direct = el.getAttribute("data-instgrm-permalink");
    if (direct) return direct;
    var links = el.getElementsByTagName("a");
    for (var i = links.length - 1; i >= 0; i--) {
      var href = links[i].href || "";
      if (/instagram\.com\/p\//i.test(href)) return href;
    }
    return "";
  }

  function processOne(el) {
    if (!el || el.tagName !== "BLOCKQUOTE") return;
    if (!/\binstagram-media\b/.test(el.className || "")) return;
    var permalink = permalinkOf(el);
    if (!permalink) {
      window.__CG_IG_ERROR__ = "missing permalink";
      return;
    }
    var key = el.id || permalink;
    if (processed[key]) return;

    if (!el.id) el.id = "instagram-media-payload-stub-" + Object.keys(processed).length;
    var id = "instagram-embed-stub-" + Object.keys(processed).length;
    var frame = document.createElement("iframe");
    frame.className = el.className;
    frame.id = id;
    frame.title = "Instagram";
    frame.setAttribute("data-instgrm-payload-id", el.id);
    frame.setAttribute("height", "0");
    frame.setAttribute("frameBorder", "0");
    frame.style.cssText =
      (el.getAttribute("style") || "") +
      ";background:#fff;border:1px solid #dbdbdb;display:block;margin:0;min-width:326px;padding:0;position:absolute;";
    var src = permalink.replace(/\/?(\?.*|#|$)/, "/") + "embed/captioned/";
    frame.src = src;

    if (!el.parentNode) {
      window.__CG_IG_ERROR__ = "missing parent";
      return;
    }
    el.parentNode.insertBefore(frame, el);
    el.className = String(el.className || "")
      .replace(/\binstagram-media\b/g, " ")
      .replace(/\s+/g, " ")
      .trim();
    el.className += (el.className ? " " : "") + "instagram-media-registered";
    processed[key] = true;
    window.__CG_IG_PROCESSED__ = true;

    // Simulate Instagram MEASURE postMessage resize after mount.
    window.setTimeout(function () {
      frame.style.position = "";
      frame.className += " instagram-media-rendered";
      frame.height = "480";
      frame.style.height = "480px";
      if (el.parentNode) el.parentNode.removeChild(el);
      window.__CG_IG_HEIGHT__ = 480;
      try {
        window.postMessage(
          JSON.stringify({ type: "MEASURE", details: { height: 480 } }),
          "*"
        );
      } catch (e) {}
    }, 30);
  }

  function process() {
    window.__CG_IG_PROCESS_CALLS__ = (window.__CG_IG_PROCESS_CALLS__ || 0) + 1;
    var nodes = document.getElementsByClassName("instagram-media");
    for (var i = 0; i < nodes.length; i++) processOne(nodes[i]);
  }

  window.instgrm = window.instgrm || {};
  window.instgrm.Embeds = window.instgrm.Embeds || {};
  window.instgrm.Embeds.process = process;
  // Auto-init like the real SDK once the script loads.
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", process);
  } else {
    process();
  }
})();
