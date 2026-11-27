window.twttr = {
  widgets: {
    load: function () {
      window.__CG_TW_LOADED__ = true;
      var nodes = document.querySelectorAll("blockquote.twitter-tweet");
      for (var i = 0; i < nodes.length; i++) nodes[i].setAttribute("data-cg-tw-ready", "1");
    }
  }
};
