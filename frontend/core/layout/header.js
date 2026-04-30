import { authService } from '../auth/auth-service.js';
import { navigate } from '../router/router.js';

function avatarInitials(name = '') {
  return name.split(' ').filter(Boolean).slice(0, 2).map((w) => w[0].toUpperCase()).join('');
}

export function renderHeader(route) {
  const user = authService.getUser();
  const initials = avatarInitials(user?.name || 'U');
  const firstName = user?.name?.split(' ')[0] || 'Usuário';

  const breadcrumb = (route.breadcrumb || [])
    .map((label, i, arr) => {
      const isLast = i === arr.length - 1;
      return isLast
        ? `<li><strong>${label}</strong></li>`
        : `<li><span>${label}</span><span class="sep"><i data-lucide="chevron-right" style="width:12px;height:12px;vertical-align:middle;"></i></span></li>`;
    })
    .join('');

  return `
    <header class="header" id="app-header">
      <div class="header__left">
        <button class="header__menu-btn" id="sidebar-toggle" aria-label="Menu">
          <i data-lucide="menu" style="width:18px;height:18px;"></i>
        </button>
        ${breadcrumb
          ? `<ol class="header__breadcrumb">${breadcrumb}</ol>`
          : `<span class="header__title">${route.title || 'Clínica'}</span>`
        }
      </div>
      <div class="header__right">
        <button class="header__user-btn" id="header-user-btn" title="Sair">
          <div class="header__avatar">${initials}</div>
          <span>${firstName}</span>
          <i data-lucide="chevron-down" style="width:13px;height:13px;opacity:0.5;"></i>
        </button>
      </div>
    </header>`;
}

export function mountHeaderEvents() {
  document.getElementById('sidebar-toggle')?.addEventListener('click', () => {
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    sidebar?.classList.toggle('open');
    overlay?.classList.toggle('active');
  });

  document.getElementById('header-user-btn')?.addEventListener('click', async () => {
    if (confirm('Deseja sair do sistema?')) {
      await authService.logout();
      navigate('/login');
    }
  });
}
