const patterns = {
  cpf: /^\d{3}\.\d{3}\.\d{3}-\d{2}$/,
  cnpj: /^\d{2}\.\d{3}\.\d{3}\/\d{4}-\d{2}$/,
  phone: /^\(\d{2}\) \d{4,5}-\d{4}$/,
  date: /^\d{2}\/\d{2}\/\d{4}$/,
  cep: /^\d{5}-\d{3}$/,
};

export function validateRequired(input) {
  return input.value.trim().length > 0;
}

export function validateOptional(input) {
  if (!input.value.trim()) return true;
  const type = input.dataset.mask;
  return patterns[type] ? patterns[type].test(input.value) : true;
}

export function initValidators() {
  document.querySelectorAll("input[required], textarea[required]").forEach((el) => {
    el.addEventListener("blur", () => {
      el.style.borderColor = validateRequired(el) ? "var(--border)" : "var(--error)";
    });
  });

  document.querySelectorAll("input[data-mask]").forEach((el) => {
    el.addEventListener("blur", () => {
      el.style.borderColor = validateOptional(el) ? "var(--border)" : "var(--error)";
    });
  });
}
