export function initTabs() {
  document.querySelectorAll("[data-tabs]").forEach((root) => {
    const tabs = root.querySelectorAll(".tab");
    tabs.forEach((tab) => tab.addEventListener("click", () => {
      tabs.forEach((t) => t.classList.remove("active"));
      document.querySelectorAll(".tab-panel").forEach((p) => p.classList.remove("active"));
      tab.classList.add("active");
      document.querySelector(tab.dataset.target)?.classList.add("active");
    }));
  });
}
