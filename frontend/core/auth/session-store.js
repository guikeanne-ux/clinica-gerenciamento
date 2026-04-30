const TOKEN_KEY = 'clinica_token';
const USER_KEY  = 'clinica_user';

export const sessionStore = {
  setToken(token) {
    localStorage.setItem(TOKEN_KEY, token);
  },

  getToken() {
    return localStorage.getItem(TOKEN_KEY);
  },

  removeToken() {
    localStorage.removeItem(TOKEN_KEY);
  },

  setUser(user) {
    try {
      localStorage.setItem(USER_KEY, JSON.stringify(user));
    } catch {
      /* quota exceeded – ignore */
    }
  },

  getUser() {
    try {
      const raw = localStorage.getItem(USER_KEY);
      return raw ? JSON.parse(raw) : null;
    } catch {
      return null;
    }
  },

  removeUser() {
    localStorage.removeItem(USER_KEY);
  },

  clear() {
    this.removeToken();
    this.removeUser();
  },

  isAuthenticated() {
    return !!this.getToken();
  },
};
