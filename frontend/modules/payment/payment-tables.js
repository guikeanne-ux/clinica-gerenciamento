import { http } from '../../core/services/http.js';
import { navigate } from '../../core/router/router.js';
import { permissionService } from '../../core/auth/permission-service.js';
import { toast } from '../../core/js/toast.js';

let _page = 1;
let _search = '';
let _status = '';

export default {
  async mount(container) {
    _page = 1; _search = ''; _status = '';

    container.innerHTML = `
      <div class="page-header">
        <div class="page-header__info">
          <h1>Tabelas de pagamento</h1>
          <p class="subtitle">Configuração de regras de repasse profissional</p>
        </div>
        <div class="page-header__actions">
          ${permissionService.has('professional_payment.create') ? `<button class="btn btn-primary btn-md" id="btn-new-pt">+ Nova tabela</button>` : ''}
        </div>
      </div>

      <div class="section">
        <div class="filters-bar">
          <div class="field"><label>Buscar</label><input id="pt-search" class="input" placeholder="Nome da tabela…" /></div>
          <div class="field" style="max-width:180px;"><label>Status</label>
            <select id="pt-status" class="input">
              <option value="">Todos</option>
              <option value="active">Ativa</option>
              <option value="inactive">Inativa</option>
            </select>
          </div>
          <button class="btn btn-secondary btn-md" id="btn-search-pt" style="margin-top:1.4rem;">Buscar</button>
        </div>
        <div id="pt-table-wrap"><div class="skeleton" style="height:200px;border-radius:var(--r-sm);"></div></div>
        <div id="pt-pagination" class="pagination"></div>
      </div>`;

    document.getElementById('btn-new-pt')?.addEventListener('click', () => navigate('/payment-tables/new'));
    document.getElementById('btn-search-pt')?.addEventListener('click', () => { _page = 1; _search = document.getElementById('pt-search').value; _status = document.getElementById('pt-status').value; this._load(); });

    await this._load();
  },

  async _load() {
    const wrap = document.getElementById('pt-table-wrap');
    const pager = document.getElementById('pt-pagination');
    if (!wrap) return;

    wrap.innerHTML = `<div class="skeleton" style="height:200px;border-radius:var(--r-sm);"></div>`;

    const res = await http.get(`/api/v1/payment-tables?search=${encodeURIComponent(_search)}&status=${encodeURIComponent(_status)}&page=${_page}&per_page=15`);
    if (!res.success) {
      toast.error(res.message || 'Não foi possível carregar as tabelas de pagamento.');
      wrap.innerHTML = '<div class="empty-state"><div class="empty-state__title">Não foi possível carregar as tabelas.</div></div>';
      return;
    }

    const items = res.data?.items || [];
    const pagination = res.data?.pagination || {};

    if (!items.length) {
      wrap.innerHTML = `<div class="empty-state"><div class="empty-state__title">Nenhuma tabela encontrada</div>${permissionService.has('professional_payment.create') ? `<div class="empty-state__action"><button class="btn btn-primary btn-md" id="btn-empty-pt">+ Nova tabela</button></div>` : ''}</div>`;
      document.getElementById('btn-empty-pt')?.addEventListener('click', () => navigate('/payment-tables/new'));
      if (pager) pager.innerHTML = '';
      return;
    }

    const typeLabel = {
      fixed_per_attendance: 'Fixo por atendimento',
      fixed_monthly: 'Fixo mensal',
      hybrid: 'Híbrido',
      custom: 'Customizado',
    };

    wrap.innerHTML = `
      <div class="table-wrap">
        <table>
          <thead><tr><th>Nome</th><th>Tipo</th><th>Vigência</th><th>Status</th><th></th></tr></thead>
          <tbody>
            ${items.map((t) => `
              <tr>
                <td data-label="Nome" class="font-medium">${t.name}</td>
                <td data-label="Tipo">${typeLabel[t.calculation_type] || t.calculation_type}</td>
                <td data-label="Vigência">${t.effective_start_date || '—'} ${t.effective_end_date ? '→ ' + t.effective_end_date : '→ aberta'}</td>
                <td data-label="Status"><span class="badge badge-${t.status === 'active' ? 'success' : 'neutral'}">${t.status === 'active' ? 'Ativa' : 'Inativa'}</span></td>
                <td>
                  <div style="display:flex;gap:0.35rem;justify-content:flex-end;">
                    <button class="btn btn-secondary btn-sm" data-edit="${t.uuid}">Editar</button>
                  </div>
                </td>
              </tr>`).join('')}
          </tbody>
        </table>
      </div>`;

    wrap.querySelectorAll('[data-edit]').forEach(b => b.addEventListener('click', () => navigate(`/payment-tables/${b.dataset.edit}`)));

    const total = pagination.total || 0;
    const perPage = pagination.per_page || 15;
    const totalPages = Math.ceil(total / perPage);
    if (totalPages > 1 && pager) {
      pager.innerHTML = `
        <button class="pagination__btn" id="ptpag-prev" ${_page <= 1 ? 'disabled' : ''}>← Anterior</button>
        <span class="pagination__info">Página ${_page} de ${totalPages}</span>
        <button class="pagination__btn" id="ptpag-next" ${_page >= totalPages ? 'disabled' : ''}>Próxima →</button>`;
      document.getElementById('ptpag-prev')?.addEventListener('click', () => { _page--; this._load(); });
      document.getElementById('ptpag-next')?.addEventListener('click', () => { _page++; this._load(); });
    } else if (pager) {
      pager.innerHTML = total ? `<span class="pagination__info">${total} tabela(s)</span>` : '';
    }
  },

  unmount() {},
};
