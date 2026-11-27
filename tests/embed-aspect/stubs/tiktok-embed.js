window.tiktokEmbed = {
  lib: {
    render: function () {
      window.__CG_TT_RENDERED__ = true;
      var nodes = document.querySelectorAll("blockquote.tiktok-embed");
      for (var i = 0; i < nodes.length; i++) nodes[i].setAttribute("data-cg-tt-ready", "1");
    }
  }
};
