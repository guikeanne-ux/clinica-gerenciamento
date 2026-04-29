export function initDropdown() {
  document.querySelectorAll("[data-dropdown]").forEach((root) => {
    const btn = root.querySelector("button");
    const menu = root.querySelector(".dropdown-menu");

    const placeMenu = () => {
      const r = btn.getBoundingClientRect();
      menu.classList.add("dropdown-floating");
      menu.style.top = `${r.bottom + 6}px`;
      menu.style.left = `${Math.max(8, r.right - 180)}px`;
    };

    btn.addEventListener("click", () => {
      const willOpen = !menu.classList.contains("open");
      document.querySelectorAll(".dropdown-menu.open").forEach((m) => m.classList.remove("open"));
      if (willOpen) {
        placeMenu();
        menu.classList.add("open");
      }
    });

    window.addEventListener("resize", () => menu.classList.remove("open"));
    window.addEventListener("scroll", () => menu.classList.remove("open"), true);
    document.addEventListener("click", (e) => {
      if (!root.contains(e.target) && !menu.contains(e.target)) menu.classList.remove("open");
    });
  });
}
