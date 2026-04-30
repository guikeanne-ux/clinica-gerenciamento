function getRoot() {
  let root = document.getElementById('toast-root');
  if (!root) {
    root = document.createElement('div');
    root.id = 'toast-root';
    document.body.appendChild(root);
  }
  return root;
}

function show(message, type = 'info', duration = 3500) {
  const root = getRoot();
  const el = document.createElement('div');
  el.className = `toast toast--${type}`;
  el.setAttribute('role', 'status');
  el.setAttribute('aria-live', type === 'error' ? 'assertive' : 'polite');
  el.innerHTML = `
    <span class="toast__text">${message}</span>
    <button class="toast__close" aria-label="Fechar">✕</button>`;
  root.appendChild(el);

  const close = () => {
    el.style.opacity = '0';
    el.style.transform = 'translateY(4px)';
    el.style.transition = 'all 0.18s ease';
    setTimeout(() => el.remove(), 200);
  };

  el.querySelector('.toast__close').addEventListener('click', close);
  if (duration > 0) setTimeout(close, duration);
  return close;
}

export const toast = {
  success: (msg, dur) => show(msg, 'success', dur),
  error:   (msg, dur) => show(msg, 'error', dur),
  warning: (msg, dur) => show(msg, 'warning', dur),
  info:    (msg, dur) => show(msg, 'info', dur),
};
