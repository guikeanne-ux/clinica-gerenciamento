import { http } from '../../core/services/http.js';
import { authService } from '../../core/auth/auth-service.js';
import { navigate } from '../../core/router/router.js';
import { permissionService } from '../../core/auth/permission-service.js';

function licon(name, size = 20) {
  return `<i data-lucide="${name}" style="width:${size}px;height:${size}px;"></i>`;
}

function quickActionHTML(path, iconName, label, sub) {
  return `
    <button class="quick-action" data-link href="${path}">
      <div class="quick-action__icon">${licon(iconName, 18)}</div>
      <div>
        <div class="quick-action__label">${label}</div>
        <div class="quick-action__sub">${sub}</div>
      </div>
    </button>`;
}

export default {
  async mount(container) {
    const user = authService.getUser();
    const firstName = user?.name?.split(' ')[0] || 'Usuário';
    const now = new Date();
    const weekday = now.toLocaleDateString('pt-BR', { weekday: 'long' });
    const dateStr = now.toLocaleDateString('pt-BR', { day: '2-digit', month: 'long', year: 'numeric' });

    container.innerHTML = `
      <div class="dashboard-welcome">
        <h1>Olá, ${firstName}</h1>
        <p>${weekday.charAt(0).toUpperCase() + weekday.slice(1)}, ${dateStr}</p>
      </div>

      <div class="stats-grid" id="stats-grid">
        ${statSkeleton()}${statSkeleton()}${statSkeleton()}${statSkeleton()}
      </div>

      <div class="dashboard-bottom">
        <div class="section">
          <div class="section__header"><h2>Ações rápidas</h2></div>
          <div id="quick-actions" style="display:grid;gap:0.5rem;"></div>
        </div>
        <div class="section">
          <div class="section__header">
            <h2>Pacientes recentes</h2>
            <a href="/patients" data-link class="btn btn-ghost btn-sm">Ver todos</a>
          </div>
          <div id="recent-patients"></div>
        </div>
      </div>`;

    this._loadStats();
    this._buildQuickActions();
    this._loadRecentPatients();

    window.lucide?.createIcons();
  },

  async _loadStats() {
    const grid = document.getElementById('stats-grid');
    if (!grid) return;

    let patCount = '—';
    let profCount = '—';

    try {
      const [patRes, profRes] = await Promise.all([
        permissionService.has('patients.view') ? http.get('/api/v1/patients?per_page=1') : null,
        permissionService.has('professionals.view') ? http.get('/api/v1/professionals?per_page=1') : null,
      ]);
      if (patRes?.success)  patCount  = patRes.data?.pagination?.total ?? '—';
      if (profRes?.success) profCount = profRes.data?.pagination?.total ?? '—';
    } catch { /* noop */ }

    grid.innerHTML = `
      ${statCard('stat-card__icon--primary', 'users',         'Pacientes ativos',  patCount,  'Total cadastrado')}
      ${statCard('stat-card__icon--accent',  'stethoscope',   'Profissionais',      profCount, 'Total cadastrado')}
      ${statCard('stat-card__icon--success', 'calendar',      'Agenda do dia',      '—',       'Disponível em breve')}
      ${statCard('stat-card__icon--warning', 'banknote',      'Repasse pendente',   '—',       'Disponível em breve')}`;

    window.lucide?.createIcons();
  },

  _buildQuickActions() {
    const container = document.getElementById('quick-actions');
    if (!container) return;
    const actions = [];
    if (permissionService.has('patients.create'))
      actions.push(quickActionHTML('/patients/new', 'user-plus', 'Novo paciente', 'Cadastrar novo paciente'));
    if (permissionService.has('professionals.create'))
      actions.push(quickActionHTML('/professionals/new', 'briefcase', 'Novo profissional', 'Cadastrar profissional'));
    if (permissionService.has('company.view'))
      actions.push(quickActionHTML('/company', 'settings', 'Configurar empresa', 'Dados e arquivos'));

    container.innerHTML = actions.length
      ? actions.join('')
      : `<div class="empty-state"><p class="empty-state__desc">Nenhuma ação disponível.</p></div>`;

    window.lucide?.createIcons();
  },

  async _loadRecentPatients() {
    const container = document.getElementById('recent-patients');
    if (!container) return;
    if (!permissionService.has('patients.view')) {
      container.innerHTML = `<div class="empty-state"><p class="empty-state__desc text-sm">Sem permissão.</p></div>`;
      return;
    }
    try {
      const res = await http.get('/api/v1/patients?per_page=5&status=active');
      const patients = res.data?.items || [];
      if (!patients.length) {
        container.innerHTML = `<div class="empty-state"><p class="empty-state__desc text-sm">Nenhum paciente.</p></div>`;
        return;
      }
      container.innerHTML = patients.map((p) => {
        const initials = p.full_name?.split(' ').filter(Boolean).slice(0,2).map(w=>w[0]).join('').toUpperCase() || 'P';
        return `
          <div class="list-item" data-link href="/patients/${p.uuid}">
            <div class="list-item__avatar">${initials}</div>
            <div class="list-item__main">
              <div class="list-item__name">${p.full_name}</div>
              <div class="list-item__sub">${p.email || p.cpf || 'Sem contato'}</div>
            </div>
            <span class="badge badge-${p.status === 'active' ? 'success' : 'neutral'}">${p.status === 'active' ? 'Ativo' : 'Inativo'}</span>
          </div>`;
      }).join('');
    } catch {
      container.innerHTML = `<div class="empty-state"><p class="empty-state__desc text-sm">Erro ao carregar.</p></div>`;
    }
  },

  unmount() {},
};

function statCard(iconClass, iconName, label, value, sub) {
  return `
    <div class="stat-card">
      <div class="stat-card__icon ${iconClass}">
        <i data-lucide="${iconName}" style="width:20px;height:20px;"></i>
      </div>
      <div class="stat-card__label">${label}</div>
      <div class="stat-card__value">${value}</div>
      <div class="stat-card__sub">${sub}</div>
    </div>`;
}

function statSkeleton() {
  return `<div class="stat-card"><div class="skeleton" style="height:80px;border-radius:var(--r-sm);"></div></div>`;
}
