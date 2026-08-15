(function () {
  "use strict";

  var root = document.querySelector(".wp-captcha-shield-settings");
  if (!root) return;

  var tabs = Array.prototype.slice.call(
    root.querySelectorAll("[data-wpcs-tab]")
  );
  var panels = Array.prototype.slice.call(
    root.querySelectorAll("[data-wpcs-tab-panel]")
  );

  if (!tabs.length || !panels.length) return;

  function activate(tabName, updateHash) {
    tabs.forEach(function (tab) {
      var active = tab.getAttribute("data-wpcs-tab") === tabName;
      tab.classList.toggle("nav-tab-active", active);
      tab.setAttribute("aria-selected", active ? "true" : "false");
      tab.setAttribute("tabindex", active ? "0" : "-1");
    });

    panels.forEach(function (panel) {
      var active = panel.getAttribute("data-wpcs-tab-panel") === tabName;
      panel.classList.toggle("is-active", active);
    });

    if (updateHash && window.history && window.history.replaceState) {
      window.history.replaceState(null, "", "#" + tabName);
    }
  }

  function availableTab(tabName) {
    return tabs.some(function (tab) {
      return tab.getAttribute("data-wpcs-tab") === tabName;
    });
  }

  root.classList.add("wpcs-tabs-ready");

  var initial = window.location.hash.replace("#", "");
  activate(availableTab(initial) ? initial : "general", false);

  tabs.forEach(function (tab, index) {
    tab.addEventListener("click", function () {
      activate(tab.getAttribute("data-wpcs-tab"), true);
    });

    tab.addEventListener("keydown", function (event) {
      var targetIndex = null;

      if (event.key === "ArrowRight") targetIndex = (index + 1) % tabs.length;
      if (event.key === "ArrowLeft") {
        targetIndex = (index - 1 + tabs.length) % tabs.length;
      }
      if (event.key === "Home") targetIndex = 0;
      if (event.key === "End") targetIndex = tabs.length - 1;

      if (targetIndex === null) return;

      event.preventDefault();
      tabs[targetIndex].focus();
      activate(tabs[targetIndex].getAttribute("data-wpcs-tab"), true);
    });
  });
})();
