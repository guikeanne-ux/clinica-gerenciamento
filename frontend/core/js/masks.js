const digits = (v) => v.replace(/\D/g, "");
const maskMap = {
  cpf: (v) => digits(v).slice(0, 11).replace(/(\d{3})(\d)/, "$1.$2").replace(/(\d{3})(\d)/, "$1.$2").replace(/(\d{3})(\d{1,2})$/, "$1-$2"),
  cnpj: (v) => digits(v).slice(0, 14).replace(/(\d{2})(\d)/, "$1.$2").replace(/(\d{3})(\d)/, "$1.$2").replace(/(\d{3})(\d)/, "$1/$2").replace(/(\d{4})(\d{1,2})$/, "$1-$2"),
  phone: (v) => digits(v).slice(0, 11).replace(/(\d{2})(\d)/, "($1) $2").replace(/(\d{5})(\d{1,4})$/, "$1-$2"),
  date: (v) => digits(v).slice(0, 8).replace(/(\d{2})(\d)/, "$1/$2").replace(/(\d{2})(\d)/, "$1/$2"),
  cep: (v) => digits(v).slice(0, 8).replace(/(\d{5})(\d)/, "$1-$2"),
  money: (v) => {
    const c = digits(v);
    if (!c) return "";
    const n = (Number(c) / 100).toLocaleString("pt-BR", { style: "currency", currency: "BRL" });
    return n;
  },
};

export function initMasks() {
  document.querySelectorAll("[data-mask]").forEach((input) => {
    input.addEventListener("input", () => {
      const t = input.dataset.mask;
      if (maskMap[t]) input.value = maskMap[t](input.value);
    });
  });
}
