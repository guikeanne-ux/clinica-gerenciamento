import { http } from '../../core/services/http.js';
import { initMasks } from '../../core/js/masks.js';

const readFileAsBase64 = (file) => new Promise((resolve, reject) => {
  const reader = new FileReader();
  reader.onload = () => resolve(String(reader.result).split(',')[1] || '');
  reader.onerror = reject;
  reader.readAsDataURL(file);
});

async function fetchAuthBlob(uuid) {
  const token = localStorage.getItem('clinica_token');
  const res = await fetch(`/api/v1/files/${uuid}/download`, {
    headers: token ? { Authorization: `Bearer ${token}` } : {},
  });
  if (!res.ok) return null;
  const blob = await res.blob();
  return URL.createObjectURL(blob);
}

const _blobUrls = [];

export default {
  async mount(container) {
    container.innerHTML = `
      <div class="page-header">
        <div class="page-header__info">
          <h1>Empresa</h1>
          <p class="subtitle">Dados cadastrais e arquivos da clínica</p>
        </div>
      </div>

      <div class="tabs" id="company-tabs">
        <button class="tab active" data-target="tab-data">Dados cadastrais</button>
        <button class="tab" data-target="tab-files">Arquivos e logotipos</button>
      </div>

      <!-- Tab: Dados -->
      <div id="tab-data">
        <div class="section">
          <form id="company-form">
            <div class="form-group">
              <div class="form-group__title">Identificação</div>
              <div class="form-grid">
                <div class="field">
                  <label>Razão social</label>
                  <input class="input" name="legal_name" placeholder="Razão social" />
                </div>
                <div class="field">
                  <label>Nome fantasia</label>
                  <input class="input" name="trade_name" placeholder="Nome fantasia" />
                </div>
                <div class="field">
                  <label>CNPJ</label>
                  <input class="input" name="document" data-mask="cnpj" placeholder="00.000.000/0000-00" />
                </div>
              </div>
            </div>

            <div class="form-group">
              <div class="form-group__title">Contato</div>
              <div class="form-grid">
                <div class="field">
                  <label>E-mail</label>
                  <input class="input" name="email" type="email" placeholder="contato@clinica.com.br" />
                </div>
                <div class="field">
                  <label>Telefone</label>
                  <input class="input" name="phone" data-mask="phone" placeholder="(00) 0000-0000" />
                </div>
                <div class="field">
                  <label>Site</label>
                  <input class="input" name="website" placeholder="https://clinica.com.br" />
                </div>
              </div>
            </div>

            <div class="form-group">
              <div class="form-group__title">Endereço</div>
              <div class="form-grid">
                <div class="field">
                  <label>CEP</label>
                  <input class="input" name="address_zipcode" data-mask="cep" placeholder="00000-000" />
                </div>
                <div class="field">
                  <label>Logradouro</label>
                  <input class="input" name="address_street" placeholder="Rua, Av." />
                </div>
                <div class="field">
                  <label>Número</label>
                  <input class="input" name="address_number" placeholder="Nº" />
                </div>
                <div class="field">
                  <label>Complemento</label>
                  <input class="input" name="address_complement" placeholder="Sala, bloco…" />
                </div>
                <div class="field">
                  <label>Bairro</label>
                  <input class="input" name="address_district" placeholder="Bairro" />
                </div>
                <div class="field">
                  <label>Cidade</label>
                  <input class="input" name="address_city" placeholder="Cidade" />
                </div>
                <div class="field">
                  <label>Estado</label>
                  <input class="input" name="address_state" placeholder="UF" maxlength="2" style="text-transform:uppercase;" />
                </div>
              </div>
            </div>
          </form>

          <div id="company-feedback" style="margin-top:0.75rem;"></div>

          <div style="margin-top:1rem;">
            <button class="btn btn-primary btn-md" id="save-company">Salvar alterações</button>
          </div>
        </div>
      </div>

      <!-- Tab: Arquivos -->
      <div id="tab-files" style="display:none;">
        <div class="section">
          <div class="section__header">
            <h2>Enviar arquivo / logotipo</h2>
          </div>
          <div class="form-grid" style="margin-bottom:1rem;">
            <div class="field">
              <label>Arquivo</label>
              <input type="file" class="input" id="upload-file-input" accept="image/*,application/pdf" />
            </div>
            <div class="field">
              <label>Classificação</label>
              <select class="input" id="upload-classification">
                <option value="logo_principal">Logo principal</option>
                <option value="logo_secundaria">Logo secundária</option>
                <option value="documento">Documento</option>
                <option value="outro">Outro</option>
              </select>
            </div>
          </div>
          <button class="btn btn-primary btn-md" id="btn-upload-file">Enviar arquivo</button>
          <div id="files-feedback" style="margin-top:0.75rem;"></div>
        </div>

        <div class="section">
          <div class="section__header">
            <h2>Arquivos da empresa</h2>
            <button class="btn btn-ghost btn-sm" id="btn-reload-files">Atualizar</button>
          </div>
          <div id="files-list"></div>
        </div>
      </div>`;

    initMasks();
    await this._loadCompany();
    this._mountEvents();
  },

  async _loadCompany() {
    const res = await http.get('/api/v1/company');
    if (!res.success) return;
    const form = document.getElementById('company-form');
    Object.entries(res.data || {}).forEach(([k, v]) => {
      const el = form?.querySelector(`[name="${k}"]`);
      if (el) el.value = v ?? '';
    });
  },

  async _loadFiles() {
    const container = document.getElementById('files-list');
    if (!container) return;

    // Revoke previous blob URLs to avoid memory leaks
    _blobUrls.splice(0).forEach(URL.revokeObjectURL.bind(URL));

    container.innerHTML = `<div class="skeleton" style="height:80px;border-radius:var(--r-sm);"></div>`;

    const res = await http.get('/api/v1/files?related_module=company&related_entity_type=company');
    if (!res.success || !res.data?.length) {
      container.innerHTML = `<div class="empty-state"><p class="empty-state__desc">Nenhum arquivo enviado.</p></div>`;
      return;
    }

    const isImage = (mime) => mime?.startsWith('image/');

    // Render cards with placeholder thumbnails for images
    container.innerHTML = res.data.map((f) => `
      <div class="card" style="display:flex;align-items:center;gap:1rem;margin-bottom:0.5rem;" data-file-uuid="${f.uuid}">
        <div style="width:56px;height:56px;flex-shrink:0;border-radius:var(--r-sm);overflow:hidden;background:var(--surface-2);display:flex;align-items:center;justify-content:center;" id="thumb-${f.uuid}">
          <i data-lucide="${isImage(f.mime_type) ? 'image' : 'file'}" style="width:24px;height:24px;opacity:0.4;"></i>
        </div>
        <div style="flex:1;min-width:0;">
          <div class="font-semibold text-sm" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${f.original_name}</div>
          <div class="text-xs text-muted">${f.classification} · ${f.mime_type}</div>
        </div>
        <div style="display:flex;gap:0.4rem;flex-shrink:0;">
          <button class="btn btn-ghost btn-xs btn-view-file" data-uuid="${f.uuid}" data-image="${isImage(f.mime_type) ? '1' : '0'}">
            ${isImage(f.mime_type) ? 'Ver' : 'Download'}
          </button>
          <button class="btn btn-danger btn-xs" data-delete-uuid="${f.uuid}">Excluir</button>
        </div>
      </div>`).join('');

    window.lucide?.createIcons();

    // Load image thumbnails via authenticated fetch
    for (const f of res.data) {
      if (!isImage(f.mime_type)) continue;
      const blobUrl = await fetchAuthBlob(f.uuid);
      if (!blobUrl) continue;
      _blobUrls.push(blobUrl);
      const thumb = document.getElementById(`thumb-${f.uuid}`);
      if (thumb) {
        thumb.innerHTML = `<img src="${blobUrl}" alt="${f.original_name}" style="width:100%;height:100%;object-fit:cover;" />`;
      }
    }

    container.querySelectorAll('.btn-view-file').forEach((btn) => {
      btn.addEventListener('click', () => this._openFile(btn.dataset.uuid, btn.dataset.image === '1'));
    });
    container.querySelectorAll('[data-delete-uuid]').forEach((btn) => {
      btn.addEventListener('click', () => this._deleteFile(btn.dataset.deleteUuid));
    });
  },

  async _openFile(uuid, isImage) {
    const blobUrl = await fetchAuthBlob(uuid);
    if (!blobUrl) return;
    _blobUrls.push(blobUrl);
    if (isImage) {
      // Open in a simple lightbox overlay
      const overlay = document.createElement('div');
      overlay.style.cssText = 'position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.85);display:flex;align-items:center;justify-content:center;cursor:zoom-out;';
      overlay.innerHTML = `<img src="${blobUrl}" style="max-width:90vw;max-height:90vh;border-radius:var(--r);box-shadow:0 8px 40px rgba(0,0,0,0.6);" />`;
      overlay.addEventListener('click', () => overlay.remove());
      document.body.appendChild(overlay);
    } else {
      const a = document.createElement('a');
      a.href = blobUrl;
      a.download = '';
      a.click();
    }
  },

  async _deleteFile(uuid) {
    if (!confirm('Excluir este arquivo?')) return;
    const res = await http.delete(`/api/v1/files/${uuid}`);
    const feedback = document.getElementById('files-feedback');
    if (res.success) {
      feedback.innerHTML = `<div class="alert alert-success">Arquivo excluído.</div>`;
      await this._loadFiles();
    } else {
      feedback.innerHTML = `<div class="alert alert-error">${res.message || 'Erro ao excluir.'}</div>`;
    }
  },

  _mountEvents() {
    document.querySelectorAll('#company-tabs .tab').forEach((tab) => {
      tab.addEventListener('click', () => {
        document.querySelectorAll('#company-tabs .tab').forEach(t => t.classList.remove('active'));
        tab.classList.add('active');
        const id = tab.dataset.target;
        ['tab-data', 'tab-files'].forEach(t => {
          document.getElementById(t).style.display = t === id ? '' : 'none';
        });
        if (id === 'tab-files') this._loadFiles();
      });
    });

    document.getElementById('btn-reload-files')?.addEventListener('click', () => this._loadFiles());

    document.getElementById('save-company')?.addEventListener('click', async () => {
      const form = document.getElementById('company-form');
      const feedback = document.getElementById('company-feedback');
      const payload = Object.fromEntries(new FormData(form));
      const res = await http.put('/api/v1/company', payload);
      feedback.innerHTML = res.success
        ? `<div class="alert alert-success">Dados da empresa atualizados.</div>`
        : `<div class="alert alert-error">${res.message || 'Erro ao salvar.'}</div>`;
    });

    document.getElementById('btn-upload-file')?.addEventListener('click', async () => {
      const feedback = document.getElementById('files-feedback');
      const input = document.getElementById('upload-file-input');
      const classification = document.getElementById('upload-classification')?.value;
      const file = input?.files?.[0];
      if (!file) { feedback.innerHTML = `<div class="alert alert-warning">Selecione um arquivo.</div>`; return; }
      try {
        const content_base64 = await readFileAsBase64(file);
        const payload = {
          original_name: file.name,
          mime_type: file.type,
          content_base64,
          classification,
          related_module: 'company',
          related_entity_type: 'company',
          related_entity_uuid: null,
        };
        const res = await http.post('/api/v1/files/upload', payload);
        if (!res.success) throw new Error(res.message || 'Falha no upload');
        feedback.innerHTML = `<div class="alert alert-success">Arquivo enviado com sucesso.</div>`;
        input.value = '';
        await this._loadFiles();
      } catch (e) {
        feedback.innerHTML = `<div class="alert alert-error">${e.message}</div>`;
      }
    });
  },

  unmount() {
    _blobUrls.splice(0).forEach(URL.revokeObjectURL.bind(URL));
  },
};
