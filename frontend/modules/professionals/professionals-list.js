import { http } from '../../core/services/http.js';
import { navigate } from '../../core/router/router.js';
import { permissionService } from '../../core/auth/permission-service.js';
import { toast } from '../../core/js/toast.js';

function initials(name = '') {
  return name.split(' ').filter(Boolean).slice(0,2).map(w=>w[0].toUpperCase()).join('');
}

function specialtyLabel(professional) {
  const list = [];
  if (professional.main_specialty) list.push(professional.main_specialty);

  try {
    const parsed = JSON.parse(professional.secondary_specialties_json || '[]');
    if (Array.isArray(parsed)) {
      parsed.forEach((item) => {
        if (item && !list.includes(item)) list.push(item);
      });
    }
  } catch {
    // Ignore malformed JSON and fallback to main specialty only.
  }

  return list.length ? list.join(', ') : '—';
}

let _page = 1;
let _search = '';

export default {
  async mount(container) {
    _page = 1; _search = '';

    container.innerHTML = `
      <div class="page-header">
        <div class="page-header__info">
          <h1>Profissionais</h1>
          <p class="subtitle">Equipe clínica e colaboradores</p>
        </div>
        <div class="page-header__actions">
          ${permissionService.has('professionals.create') ? `<button class="btn btn-primary btn-md" id="btn-new-prof">+ Novo profissional</button>` : ''}
        </div>
      </div>

      <div class="section">
        <div class="filters-bar">
          <div class="field"><label>Buscar</label><input id="prof-search" class="input" placeholder="Nome, CRP, e-mail…" /></div>
          <button class="btn btn-secondary btn-md" id="btn-search-prof" style="margin-top:1.4rem;">Buscar</button>
        </div>
        <div id="prof-table-wrap"><div class="skeleton" style="height:200px;border-radius:var(--r-sm);"></div></div>
        <div id="prof-pagination" class="pagination"></div>
      </div>`;

    document.getElementById('btn-new-prof')?.addEventListener('click', () => navigate('/professionals/new'));
    document.getElementById('btn-search-prof')?.addEventListener('click', () => { _page = 1; _search = document.getElementById('prof-search').value; this._load(); });
    document.getElementById('prof-search')?.addEventListener('keydown', (e) => { if (e.key === 'Enter') { _page = 1; _search = e.target.value; this._load(); } });

    await this._load();
  },

  async _load() {
    const wrap = document.getElementById('prof-table-wrap');
    const pager = document.getElementById('prof-pagination');
    if (!wrap) return;

    wrap.innerHTML = `<div class="skeleton" style="height:200px;border-radius:var(--r-sm);"></div>`;

    const res = await http.get(`/api/v1/professionals?search=${encodeURIComponent(_search)}&page=${_page}&per_page=15`);
    if (!res.success) {
      toast.error(res.message || 'Não foi possível carregar os profissionais.');
      wrap.innerHTML = '<div class="empty-state"><div class="empty-state__title">Não foi possível carregar os profissionais.</div></div>';
      return;
    }

    const items = res.data?.items || [];
    const pagination = res.data?.pagination || {};

    if (!items.length) {
      wrap.innerHTML = `
        <div class="empty-state">
          <div class="empty-state__icon"><i data-lucide="stethoscope" style="width:24px;height:24px;"></i></div>
          <div class="empty-state__title">Nenhum profissional encontrado</div>
          <div class="empty-state__desc">Ajuste o filtro ou cadastre um novo profissional.</div>
          ${permissionService.has('professionals.create') ? `<div class="empty-state__action"><button class="btn btn-primary btn-md" id="btn-empty-prof">+ Novo profissional</button></div>` : ''}
        </div>`;
      document.getElementById('btn-empty-prof')?.addEventListener('click', () => navigate('/professionals/new'));
      window.lucide?.createIcons();
      if (pager) pager.innerHTML = '';
      return;
    }

    wrap.innerHTML = `
      <div class="table-wrap">
        <table>
          <thead><tr><th>Profissional</th><th>Conselho</th><th>Especialidade</th><th>Contato</th><th></th></tr></thead>
          <tbody>
            ${items.map((p) => `
              <tr>
                <td data-label="Profissional">
                  <div style="display:flex;align-items:center;gap:0.6rem;">
                    <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,var(--color-accent-200),var(--color-accent-400));display:flex;align-items:center;justify-content:center;font-size:0.7rem;font-weight:700;color:var(--color-accent-600);flex-shrink:0;">${initials(p.full_name)}</div>
                    <span class="font-medium">${p.full_name}</span>
                  </div>
                </td>
                <td data-label="Conselho">${p.professional_registry || '—'}</td>
                <td data-label="Especialidade">${specialtyLabel(p)}</td>
                <td data-label="Contato">${p.email || '—'}</td>
                <td>
                  <div style="display:flex;gap:0.35rem;justify-content:flex-end;">
                    ${permissionService.has('professionals.update') ? `<button class="btn btn-secondary btn-sm" data-edit="${p.uuid}">Editar</button>` : ''}
                  </div>
                </td>
              </tr>`).join('')}
          </tbody>
        </table>
      </div>`;

    wrap.querySelectorAll('[data-edit]').forEach(b => b.addEventListener('click', () => navigate(`/professionals/${b.dataset.edit}`)));

    const total = pagination.total || 0;
    const perPage = pagination.per_page || 15;
    const totalPages = Math.ceil(total / perPage);
    if (totalPages > 1 && pager) {
      pager.innerHTML = `
        <button class="pagination__btn" id="ppag-prev" ${_page <= 1 ? 'disabled' : ''}>← Anterior</button>
        <span class="pagination__info">Página ${_page} de ${totalPages}</span>
        <button class="pagination__btn" id="ppag-next" ${_page >= totalPages ? 'disabled' : ''}>Próxima →</button>`;
      document.getElementById('ppag-prev')?.addEventListener('click', () => { _page--; this._load(); });
      document.getElementById('ppag-next')?.addEventListener('click', () => { _page++; this._load(); });
    } else if (pager) {
      pager.innerHTML = total ? `<span class="pagination__info">${total} registro(s)</span>` : '';
    }
  },

  unmount() {},
};
