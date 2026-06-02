const FALLBACK = { tl: "23306b", tr: "3b2566", br: "14424f", bl: "2a1d4d" };

// UltraBlur ambient gradient — matches ultrablur_gradient() in includes/header.php.
function buildUltraBlurGradient(tl, tr, br, bl) {
  return `
    radial-gradient(ellipse 130% 130% at 0% 0%,     #${tl} 0%, transparent 78%),
    radial-gradient(ellipse 130% 130% at 100% 0%,   #${tr} 0%, transparent 78%),
    radial-gradient(ellipse 130% 130% at 100% 100%, #${br} 0%, transparent 78%),
    radial-gradient(ellipse 130% 130% at 0% 100%,   #${bl} 0%, transparent 78%)
  `;
}

// Cinematic hero: cross-fade through featured titles; the poster strip is the slide control.
(function phero() {
  const root = document.getElementById("phero");
  if (!root) return;
  const slides = Array.from(root.querySelectorAll(".phero-slide"));
  if (!slides.length) return;

  const bgCurrent = document.querySelector(".app-bg");
  const bgNext = document.querySelector(".app-bg-next");
  const strip = Array.from(root.querySelectorAll(".phero-card"));

  let index = 0;
  let timer = null;
  const INTERVAL = 6500;

  strip.forEach((item, i) => {
    item.addEventListener("click", () => {
      go(i);
      restart();
    });
  });

  function colorsOf(slide) {
    return {
      tl: slide.dataset.tl || FALLBACK.tl,
      tr: slide.dataset.tr || FALLBACK.tr,
      br: slide.dataset.br || FALLBACK.br,
      bl: slide.dataset.bl || FALLBACK.bl,
    };
  }

  function paintAmbient(slide) {
    if (!bgCurrent || !bgNext) return;
    const { tl, tr, br, bl } = colorsOf(slide);
    bgNext.style.background = buildUltraBlurGradient(tl, tr, br, bl);
    bgNext.style.opacity = "1";
    bgCurrent.style.opacity = "0";
    setTimeout(() => {
      bgCurrent.style.background = bgNext.style.background;
      bgCurrent.style.opacity = "1";
      bgNext.style.opacity = "0";
    }, 850);
  }

  function go(i) {
    slides[index].classList.remove("active");
    strip[index] && strip[index].classList.remove("active");
    index = (i + slides.length) % slides.length;
    slides[index].classList.add("active");
    strip[index] && strip[index].classList.add("active");
    paintAmbient(slides[index]);
  }

  const next = () => go(index + 1);
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

  root.addEventListener("mouseenter", stop);
  root.addEventListener("mouseleave", start);

  // init: first slide active + paint immediately (no fade)
  const c0 = colorsOf(slides[0]);
  if (bgCurrent) {
    bgCurrent.style.background = buildUltraBlurGradient(c0.tl, c0.tr, c0.br, c0.bl);
    bgCurrent.style.opacity = "1";
  }
  slides[0].classList.add("active");
  strip[0] && strip[0].classList.add("active");
  start();
})();
