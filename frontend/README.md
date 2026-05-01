# Frontend — ClinicaGest SPA

SPA vanilla (HTML + CSS + JavaScript ES Modules), sem frameworks.

## Como abrir

Com o Docker rodando (`make up`), acesse:

```
http://localhost:8080/
```

A raiz redireciona para `/login` se não autenticado, ou `/dashboard` se autenticado.

---

## Estrutura de rotas

| Rota | Módulo | Proteção |
|------|--------|----------|
| `/login` | `modules/auth/login.js` | Público (guest only) |
| `/dashboard` | `modules/dashboard/dashboard.js` | Autenticado |
| `/company` | `modules/company/company.js` | `company.view` |
| `/patients` | `modules/patients/patients-list.js` | `patients.view` |
| `/patients/new` | `modules/patients/patient-form.js` | `patients.create` |
| `/patients/:uuid` | `modules/patients/patient-detail.js` | `patients.view` |
| `/professionals` | `modules/professionals/professionals-list.js` | `professionals.view` |
| `/professionals/new` | `modules/professionals/professional-form.js` | `professionals.create` |
| `/professionals/:uuid` | `modules/professionals/professional-form.js` | `professionals.view` |
| `/payment-tables` | `modules/payment/payment-tables.js` | `professional_payment.view` |
| `/payment-tables/new` | `modules/payment/payment-table-form.js` | `professional_payment.create` |
| `/payment-tables/:uuid` | `modules/payment/payment-table-form.js` | `professional_payment.view` |
| `/professional-payment` | `modules/payment/professional-payment-config.js` | `professional_payment.view` |
| `/design-system` | `modules/design-system/design-system.js` | Autenticado |
| `/403` | `modules/errors/error-403.js` | Autenticado |
| `/404` | `modules/errors/error-404.js` | Público |

---

## Como criar um novo módulo

1. Crie `frontend/modules/<area>/<nome>.js`:

```js
export default {
  async mount(container, params) {
    container.innerHTML = `<h1>Minha tela</h1>`;
    // params.uuid, etc. (params de rota)
  },
  unmount() {
    // limpeza opcional
  },
};
```

2. Registre em `core/modules-loader.js`:

```js
'area/nome': () => import('../modules/area/nome.js'),
```

3. Adicione a rota em `core/router/routes.js`:

```js
{
  path: '/minha-rota',
  layout: 'app',
  guard: guardPermission('permissao.view'),
  module: () => import('../modules-loader.js').then(m => m.loadModule('area/nome')),
  title: 'Minha tela',
  breadcrumb: ['Início', 'Minha tela'],
},
```

4. Adicione item no menu em `core/layout/sidebar.js`, no array `NAV_GROUPS`.

---

## Como usar o HTTP service

```js
import { http } from '../../core/services/http.js';

// GET
const res = await http.get('/api/v1/patients');

// POST
const res = await http.post('/api/v1/patients', { full_name: 'João', birth_date: '2000-01-01' });

// PUT
const res = await http.put(`/api/v1/patients/${uuid}`, data);

// DELETE
const res = await http.delete(`/api/v1/patients/${uuid}`);

// Response padrão:
// { success: boolean, message: string, data: any, errors: [] }
```

O token JWT é injetado automaticamente. 401 limpa sessão e redireciona ao login.

---

## Como proteger uma rota

Em `routes.js`, use os guards disponíveis em `core/router/guards.js`:

```js
import { guardAuth, guardPermission, guardAnyPermission } from './guards.js';

// Apenas autenticado
guard: guardAuth,

// Com permissão específica
guard: guardPermission('patients.view'),

// Com qualquer uma das permissões
guard: guardAnyPermission(['admin.full', 'patients.view']),
```

---

## Como verificar permissão dentro de um módulo

```js
import { permissionService } from '../../core/auth/permission-service.js';

if (permissionService.has('patients.create')) {
  // mostrar botão de criar
}

if (permissionService.hasAny(['admin.full', 'patients.update'])) {
  // mostrar edição
}
```

---

## Como usar o router (navegação)

```js
import { navigate } from '../../core/router/router.js';

// Navegar
navigate('/patients');
navigate(`/patients/${uuid}`);

// Links com data-link são interceptados automaticamente:
// <a href="/patients" data-link>Pacientes</a>
// <button data-link href="/patients/new">Novo</button>
```

---

## Como adicionar item no menu lateral

Em `core/layout/sidebar.js`, dentro do array `NAV_GROUPS`, adicione no grupo adequado:

```js
{
  path: '/minha-rota',
  label: 'Minha Tela',
  icon: iconMinhaFuncao(),
  permission: 'minha.permissao',
}
```

Itens sem permissão (`always: true`) aparecem para todos os usuários autenticados.

---

## Como executar validação manual básica

1. Acesse `http://localhost:8080/`
2. Você deve ser redirecionado para `/login`
3. Faça login com credenciais válidas
4. Verifique que o dashboard carrega
5. Navegue pelo menu lateral
6. Acesse `/patients`, `/professionals`, `/company`
7. Tente acessar uma rota protegida sem permissão → deve ir para `/403`
8. Acesse uma rota inexistente → deve ir para `/404`
9. Faça logout e tente acessar `/dashboard` → deve ir para `/login`

---

## Legado

Os seguintes arquivos HTML foram mantidos como referência histórica e não são usados pelo fluxo principal:

- `modules/app/layout.html`
- `modules/auth/login.html`
- `modules/company/settings.html`
- `modules/patients/index.html`
- `modules/professional-payment/index.html`
- `modules/professionals/index.html`
- `design-system.html`

## Módulo desativado no fluxo principal (Entrega 09.1)

- Fornecedores (`/suppliers`) foi removido da navegação principal da SPA.
- Endpoints legados podem permanecer apenas por compatibilidade técnica temporária.
