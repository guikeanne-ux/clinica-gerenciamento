import { router } from '../router/router.js';
import { routes } from '../router/routes.js';

/* Toast global (acessível via window.toast) */
import { toast } from './toast.js';
window.toast = toast;

/* Inicializar SPA */
router.init(routes);
