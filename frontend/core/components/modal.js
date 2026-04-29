export function initModal() {
  document.querySelectorAll("[data-open-modal]").forEach((b) => b.addEventListener("click", () => document.querySelector(b.dataset.openModal)?.classList.add("open")));
  document.querySelectorAll("[data-close-modal]").forEach((b) => b.addEventListener("click", () => b.closest(".modal")?.classList.remove("open")));
  document.querySelectorAll(".modal").forEach((m) => m.addEventListener("click", (e) => { if (e.target === m) m.classList.remove("open"); }));
}
