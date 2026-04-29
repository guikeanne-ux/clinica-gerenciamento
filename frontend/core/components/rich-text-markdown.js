function parse(md) {
  return md.replace(/\*\*(.*?)\*\*/g, "<strong>$1</strong>").replace(/\*(.*?)\*/g, "<em>$1</em>").replace(/\n/g, "<br>");
}

export function initRichText() {
  document.querySelectorAll("[data-richtext]").forEach((root) => {
    const src = root.querySelector(".rich-source");
    const preview = root.querySelector(".rich-preview");
    root.querySelectorAll("[data-md]").forEach((b) => b.addEventListener("click", () => {
      const token = b.dataset.md === "bold" ? "**texto**" : "*texto*";
      src.value += src.value ? ` ${token}` : token;
      preview.innerHTML = parse(src.value);
    }));
    src.addEventListener("input", () => { preview.innerHTML = parse(src.value); });
  });
}
