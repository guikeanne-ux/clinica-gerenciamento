import { initMasks } from '../../core/js/masks.js';

const token = localStorage.getItem('token');
if (!token) window.location.href = '../auth/login.html';

initMasks();

const companyForm = document.getElementById('company-form');
const companyFeedback = document.getElementById('company-feedback');
const filesFeedback = document.getElementById('files-feedback');
const filesList = document.getElementById('files-list');

const api = async (url, method = 'GET', body = null) => {
  const res = await fetch(url, {
    method,
    headers: { 'Content-Type': 'application/json', Authorization: 'Bearer ' + token },
    body: body ? JSON.stringify(body) : null,
  });
  const isJson = (res.headers.get('content-type') || '').includes('application/json');
  const data = isJson ? await res.json() : null;
  return { res, data };
};

const setCompany = (data) => {
  Object.entries(data || {}).forEach(([k, v]) => {
    const field = companyForm.querySelector(`[name="${k}"]`);
    if (field) field.value = v ?? '';
  });
};

const readFileAsBase64 = (file) => new Promise((resolve, reject) => {
  const reader = new FileReader();
  reader.onload = () => resolve(String(reader.result).split(',')[1] || '');
  reader.onerror = reject;
  reader.readAsDataURL(file);
});

const loadCompany = async () => {
  const { res, data } = await api('/api/v1/company');
  if (res.ok) setCompany(data.data);
};

const loadFiles = async () => {
  const { res, data } = await api('/api/v1/files?related_module=company&related_entity_type=company');
  filesList.innerHTML = '';
  if (!res.ok) return;
  data.data.forEach((f) => {
    const card = document.createElement('article');
    card.className = 'card';
    card.innerHTML = `<h3>${f.original_name}</h3><p>${f.classification} · ${f.mime_type}</p>`;
    filesList.appendChild(card);
  });
};

document.getElementById('save-company').addEventListener('click', async () => {
  const payload = Object.fromEntries(new FormData(companyForm));
  const { res, data } = await api('/api/v1/company', 'PUT', payload);
  companyFeedback.innerHTML = res.ok ? '<div class="alert alert-success">Dados salvos.</div>' : `<div class="alert alert-error">${data?.message || 'Erro ao salvar.'}</div>`;
});

document.getElementById('upload-logos').addEventListener('click', async () => {
  const send = async (inputId, classification, previewId) => {
    const input = document.getElementById(inputId);
    const file = input.files?.[0];
    if (!file) return;
    const base64 = await readFileAsBase64(file);
    const payload = {
      original_name: file.name,
      mime_type: file.type,
      content_base64: base64,
      classification,
      related_module: 'company',
      related_entity_type: 'company',
      related_entity_uuid: null,
    };

    const { res, data } = await api('/api/v1/files/upload', 'POST', payload);
    if (!res.ok) throw new Error(data?.message || 'Falha no upload');

    const prev = document.getElementById(previewId);
    prev.src = URL.createObjectURL(file);
    prev.classList.remove('hidden');
  };

  try {
    await send('logo-main', 'logo_principal', 'logo-main-preview');
    await send('logo-secondary', 'logo_secundaria', 'logo-secondary-preview');
    filesFeedback.innerHTML = '<div class="alert alert-success">Upload concluído.</div>';
    loadFiles();
  } catch (e) {
    filesFeedback.innerHTML = `<div class="alert alert-error">${e.message}</div>`;
  }
});

loadCompany();
loadFiles();
