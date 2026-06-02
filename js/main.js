const FALLBACK = { tl: "b0bec5", tr: "90a4ae", br: "78909c", bl: "607d8b" };

// UltraBlur ambient gradient
function buildUltraBlurGradient(tl, tr, br, bl) {
  return `
    radial-gradient(ellipse 80% 80% at top left,    #${tl}bb 0%, transparent 65%),
    radial-gradient(ellipse 80% 80% at top right,   #${tr}bb 0%, transparent 65%),
    radial-gradient(ellipse 80% 80% at bottom right,#${br}bb 0%, transparent 65%),
    radial-gradient(ellipse 80% 80% at bottom left, #${bl}bb 0%, transparent 65%)
  `;
}

(function heroSlider() {
  const track = document.getElementById("heroTrack");
  if (!track) return;
  const slides = Array.from(track.querySelectorAll(".hero-slide"));
  if (!slides.length) return;

  const blurCurrent = document.querySelector(".hero-bg-blur");
  const blurNext = document.querySelector(".hero-bg-blur-next");
  const dotsWrap = document.getElementById("heroDots");
  const prevBtn = document.getElementById("heroPrev");
  const nextBtn = document.getElementById("heroNext");

  let index = 0;
  let timer = null;
  const INTERVAL = 5000;

  // dots
  slides.forEach((_, i) => {
    const b = document.createElement("button");
    b.setAttribute("aria-label", "Slide " + (i + 1));
    b.addEventListener("click", () => {
      go(i);
      restart();
    });
    dotsWrap.appendChild(b);
  });
  const dots = Array.from(dotsWrap.children);

  function colorsOf(slide) {
    return {
      tl: slide.dataset.tl || FALLBACK.tl,
      tr: slide.dataset.tr || FALLBACK.tr,
      br: slide.dataset.br || FALLBACK.br,
      bl: slide.dataset.bl || FALLBACK.bl,
    };
  }

  function transitionUltrablur(slide) {
    const { tl, tr, br, bl } = colorsOf(slide);
    blurNext.style.background = buildUltraBlurGradient(tl, tr, br, bl);
    blurNext.style.opacity = "1";
    blurCurrent.style.opacity = "0";
    setTimeout(() => {
      blurCurrent.style.background = blurNext.style.background;
      blurCurrent.style.opacity = "1";
      blurNext.style.opacity = "0";
    }, 900);
  }

  function go(i) {
    index = (i + slides.length) % slides.length;
    const offset = slides[index].offsetLeft;
    track.style.transform = `translateX(-${offset}px)`;
    dots.forEach((d, di) => d.classList.toggle("active", di === index));
    transitionUltrablur(slides[index]);
  }

  const next = () => go(index + 1);
  const prev = () => go(index - 1);

  function start() {
    timer = setInterval(next, INTERVAL);
  }
  function stop() {
    clearInterval(timer);
  }
  function restart() {
    stop();
    start();
  }

  nextBtn?.addEventListener("click", () => {
    next();
    restart();
  });
  prevBtn?.addEventListener("click", () => {
    prev();
    restart();
  });

  const hero = document.getElementById("hero");
  hero.addEventListener("mouseenter", stop);
  hero.addEventListener("mouseleave", start);
  window.addEventListener("resize", () => go(index));

  // init: paint ambient immediately (no crossfade on first)
  const c0 = colorsOf(slides[0]);
  blurCurrent.style.background = buildUltraBlurGradient(
    c0.tl,
    c0.tr,
    c0.br,
    c0.bl,
  );
  blurCurrent.style.opacity = "1";
  go(0);
  start();
})();

// Genre filter tabs
(function genreFilter() {
  const tabs = document.getElementById("genreTabs");
  const grid = document.getElementById("movieGrid");
  if (!tabs || !grid) return;

  const label = document.getElementById("sectionLabel");
  const empty = document.getElementById("emptyGenre");
  const cards = Array.from(grid.querySelectorAll("[data-genre]"));

  tabs.addEventListener("click", (e) => {
    const btn = e.target.closest(".pill");
    if (!btn) return;
    const genre = btn.dataset.genre;

    tabs
      .querySelectorAll(".pill")
      .forEach((p) => p.classList.toggle("active", p === btn));

    let shown = 0;
    cards.forEach((card) => {
      const match = genre === "All" || card.dataset.genre === genre;
      card.style.display = match ? "" : "none";
      if (match) shown++;
    });

    label.textContent =
      genre === "All" ? "Trending Now" : "Trending in " + genre;
    empty.style.display = shown === 0 ? "block" : "none";
  });
})();
