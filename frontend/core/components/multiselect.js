export function initMultiselect() {
  document.querySelectorAll("[data-multiselect]").forEach((root) => {
    const search = root.querySelector(".multi-search");
    const menu = root.querySelector(".multi-menu");
    const tags = root.querySelector(".tags");
    const selected = new Map();
    const options = Array.from(menu.querySelectorAll("li"));
    options.forEach((li) => {
      li.dataset.label = li.textContent;
    });

    const renderTags = () => {
      tags.innerHTML = "";
      selected.forEach((label, value) => {
        const tag = document.createElement("span");
        tag.className = "tag";
        tag.innerHTML = `${label} <button type='button' aria-label='Remover' data-remove='${value}'>×</button>`;
        tags.appendChild(tag);
      });
      tags.querySelectorAll("button[data-remove]").forEach((b) => b.addEventListener("click", () => {
        selected.delete(b.dataset.remove);
        renderTags();
        renderOptions();
      }));
    };

    const renderOptions = () => {
      options.forEach((li) => {
        const isSelected = selected.has(li.dataset.value);
        li.style.opacity = isSelected ? "0.45" : "1";
        li.style.pointerEvents = isSelected ? "none" : "auto";
        li.textContent = isSelected ? `${li.dataset.label} ✓` : li.dataset.label;
      });
    };

    search.addEventListener("focus", () => menu.classList.add("open"));
    search.addEventListener("input", () => {
      const q = search.value.toLowerCase();
      menu.querySelectorAll("li").forEach((li) => { li.style.display = li.textContent.toLowerCase().includes(q) ? "block" : "none"; });
    });

    options.forEach((li) => li.addEventListener("click", () => {
      selected.set(li.dataset.value, li.dataset.label);
      search.value = "";
      renderTags();
      renderOptions();
      menu.classList.remove("open");
    }));

    document.addEventListener("click", (e) => { if (!root.contains(e.target)) menu.classList.remove("open"); });
    renderOptions();
  });
}
