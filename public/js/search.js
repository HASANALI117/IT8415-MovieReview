(function () {
  const toggle = document.getElementById("searchToggle");
  const navBox = document.getElementById("navSearch");
  const input = document.getElementById("liveSearch");
  const dropdown = document.getElementById("searchDropdown");
  const spinner = document.getElementById("searchSpinner");
  if (!input || !dropdown) return;

  let debounceTimer = null;
  let controller = null;
  let focusIdx = -1;
  let items = [];

  // toggle inline search bar
  toggle?.addEventListener("click", () => {
    navBox.classList.toggle("open");
    if (navBox.classList.contains("open")) setTimeout(() => input.focus(), 50);
    else closeDropdown();
  });

  // debounced fetch
  input.addEventListener("input", () => {
    const q = input.value.trim();
    clearTimeout(debounceTimer);
    focusIdx = -1;
    if (q.length < 2) {
      closeDropdown();
      spinner.classList.remove("show");
      return;
    }
    debounceTimer = setTimeout(() => runSearch(q), 300);
  });

  async function runSearch(q) {
    if (controller) controller.abort();
    controller = new AbortController();
    spinner.classList.add("show");
    try {
      const res = await fetch(
        "/ajax/search.php?q=" + encodeURIComponent(q),
        { signal: controller.signal },
      );
      const data = await res.json();
      render(data);
    } catch (err) {
      if (err.name !== "AbortError") console.error(err);
    } finally {
      spinner.classList.remove("show");
    }
  }

  function render(data) {
    items = data || [];
    if (!items.length) {
      dropdown.innerHTML = '<div class="sr-empty">No results</div>';
      dropdown.classList.add("show");
      return;
    }
    dropdown.innerHTML = items
      .map((m, i) => {
        const title = escapeHtml(m.title);
        return `
        <a class="sr-item" data-idx="${i}" href="movie-detail.php?id=${m.id}">
          <img src="${escapeHtml(m.poster)}" alt=""
               onerror="this.onerror=null;this.replaceWith(Object.assign(document.createElement('div'),{className:'sr-thumb',style:'background:#3d7cf5'}))">
          <div>
            <div class="sr-title">${title}</div>
            <div class="sr-sub">${m.year} &middot; &#9733; ${Number(m.rating).toFixed(1)}</div>
          </div>
        </a>`;
      })
      .join("");
    dropdown.classList.add("show");
  }

  // keyboard nav
  input.addEventListener("keydown", (e) => {
    const rows = Array.from(dropdown.querySelectorAll(".sr-item"));
    if (e.key === "ArrowDown") {
      e.preventDefault();
      focusIdx = Math.min(focusIdx + 1, rows.length - 1);
      highlight(rows);
    } else if (e.key === "ArrowUp") {
      e.preventDefault();
      focusIdx = Math.max(focusIdx - 1, 0);
      highlight(rows);
    } else if (e.key === "Enter") {
      if (focusIdx >= 0 && rows[focusIdx]) {
        e.preventDefault();
        window.location = rows[focusIdx].href;
      }
    } else if (e.key === "Escape") {
      closeDropdown();
    }
  });

  function highlight(rows) {
    rows.forEach((r, i) => r.classList.toggle("focused", i === focusIdx));
    rows[focusIdx]?.scrollIntoView({ block: "nearest" });
  }

  document.addEventListener("click", (e) => {
    if (!navBox?.contains(e.target) && !toggle?.contains(e.target))
      closeDropdown();
  });
  function closeDropdown() {
    dropdown.classList.remove("show");
    dropdown.innerHTML = "";
  }

  function escapeHtml(s) {
    return String(s).replace(
      /[&<>"']/g,
      (c) =>
        ({
          "&": "&amp;",
          "<": "&lt;",
          ">": "&gt;",
          '"': "&quot;",
          "'": "&#39;",
        })[c],
    );
  }
})();
