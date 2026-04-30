import { http } from '../../core/services/http.js';
import { navigate } from '../../core/router/router.js';
import { permissionService } from '../../core/auth/permission-service.js';
import { toast } from '../../core/js/toast.js';

let _page = 1;
let _search = '';

export default {
  async mount(container) {
    _page = 1; _search = '';

    container.innerHTML = `
      <div class="page-header">
        <div class="page-header__info">
          <h1>Fornecedores</h1>
          <p class="subtitle">Cadastro de fornecedores e parceiros</p>
        </div>
        <div class="page-header__actions">
          ${permissionService.has('suppliers.create') ? `<button class="btn btn-primary btn-md" id="btn-new-sup">+ Novo fornecedor</button>` : ''}
        </div>
      </div>

      <div class="section">
        <div class="filters-bar">
          <div class="field"><label>Buscar</label><input id="sup-search" class="input" placeholder="Nome, CNPJ…" /></div>
          <button class="btn btn-secondary btn-md" id="btn-search-sup" style="margin-top:1.4rem;">Buscar</button>
        </div>
        <div id="sup-table-wrap"><div class="skeleton" style="height:200px;border-radius:var(--r-sm);"></div></div>
        <div id="sup-pagination" class="pagination"></div>
      </div>`;

    document.getElementById('btn-new-sup')?.addEventListener('click', () => navigate('/suppliers/new'));
    document.getElementById('btn-search-sup')?.addEventListener('click', () => { _page = 1; _search = document.getElementById('sup-search').value; this._load(); });
    document.getElementById('sup-search')?.addEventListener('keydown', (e) => { if (e.key === 'Enter') { _page = 1; _search = e.target.value; this._load(); } });

    await this._load();
  },

  async _load() {
    const wrap = document.getElementById('sup-table-wrap');
    const pager = document.getElementById('sup-pagination');
    if (!wrap) return;

    wrap.innerHTML = `<div class="skeleton" style="height:200px;border-radius:var(--r-sm);"></div>`;

    const res = await http.get(`/api/v1/suppliers?search=${encodeURIComponent(_search)}&page=${_page}&per_page=15`);
    if (!res.success) {
      toast.error(res.message || 'Não foi possível carregar os fornecedores.');
      wrap.innerHTML = '<div class="empty-state"><div class="empty-state__title">Não foi possível carregar os fornecedores.</div></div>';
      return;
    }

    const items = res.data?.items || [];
    const pagination = res.data?.pagination || {};

    if (!items.length) {
      wrap.innerHTML = `
        <div class="empty-state">
          <div class="empty-state__icon"><i data-lucide="building-2" style="width:24px;height:24px;"></i></div>
          <div class="empty-state__title">Nenhum fornecedor encontrado</div>
          ${permissionService.has('suppliers.create') ? `<div class="empty-state__action"><button class="btn btn-primary btn-md" id="btn-empty-sup">+ Novo fornecedor</button></div>` : ''}
        </div>`;
      document.getElementById('btn-empty-sup')?.addEventListener('click', () => navigate('/suppliers/new'));
      window.lucide?.createIcons();
      if (pager) pager.innerHTML = '';
      return;
    }

    wrap.innerHTML = `
      <div class="table-wrap">
        <table>
          <thead><tr><th>Nome/Razão social</th><th>Documento</th><th>Tipo</th><th>Contato</th><th></th></tr></thead>
          <tbody>
            ${items.map((s) => `
              <tr>
                <td data-label="Nome" class="font-medium">${s.name_or_legal_name || s.legal_name || s.trade_name || '—'}</td>
                <td data-label="Documento">${s.document || s.cnpj || s.cpf || '—'}</td>
                <td data-label="Tipo">${s.type || '—'}</td>
                <td data-label="Contato">${s.email || s.phone || '—'}</td>
                <td>
                  <div style="display:flex;gap:0.35rem;justify-content:flex-end;">
                    ${permissionService.has('suppliers.update') ? `<button class="btn btn-secondary btn-sm" data-edit="${s.uuid}">Editar</button>` : ''}
                  </div>
                </td>
              </tr>`).join('')}
          </tbody>
        </table>
      </div>`;

    wrap.querySelectorAll('[data-edit]').forEach(b => b.addEventListener('click', () => navigate(`/suppliers/${b.dataset.edit}`)));

    const total = pagination.total || 0;
    const perPage = pagination.per_page || 15;
    const totalPages = Math.ceil(total / perPage);
    if (totalPages > 1 && pager) {
      pager.innerHTML = `
        <button class="pagination__btn" id="spag-prev" ${_page <= 1 ? 'disabled' : ''}>← Anterior</button>
        <span class="pagination__info">Página ${_page} de ${totalPages}</span>
        <button class="pagination__btn" id="spag-next" ${_page >= totalPages ? 'disabled' : ''}>Próxima →</button>`;
      document.getElementById('spag-prev')?.addEventListener('click', () => { _page--; this._load(); });
      document.getElementById('spag-next')?.addEventListener('click', () => { _page++; this._load(); });
    } else if (pager) {
      pager.innerHTML = total ? `<span class="pagination__info">${total} registro(s)</span>` : '';
    }
  },

  unmount() {},
};
