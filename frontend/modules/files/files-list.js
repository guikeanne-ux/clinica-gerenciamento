import { http } from '../../core/services/http.js';

export default {
  async mount(container) {
    container.innerHTML = `
      <div class="page-header">
        <div class="page-header__info">
          <h1>Arquivos</h1>
          <p class="subtitle">Arquivos e documentos da empresa</p>
        </div>
      </div>
      <div class="section">
        <div id="files-list" class="cards-grid"></div>
      </div>`;

    const res = await http.get('/api/v1/files?related_module=company&related_entity_type=company');
    const container2 = document.getElementById('files-list');
    if (!res.success || !res.data?.length) {
      container2.innerHTML = `
        <div class="empty-state">
          <div class="empty-state__title">Nenhum arquivo encontrado</div>
          <div class="empty-state__desc">Envie arquivos na tela de Empresa.</div>
        </div>`;
      return;
    }
    container2.innerHTML = res.data.map((f) => `
      <div class="card">
        <div class="font-semibold">${f.original_name}</div>
        <div class="text-xs text-muted" style="margin-top:0.25rem;">${f.classification} · ${f.mime_type}</div>
        <div style="margin-top:0.75rem; display:flex; gap:0.4rem;">
          <a href="/api/v1/files/${f.uuid}/download" class="btn btn-secondary btn-sm" target="_blank">Baixar</a>
        </div>
      </div>`).join('');
  },

  unmount() {},
};
