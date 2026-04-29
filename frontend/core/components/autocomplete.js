const items = ["São Paulo", "Santos", "Sorocaba", "Salvador", "Curitiba", "Campinas"];
export function initAutocomplete() {
  document.querySelectorAll("[data-autocomplete]").forEach((root) => {
    const input = root.querySelector(".auto-input");
    const menu = root.querySelector(".auto-menu");
    input.addEventListener("input", () => {
      const q = input.value.toLowerCase();
      const filtered = items.filter((i) => i.toLowerCase().includes(q));
      menu.innerHTML = filtered.map((i) => `<li>${i}</li>`).join("");
      menu.classList.toggle("open", filtered.length > 0 && q.length > 0);
      menu.querySelectorAll("li").forEach((li) => li.addEventListener("click", () => { input.value = li.textContent; menu.classList.remove("open"); }));
    });
    document.addEventListener("click", (e) => { if (!root.contains(e.target)) menu.classList.remove("open"); });
  });
}
