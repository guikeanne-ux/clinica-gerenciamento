import { http } from '../../core/services/http.js';
import { navigate } from '../../core/router/router.js';
import { permissionService } from '../../core/auth/permission-service.js';

let _page = 1;
let _search = '';
let _status = '';

function initials(name = '') {
  return name.split(' ').filter(Boolean).slice(0,2).map(w=>w[0].toUpperCase()).join('');
}

export default {
  async mount(container) {
    _page = 1; _search = ''; _status = '';

    container.innerHTML = `
      <div class="page-header">
        <div class="page-header__info">
          <h1>Pacientes</h1>
          <p class="subtitle">Cadastro e gestão de pacientes</p>
        </div>
        <div class="page-header__actions">
          ${permissionService.has('patients.create') ? `<button class="btn btn-primary btn-md" id="btn-new-patient">+ Novo paciente</button>` : ''}
        </div>
      </div>

      <div class="section">
        <div class="filters-bar">
          <div class="field"><label>Buscar</label><input id="pat-search" class="input" placeholder="Nome, CPF, e-mail ou telefone…" /></div>
          <div class="field" style="max-width:180px;"><label>Status</label>
            <select id="pat-status" class="input">
              <option value="">Todos</option>
              <option value="active">Ativo</option>
              <option value="inactive">Inativo</option>
            </select>
          </div>
          <button class="btn btn-secondary btn-md" id="btn-search-pat" style="margin-top:1.4rem;">Buscar</button>
        </div>

        <div id="patients-table-wrap">
          <div class="skeleton" style="height:200px;border-radius:var(--r-sm);"></div>
        </div>
        <div id="patients-pagination" class="pagination"></div>
      </div>`;

    document.getElementById('btn-new-patient')?.addEventListener('click', () => navigate('/patients/new'));
    document.getElementById('btn-search-pat')?.addEventListener('click', () => { _page = 1; _search = document.getElementById('pat-search').value; _status = document.getElementById('pat-status').value; this._load(); });
    document.getElementById('pat-search')?.addEventListener('keydown', (e) => { if (e.key === 'Enter') { _page = 1; _search = e.target.value; _status = document.getElementById('pat-status').value; this._load(); } });

    await this._load();
  },

  async _load() {
    const wrap = document.getElementById('patients-table-wrap');
    const pager = document.getElementById('patients-pagination');
    if (!wrap) return;

    wrap.innerHTML = `<div class="skeleton" style="height:200px;border-radius:var(--r-sm);"></div>`;

    const url = `/api/v1/patients?search=${encodeURIComponent(_search)}&status=${encodeURIComponent(_status)}&page=${_page}&per_page=15`;
    const res = await http.get(url);

    if (!res.success) {
      wrap.innerHTML = `<div class="alert alert-error">${res.message}</div>`;
      return;
    }

    const items = res.data?.items || [];
    const pagination = res.data?.pagination || {};

    if (!items.length) {
      wrap.innerHTML = `
        <div class="empty-state">
          <div class="empty-state__icon"><i data-lucide="users" style="width:24px;height:24px;"></i></div>
          <div class="empty-state__title">Nenhum paciente encontrado</div>
          <div class="empty-state__desc">Tente ajustar os filtros ou cadastre um novo paciente.</div>
          ${permissionService.has('patients.create') ? `<div class="empty-state__action"><button class="btn btn-primary btn-md" id="btn-empty-new">+ Novo paciente</button></div>` : ''}
        </div>`;
      document.getElementById('btn-empty-new')?.addEventListener('click', () => navigate('/patients/new'));
      window.lucide?.createIcons();
      pager.innerHTML = '';
      return;
    }

    wrap.innerHTML = `
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Paciente</th>
              <th>Contato</th>
              <th>CPF</th>
              <th>Status</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            ${items.map((p) => `
              <tr class="patient-row" data-view="${p.uuid}" style="cursor:pointer;">
                <td data-label="Paciente">
                  <div style="display:flex;align-items:center;gap:0.6rem;">
                    <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,var(--color-primary-200),var(--color-primary-400));display:flex;align-items:center;justify-content:center;font-size:0.7rem;font-weight:700;color:var(--color-primary-900);flex-shrink:0;">${initials(p.full_name)}</div>
                    <span class="font-medium">${p.full_name}</span>
                  </div>
                </td>
                <td data-label="Contato">${p.email || p.phone_primary || '—'}</td>
                <td data-label="CPF">${p.cpf || '—'}</td>
                <td data-label="Status"><span class="badge badge-${p.status === 'active' ? 'success' : 'neutral'}">${p.status === 'active' ? 'Ativo' : 'Inativo'}</span></td>
                <td>
                  <div style="display:flex;gap:0.35rem;justify-content:flex-end;">
                    <button class="btn btn-ghost btn-sm" data-view="${p.uuid}">Ver</button>
                    ${permissionService.has('patients.update') ? `<button class="btn btn-secondary btn-sm" data-edit="${p.uuid}">Editar</button>` : ''}
                  </div>
                </td>
              </tr>`).join('')}
          </tbody>
        </table>
      </div>`;

    wrap.querySelectorAll('[data-view]').forEach(b => b.addEventListener('click', (e) => {
      e.stopPropagation();
      navigate(`/patients/${b.dataset.view}`);
    }));
    wrap.querySelectorAll('.patient-row').forEach((row) => row.addEventListener('click', () => navigate(`/patients/${row.dataset.view}`)));
    wrap.querySelectorAll('[data-edit]').forEach(b => b.addEventListener('click', () => navigate(`/patients/${b.dataset.edit}/edit`)));

    /* Pagination */
    const total = pagination.total || 0;
    const perPage = pagination.per_page || 15;
    const totalPages = Math.ceil(total / perPage);
    if (totalPages > 1) {
      pager.innerHTML = `
        <button class="pagination__btn" id="pag-prev" ${_page <= 1 ? 'disabled' : ''}>← Anterior</button>
        <span class="pagination__info">Página ${_page} de ${totalPages} · ${total} registro(s)</span>
        <button class="pagination__btn" id="pag-next" ${_page >= totalPages ? 'disabled' : ''}>Próxima →</button>`;
      document.getElementById('pag-prev')?.addEventListener('click', () => { _page--; this._load(); });
      document.getElementById('pag-next')?.addEventListener('click', () => { _page++; this._load(); });
    } else {
      pager.innerHTML = total ? `<span class="pagination__info">${total} registro(s)</span>` : '';
    }
  },

  unmount() {},
};
