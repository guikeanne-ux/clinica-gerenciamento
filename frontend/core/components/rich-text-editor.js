export function initRichTextEditor() {
  if (!window.Quill) return;

  document.querySelectorAll("[data-richtext-editor]").forEach((root) => {
    const editorEl = root.querySelector(".rich-editor");
    const outputEl = root.querySelector(".rich-output");

    const quill = new window.Quill(editorEl, {
      theme: "snow",
      placeholder: "Digite o conteúdo...",
      modules: {
        toolbar: [
          [{ header: [1, 2, 3, false] }],
          ["bold", "italic", "underline", "strike"],
          [{ color: [] }, { background: [] }],
          [{ list: "ordered" }, { list: "bullet" }],
          [{ align: [] }],
          ["blockquote", "code-block"],
          ["clean"]
        ]
      }
    });

    quill.on("text-change", () => {
      outputEl.value = quill.root.innerHTML;
    });

    outputEl.value = quill.root.innerHTML;
  });
}
