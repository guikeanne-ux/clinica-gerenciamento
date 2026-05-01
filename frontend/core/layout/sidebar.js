import { permissionService } from '../auth/permission-service.js';
import { authService } from '../auth/auth-service.js';
import { navigate } from '../router/router.js';

const NAV_GROUPS = [
  {
    label: 'Principal',
    items: [
      { path: '/dashboard',  label: 'Início',               icon: 'home',         always: true },
    ],
  },
  {
    label: 'Clínica',
    items: [
      { path: '/schedule', label: 'Agenda', icon: 'calendar-days', permission: 'schedule.view' },
      { path: '/patients',       label: 'Pacientes',      icon: 'users',          permission: 'patients.view' },
      { path: '/professionals',  label: 'Profissionais',  icon: 'stethoscope',    permission: 'professionals.view' },
      { path: '/specialties',    label: 'Especialidades', icon: 'tag',            permission: 'professionals.view' },
    ],
  },
  {
    label: 'Operacional',
    items: [
      { path: '/payment-tables',       label: 'Tabelas de pagamento', icon: 'receipt-text', permission: 'professional_payment.view' },
      { path: '/professional-payment', label: 'Repasse profissional', icon: 'banknote',     permission: 'professional_payment.view' },
    ],
  },
  {
    label: 'Configurações',
    items: [
      { path: '/company',                    label: 'Empresa',                icon: 'settings', permission: 'company.view' },
      { path: '/schedule/appointment-types', label: 'Tipos de compromisso',  icon: 'tags',     permission: 'schedule.event_types.view' },
    ],
  },
];

function isVisible(item) {
  if (item.always) return true;
  if (item.permission) return permissionService.has(item.permission);
  return true;
}

function avatarInitials(name = '') {
  return name.split(' ').filter(Boolean).slice(0, 2).map((w) => w[0].toUpperCase()).join('');
}

export function renderSidebar(user) {
  const currentPath = location.pathname;

  const groups = NAV_GROUPS.map((g) => {
    const visibleItems = g.items.filter(isVisible);
    if (!visibleItems.length) return '';
    const links = visibleItems.map((item) => {
      const active = currentPath === item.path || currentPath.startsWith(item.path + '/');
      return `
        <a class="sidebar__link${active ? ' active' : ''}" href="${item.path}" data-link>
          <i data-lucide="${item.icon}" class="sidebar__icon"></i>
          ${item.label}
        </a>`;
    }).join('');

    return `
      <div class="sidebar__section">
        <div class="sidebar__section-label">${g.label}</div>
        ${links}
      </div>`;
  }).join('');

  const initials = avatarInitials(user?.name || 'U');
  const roleName = (user?.roles?.[0] || 'Usuário').replace(/_/g, ' ');

  return `
    <div class="sidebar__logo">
      <div class="sidebar__logo-mark">C</div>
      <div class="sidebar__logo-text">
        <span class="sidebar__logo-name">ClinicaGest</span>
        <span class="sidebar__logo-sub">Gestão clínica</span>
      </div>
    </div>
    <nav class="sidebar__nav">${groups}</nav>
    <div class="sidebar__footer">
      <div class="sidebar__user" id="sidebar-user-btn" title="Clique para sair">
        <div class="sidebar__avatar">${initials}</div>
        <div class="sidebar__user-info">
          <div class="sidebar__user-name">${user?.name || 'Usuário'}</div>
          <div class="sidebar__user-role">${roleName}</div>
        </div>
        <i data-lucide="log-out" style="width:14px;height:14px;flex-shrink:0;opacity:0.4;"></i>
      </div>
    </div>`;
}

export function mountSidebarEvents() {
  document.getElementById('sidebar-user-btn')?.addEventListener('click', async () => {
    if (confirm('Deseja sair do sistema?')) {
      await authService.logout();
      navigate('/login');
    }
  });
}
