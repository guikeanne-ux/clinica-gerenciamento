import { API_BASE } from './api-config.js';
import { sessionStore } from '../auth/session-store.js';
import { toast } from '../js/toast.js';

function sanitizeUserMessage(message, fallback) {
  const raw = String(message || '').trim();
  if (!raw) return fallback;

  const withoutUuid = raw.replace(
    /\b[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}\b/gi,
    'registro informado'
  );

  return withoutUuid;
}

function firstFieldError(errors) {
  if (!Array.isArray(errors)) return '';
  const first = errors.find((item) => item && typeof item.message === 'string' && item.message.trim() !== '');
  return first ? String(first.message).trim() : '';
}

function getHeaders(extra = {}) {
  const headers = { 'Content-Type': 'application/json', ...extra };
  const token = sessionStore.getToken();
  if (token) headers['Authorization'] = 'Bearer ' + token;
  return headers;
}

async function parseResponse(res) {
  const ct = res.headers.get('content-type') || '';
  if (!ct.includes('application/json')) {
    return { success: res.ok, message: res.ok ? 'OK' : 'Erro no servidor.', data: null, errors: [] };
  }
  const json = await res.json();
  if (json && typeof json === 'object' && typeof json.message === 'string') {
    json.message = sanitizeUserMessage(json.message, 'Não foi possível concluir a ação.');
  }
  return json;
}

async function request(method, path, body = null, options = {}) {
  const url = API_BASE + path;
  const init = { method, headers: getHeaders(options.headers || {}) };

  if (body !== null) init.body = JSON.stringify(body);

  let res;
  try {
    res = await fetch(url, init);
  } catch {
    toast.error('Sem conexão com o servidor. Verifique sua internet e tente novamente.');
    return { success: false, message: 'Sem conexão com o servidor.', data: null, errors: [] };
  }

  const json = await parseResponse(res);

  if (res.status === 401) {
    const isLoginAttempt = path === '/api/v1/auth/login' || path.endsWith('/auth/login');
    if (isLoginAttempt) {
      toast.error(json?.message || 'Usuário ou senha inválidos.');
      return json?.success === false
        ? json
        : { success: false, message: 'Usuário ou senha inválidos.', data: null, errors: [] };
    }

    sessionStore.clear();
    toast.warning('Sua sessão expirou. Faça login novamente.');
    window.dispatchEvent(new CustomEvent('auth:expired'));
    return json?.success === false ? json : { success: false, message: 'Não autenticado.', data: null, errors: [] };
  }

  if (res.status === 403) {
    toast.error('Você não tem permissão para acessar esta área.');
    window.dispatchEvent(new CustomEvent('auth:forbidden'));
    return json?.success === false ? json : { success: false, message: 'Acesso negado.', data: null, errors: [] };
  }

  if (res.status === 404 && options.context !== 'page') {
    toast.error(sanitizeUserMessage(json?.message, 'Recurso não encontrado.'));
  }

  if (res.status === 409) {
    toast.warning(sanitizeUserMessage(json?.message, 'Conflito de dados. Revise as informações e tente novamente.'));
  }

  if (res.status === 422) {
    toast.warning(firstFieldError(json?.errors) || sanitizeUserMessage(json?.message, 'Revise os campos destacados.'));
  }

  if (res.status === 429) {
    toast.warning('Muitas tentativas. Aguarde alguns instantes e tente novamente.');
  }

  if (res.status >= 500) {
    const reqId = String(json?.meta?.request_id || '').trim();
    const detailed = sanitizeUserMessage(json?.message, 'Erro interno no servidor.');
    toast.error(reqId ? `${detailed} (Ref: ${reqId})` : detailed);
  }

  return json;
}

export const http = {
  get:    (path, options)        => request('GET',    path, null, options),
  post:   (path, body, options)  => request('POST',   path, body, options),
  put:    (path, body, options)  => request('PUT',    path, body, options),
  patch:  (path, body, options)  => request('PATCH',  path, body, options),
  delete: (path, options)        => request('DELETE', path, null, options),
};
