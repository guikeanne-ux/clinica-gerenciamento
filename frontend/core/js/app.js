import { initSelect } from "../components/select.js";
import { initMultiselect } from "../components/multiselect.js";
import { initAutocomplete } from "../components/autocomplete.js";
import { initFileInput } from "../components/file-input.js";
import { initRichTextEditor } from "../components/rich-text-editor.js";
import { initModal } from "../components/modal.js";
import { initToastButtons } from "../components/toast.js";
import { initDatepickers } from "../components/datepicker.js";
import { initTabs } from "../components/tabs.js";
import { initDropdown } from "../components/dropdown.js";
import { initValidators } from "./validators.js";

initSelect();
initMultiselect();
initAutocomplete();
initFileInput();
initRichTextEditor();
initModal();
initToastButtons();
initDatepickers();
initTabs();
initDropdown();
initValidators();

document.getElementById("toggle-loading")?.addEventListener("click", () => {
  const overlay = document.getElementById("loading-overlay");
  overlay.classList.remove("hidden");
  setTimeout(() => overlay.classList.add("hidden"), 1200);
});
