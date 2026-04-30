import { sessionStore } from '../auth/session-store.js';
import { permissionService } from '../auth/permission-service.js';

export function guardAuth() {
  if (!sessionStore.isAuthenticated()) {
    return '/login';
  }
  return null;
}

export function guardGuest() {
  if (sessionStore.isAuthenticated()) {
    return '/dashboard';
  }
  return null;
}

export function guardPermission(permission) {
  return function () {
    const authFail = guardAuth();
    if (authFail) return authFail;
    if (!permissionService.has(permission)) {
      return '/403';
    }
    return null;
  };
}

export function guardAnyPermission(permissions) {
  return function () {
    const authFail = guardAuth();
    if (authFail) return authFail;
    if (!permissionService.hasAny(permissions)) {
      return '/403';
    }
    return null;
  };
}
