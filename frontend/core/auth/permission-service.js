import { sessionStore } from './session-store.js';

export const permissionService = {
  getPermissions() {
    const user = sessionStore.getUser();
    return Array.isArray(user?.permissions) ? user.permissions : [];
  },

  getRoles() {
    const user = sessionStore.getUser();
    return Array.isArray(user?.roles) ? user.roles : [];
  },

  has(permission) {
    return this.getPermissions().includes(permission);
  },

  hasAny(permissions) {
    const userPerms = this.getPermissions();
    return permissions.some((p) => userPerms.includes(p));
  },

  hasAll(permissions) {
    const userPerms = this.getPermissions();
    return permissions.every((p) => userPerms.includes(p));
  },

  hasRole(role) {
    return this.getRoles().includes(role);
  },
};
