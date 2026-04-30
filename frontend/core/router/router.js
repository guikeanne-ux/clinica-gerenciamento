import { appLayout } from '../layout/app-layout.js';

let _routes = [];
let _currentModule = null;
let _currentPath = null;

function matchRoute(path) {
  for (const route of _routes) {
    const params = matchPath(route.path, path);
    if (params !== null) return { route, params };
  }
  return null;
}

function matchPath(pattern, path) {
  const patternParts = pattern.split('/').filter(Boolean);
  const pathParts = path.split('/').filter(Boolean);

  if (patternParts.length !== pathParts.length) return null;

  const params = {};
  for (let i = 0; i < patternParts.length; i++) {
    if (patternParts[i].startsWith(':')) {
      params[patternParts[i].slice(1)] = decodeURIComponent(pathParts[i]);
    } else if (patternParts[i] !== pathParts[i]) {
      return null;
    }
  }
  return params;
}

function normalizePath(path) {
  if (!path) return '/';

  // Accept full URL, relative path with query/hash, or plain pathname.
  const url = new URL(path, window.location.origin);
  return url.pathname || '/';
}

async function resolve(path) {
  const normalizedPath = normalizePath(path);
  if (_currentPath === normalizedPath) return;
  _currentPath = normalizedPath;

  /* Raiz redireciona para dashboard (autenticado) ou login (não autenticado) */
  if (normalizedPath === '/') {
    const { sessionStore } = await import('../auth/session-store.js');
    navigate(sessionStore.isAuthenticated() ? '/dashboard' : '/login', true);
    return;
  }

  const matched = matchRoute(normalizedPath);
  if (!matched) {
    navigate('/404');
    return;
  }

  const { route, params } = matched;

  if (route.guard) {
    const redirect = route.guard(params);
    if (redirect) {
      navigate(redirect);
      return;
    }
  }

  let mod;
  try {
    mod = await route.module();
  } catch (err) {
    console.error('Failed to load module:', err);
    renderError('Não foi possível carregar esta página.');
    return;
  }

  if (_currentModule?.unmount) {
    _currentModule.unmount();
  }
  _currentModule = mod.default ?? mod;

  document.title = route.title ? `${route.title} — Clínica` : 'Clínica';

  const container = await appLayout.render(route.layout, route);

  try {
    await _currentModule.mount(container, params);
  } catch (err) {
    console.error('Module mount error:', err);
    renderError('Erro ao renderizar esta página.');
  }
}

function renderError(msg) {
  const root = document.getElementById('app');
  root.innerHTML = `<div class="page-content"><div class="alert alert-error">${msg}</div></div>`;
}

export function navigate(path, replace = false) {
  if (replace) {
    history.replaceState(null, '', path);
  } else {
    history.pushState(null, '', path);
  }
  resolve(path);
}

export const router = {
  init(routes) {
    _routes = routes;

    window.addEventListener('popstate', () => {
      resolve(location.pathname + location.search);
    });

    document.addEventListener('click', (e) => {
      const link = e.target.closest('[data-link]');
      if (!link) return;
      e.preventDefault();
      const href = link.getAttribute('href') || link.dataset.href;
      if (href) navigate(href);
    });

    window.addEventListener('auth:expired', () => {
      navigate('/login');
    });

    resolve(location.pathname + location.search);
  },
};
