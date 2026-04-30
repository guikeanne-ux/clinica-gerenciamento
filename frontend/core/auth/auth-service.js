import { sessionStore } from './session-store.js';
import { http } from '../services/http.js';

export const authService = {
  async login(login, password) {
    const res = await http.post('/api/v1/auth/login', { login, password });
    if (res.success && res.data?.token) {
      sessionStore.setToken(res.data.token);
      await this.loadMe();
    }
    return res;
  },

  async logout() {
    try {
      await http.post('/api/v1/auth/logout');
    } catch {
      /* ignore errors on logout */
    } finally {
      sessionStore.clear();
    }
  },

  async loadMe() {
    try {
      const res = await http.get('/api/v1/auth/me');
      if (res.success && res.data) {
        sessionStore.setUser(res.data);
        return res.data;
      }
    } catch {
      /* noop */
    }
    return null;
  },

  async ensureUser() {
    if (!sessionStore.getUser()) {
      return await this.loadMe();
    }
    return sessionStore.getUser();
  },

  isAuthenticated() {
    return sessionStore.isAuthenticated();
  },

  getUser() {
    return sessionStore.getUser();
  },
};
