export function pushToast(message) {
  const root = document.getElementById("toast-root");
  const el = document.createElement("div");
  el.className = "toast";
  el.textContent = message;
  root.appendChild(el);
  setTimeout(() => el.remove(), 2800);
}

export function initToastButtons() {
  document.querySelectorAll("[data-toast]").forEach((b) => b.addEventListener("click", () => pushToast(b.dataset.toast)));
}
