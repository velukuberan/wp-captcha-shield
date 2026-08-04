(() => {
  const body = document.body;
  const menuButton = document.querySelector("[data-menu-toggle]");
  const mobileNav = document.getElementById("mobile-navigation");

  if (menuButton && mobileNav) {
    menuButton.addEventListener("click", () => {
      const open = menuButton.getAttribute("aria-expanded") === "true";
      menuButton.setAttribute("aria-expanded", String(!open));
      mobileNav.hidden = open;
    });
  }

  const themeButton = document.querySelector("[data-theme-toggle]");

  const applyTheme = (theme) => {
    document.documentElement.dataset.theme = theme;
    localStorage.setItem("wcs-docs-theme", theme);

    if (!themeButton) {
      return;
    }

    const nextTheme = theme === "dark" ? "light" : "dark";

    themeButton.setAttribute("aria-label", `Switch to ${nextTheme} theme`);
  };

  themeButton?.addEventListener("click", () => {
    const currentTheme =
      document.documentElement.dataset.theme === "light" ? "light" : "dark";

    applyTheme(currentTheme === "dark" ? "light" : "dark");
  });

  applyTheme(
    document.documentElement.dataset.theme === "light" ? "light" : "dark",
  );

  const dialog = document.getElementById("search-dialog");
  const input = document.getElementById("docs-search");
  const results = document.getElementById("search-results");
  let searchDocs = null;

  const openSearch = async () => {
    if (!dialog) return;
    dialog.hidden = false;
    body.style.overflow = "hidden";
    input?.focus();

    if (!searchDocs) {
      try {
        const response = await fetch(window.WCS_DOCS.searchIndex);
        const index = await response.json();
        searchDocs = index.docs || [];
      } catch {
        searchDocs = [];
      }
    }
  };

  const closeSearch = () => {
    if (!dialog) return;
    dialog.hidden = true;
    body.style.overflow = "";
    if (input) input.value = "";
    if (results) results.innerHTML = "";
  };

  document.querySelectorAll("[data-search-open]").forEach((button) => {
    button.addEventListener("click", openSearch);
  });

  document.querySelectorAll("[data-search-close]").forEach((button) => {
    button.addEventListener("click", closeSearch);
  });

  document.addEventListener("keydown", (event) => {
    if (
      event.key === "/" &&
      !["INPUT", "TEXTAREA"].includes(document.activeElement?.tagName)
    ) {
      event.preventDefault();
      openSearch();
    }
    if (event.key === "Escape" && dialog && !dialog.hidden) {
      closeSearch();
    }
  });

  input?.addEventListener("input", () => {
    const query = input.value.trim().toLowerCase();

    if (!query) {
      results.innerHTML = "";
      return;
    }

    const matches = (searchDocs || [])
      .filter((doc) => `${doc.title} ${doc.text}`.toLowerCase().includes(query))
      .slice(0, 8);

    if (!matches.length) {
      results.innerHTML =
        '<p class="search-empty">No matching documentation found.</p>';
      return;
    }

    results.innerHTML = matches
      .map((doc) => {
        const text = (doc.text || "").replace(/\s+/g, " ").trim().slice(0, 150);
        const base =
          window.WCS_DOCS.baseUrl === "." ? "" : window.WCS_DOCS.baseUrl;
        return `
        <a class="search-result" href="${base}/${doc.location}">
          <strong>${escapeHtml(doc.title)}</strong>
          <p>${escapeHtml(text)}${text.length === 150 ? "…" : ""}</p>
        </a>
      `;
      })
      .join("");
  });

  function escapeHtml(value) {
    return value
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");
  }
})();
