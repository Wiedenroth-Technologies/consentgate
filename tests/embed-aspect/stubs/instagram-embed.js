window.instgrm = {
  Embeds: {
    process: function () {
      window.__CG_IG_PROCESSED__ = true;
      var nodes = document.querySelectorAll("blockquote.instagram-media");
      for (var i = 0; i < nodes.length; i++) {
        nodes[i].setAttribute("data-cg-ig-ready", "1");
        if (nodes[i].querySelector("iframe.instagram-media-iframe")) continue;
        var frame = document.createElement("iframe");
        frame.className = "instagram-media-iframe";
        frame.title = "Instagram";
        frame.width = "400";
        frame.height = "480";
        frame.src = "https://www.instagram.com/p/demo/embed/captioned/";
        nodes[i].appendChild(frame);
      }
    }
  }
};
