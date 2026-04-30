import { authService } from '../auth/auth-service.js';
import { renderSidebar, mountSidebarEvents } from './sidebar.js';
import { renderHeader, mountHeaderEvents } from './header.js';

let _currentLayout = null;

export const appLayout = {
  async render(layoutType, route) {
    const root = document.getElementById('app');

    if (layoutType === 'public') {
      if (_currentLayout !== 'public') {
        _currentLayout = 'public';
        root.innerHTML = `<div id="page-container"></div>`;
      }
      return document.getElementById('page-container');
    }

    /* authenticated layout */
    const user = await authService.ensureUser();

    if (_currentLayout !== 'app') {
      _currentLayout = 'app';
      root.innerHTML = `
        <div id="sidebar-overlay" class="sidebar-overlay"></div>
        <div class="app-shell">
          <aside class="sidebar" id="main-sidebar"></aside>
          <div class="main-area">
            <div id="header-slot"></div>
            <main class="page-content" id="page-container"></main>
          </div>
        </div>`;

      document.getElementById('sidebar-overlay')?.addEventListener('click', () => {
        document.querySelector('.sidebar')?.classList.remove('open');
        document.getElementById('sidebar-overlay')?.classList.remove('active');
      });
    }

    /* always re-render sidebar (active state) and header */
    const sidebar = document.getElementById('main-sidebar');
    if (sidebar) {
      sidebar.innerHTML = renderSidebar(user);
      mountSidebarEvents();
    }

    const headerSlot = document.getElementById('header-slot');
    if (headerSlot) {
      headerSlot.innerHTML = renderHeader(route);
      mountHeaderEvents();
    }

    /* Renderizar ícones Lucide após atualizar o DOM */
    window.lucide?.createIcons();

    return document.getElementById('page-container');
  },
};
