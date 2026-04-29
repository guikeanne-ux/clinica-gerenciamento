import { initMasks } from "../js/masks.js";

function normDate(v) {
  const n = v.replace(/\D/g, "").slice(0, 12);
  if (n.length <= 8) return n.replace(/(\d{2})(\d)/, "$1/$2").replace(/(\d{2})(\d)/, "$1/$2");
  return n.slice(0, 8).replace(/(\d{2})(\d)/, "$1/$2").replace(/(\d{2})(\d)/, "$1/$2") + " " + n.slice(8).replace(/(\d{2})(\d)/, "$1:$2");
}

export function initDatepickers() {
  const hasFlatpickr = typeof window.flatpickr === "function";
  const locale = window.flatpickr?.l10ns?.pt ?? "default";

  document.querySelectorAll("[data-datepicker]").forEach((i) => {
    i.setAttribute("inputmode", "numeric");
    if (hasFlatpickr) {
      window.flatpickr(i, {
        dateFormat: "d/m/Y",
        locale,
        allowInput: true
      });
      return;
    }
    i.dataset.mask = "date";
  });

  document.querySelectorAll("[data-datetimepicker]").forEach((i) => {
    i.setAttribute("inputmode", "numeric");
    if (hasFlatpickr) {
      window.flatpickr(i, {
        enableTime: true,
        time_24hr: true,
        dateFormat: "d/m/Y H:i",
        locale,
        allowInput: true
      });
      return;
    }
    i.addEventListener("input", () => {
      i.value = normDate(i.value);
    });
  });

  if (!hasFlatpickr) initMasks();
}
