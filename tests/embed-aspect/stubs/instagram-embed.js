window.instgrm = {
  Embeds: {
    process: function () {
      window.__CG_IG_PROCESSED__ = true;
      var nodes = document.querySelectorAll("blockquote.instagram-media");
      for (var i = 0; i < nodes.length; i++) nodes[i].setAttribute("data-cg-ig-ready", "1");
    }
  }
};
