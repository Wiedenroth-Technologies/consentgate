window.tiktokEmbed = {
  lib: {
    render: function () {
      window.__CG_TT_RENDERED__ = true;
      var nodes = document.querySelectorAll("blockquote.tiktok-embed");
      for (var i = 0; i < nodes.length; i++) {
        nodes[i].setAttribute("data-cg-tt-ready", "1");
        if (nodes[i].querySelector("iframe")) continue;
        var frame = document.createElement("iframe");
        frame.title = "TikTok";
        frame.width = "325";
        frame.height = "580";
        frame.src = "https://www.tiktok.com/embed/v2/7123456789";
        nodes[i].appendChild(frame);
      }
    }
  }
};
