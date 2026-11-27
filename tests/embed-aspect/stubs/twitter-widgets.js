window.twttr = {
  widgets: {
    load: function () {
      window.__CG_TW_LOADED__ = true;
      var nodes = document.querySelectorAll("blockquote.twitter-tweet");
      for (var i = 0; i < nodes.length; i++) {
        nodes[i].setAttribute("data-cg-tw-ready", "1");
      }
      if (!document.getElementById("twitter-widget-0")) {
        var frame = document.createElement("iframe");
        frame.id = "twitter-widget-0";
        frame.title = "X helper";
        frame.width = "1";
        frame.height = "1";
        frame.src = "https://platform.twitter.com/widgets/widget_iframe.2.0.html?origin=https%3A%2F%2Fexample.com";
        document.body.appendChild(frame);
      }
    }
  }
};
