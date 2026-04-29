export function initFileInput() {
  document.querySelectorAll("[data-file-preview]").forEach((root) => {
    const input = root.querySelector("input[type='file']");
    const img = root.querySelector(".file-preview");
    input.addEventListener("change", () => {
      const file = input.files?.[0];
      if (!file || !file.type.startsWith("image/")) return;
      img.src = URL.createObjectURL(file);
      img.classList.remove("hidden");
    });
  });
}
