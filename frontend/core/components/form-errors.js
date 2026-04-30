export function clearFieldErrors(form) {
  if (!form) return;
  form.querySelectorAll('.field.has-error').forEach((field) => field.classList.remove('has-error'));
  form.querySelectorAll('.field-error').forEach((el) => el.remove());
}

export function applyFieldErrors(form, errors = []) {
  if (!form || !Array.isArray(errors)) return;

  errors.forEach((err) => {
    const fieldName = err?.field;
    const message = err?.message;
    if (!fieldName || !message) return;

    const input = form.querySelector(`[name="${fieldName}"]`);
    if (!input) return;

    const wrapper = input.closest('.field');
    if (!wrapper) return;

    wrapper.classList.add('has-error');

    const msg = document.createElement('div');
    msg.className = 'field-error';
    msg.textContent = message;
    wrapper.appendChild(msg);
  });
}
