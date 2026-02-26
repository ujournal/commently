(function () {
  document.documentElement.addEventListener("click", function (e) {
    var nav = document.querySelector(".nav");
    var toggle = e.target && e.target.closest(".nav-toggle");
    var drawer = nav && nav.querySelector("turbo-frame:first-of-type .nav-list");
    var clickedDrawer = drawer && drawer.contains(e.target);
    var clickedToggle = toggle && toggle.contains(e.target);

    if (toggle && nav) {
      e.preventDefault();
      var open = nav.classList.toggle("is-open");
      toggle.setAttribute("aria-expanded", open);
      return;
    }

    if (nav && nav.classList.contains("is-open") && !clickedDrawer && !clickedToggle) {
      nav.classList.remove("is-open");
      var t = nav.querySelector(".nav-toggle");
      if (t) t.setAttribute("aria-expanded", "false");
    }
  });

  document.documentElement.addEventListener("turbo:before-visit", function () {
    var nav = document.querySelector(".nav");
    var toggle = nav && nav.querySelector(".nav-toggle");
    if (nav) nav.classList.remove("is-open");
    if (toggle) toggle.setAttribute("aria-expanded", "false");
  });
})();
