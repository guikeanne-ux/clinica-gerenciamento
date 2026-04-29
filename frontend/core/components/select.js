export function initSelect() {
  document.querySelectorAll("[data-select]").forEach((root) => {
    const trigger = root.querySelector(".select-trigger");
    const menu = root.querySelector(".select-menu");
    const hidden = root.querySelector("input[type='hidden']");
    trigger.addEventListener("click", () => menu.classList.toggle("open"));
    menu.querySelectorAll("li").forEach((li) => {
      li.addEventListener("click", () => {
        trigger.textContent = li.textContent;
        hidden.value = li.dataset.value;
        menu.classList.remove("open");
      });
    });
    document.addEventListener("click", (e) => { if (!root.contains(e.target)) menu.classList.remove("open"); });
  });
}
