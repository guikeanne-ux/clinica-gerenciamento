const moduleCache = new Map();

const moduleMap = {
  'auth/login':           () => import('../modules/auth/login.js'),
  'dashboard/dashboard':  () => import('../modules/dashboard/dashboard.js'),
  'company/company':      () => import('../modules/company/company.js'),
  'files/files-list':     () => import('../modules/files/files-list.js'),
  'patients/patients-list':   () => import('../modules/patients/patients-list.js'),
  'patients/patient-form':    () => import('../modules/patients/patient-form.js'),
  'patients/patient-detail':  () => import('../modules/patients/patient-detail.js'),
  'professionals/professionals-list': () => import('../modules/professionals/professionals-list.js'),
  'professionals/professional-form':  () => import('../modules/professionals/professional-form.js'),
  'payment/payment-tables':          () => import('../modules/payment/payment-tables.js'),
  'payment/payment-table-form':      () => import('../modules/payment/payment-table-form.js'),
  'payment/professional-payment-config': () => import('../modules/payment/professional-payment-config.js'),
  'specialties/specialties': () => import('../modules/specialties/specialties.js'),
  'schedule/schedule-page':        () => import('../modules/schedule/schedule-page.js'),
  'schedule/appointment-types':    () => import('../modules/schedule/appointment-types.js'),
  'design-system/design-system': () => import('../modules/design-system/design-system.js'),
  'errors/error-403': () => import('../modules/errors/error-403.js'),
  'errors/error-404': () => import('../modules/errors/error-404.js'),
};

export async function loadModule(name) {
  if (moduleCache.has(name)) return moduleCache.get(name);
  const loader = moduleMap[name];
  if (!loader) throw new Error(`Module not found: ${name}`);
  const mod = await loader();
  moduleCache.set(name, mod);
  return mod;
}
