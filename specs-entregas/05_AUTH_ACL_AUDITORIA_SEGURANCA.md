# Entrega 05 — Auth, usuários, ACL, auditoria e segurança base


## Como usar esta entrega

1. Envie somente este arquivo para a IA implementadora.
2. Peça para implementar apenas este escopo.
3. Exija código completo, migrations, testes e validação local.
4. Só avance para o próximo arquivo depois de testar e validar.
5. Se a IA antecipar etapas futuras, mande voltar ao escopo.

Regras globais:
- PHP 8.3 puro no backend.
- Eloquent sem Laravel.
- Frontend em HTML, CSS e JavaScript vanilla.
- Monolito modular.
- API versionada em `/api/v1`.
- UUID como identificador público.
- Nunca expor `id` numérico nas APIs.
- Services com regras de negócio.
- Controllers finos.
- Validators/DTOs para entrada.
- Policies/middlewares para autorização.
- Auditoria em ações críticas.
- Testes automatizados por entrega.


## Objetivo

Implementar autenticação e autorização antes de lidar com dados clínicos.

## Módulos

```txt
/backend/app/Modules/Auth
/backend/app/Modules/ACL
/backend/app/Modules/Audit
```

## Tabelas

- users
- roles
- permissions
- role_permissions
- user_roles
- user_permission_overrides
- audit_logs
- failed_login_attempts

Todas com UUID.

## Usuário

Campos:
- uuid;
- name;
- login;
- email;
- password_hash;
- status;
- last_access_at;
- person_uuid;
- professional_uuid;
- created_at;
- updated_at;
- deleted_at.

Regras:
- login único;
- senha com hash forte;
- usuário inativo não acessa;
- cada usuário deve ter ao menos um perfil.

## Perfis iniciais

- Administrador.
- Direção.
- Financeiro.
- Secretária/Recepção.
- Profissional clínico.
- Contas médicas.
- RH.
- Auditor/leitura.

## Endpoints

```txt
POST /api/v1/auth/login
POST /api/v1/auth/logout
GET  /api/v1/auth/me
POST /api/v1/auth/change-password
```

## Auditoria

Registrar:
- login;
- logout;
- falha de login;
- criação/edição/inativação de usuário;
- alteração de perfis;
- alteração de permissões;
- troca de senha.

## Frontend

- Login.
- Layout autenticado base.
- Menu por permissão.
- Tela “meus dados”.
- Administração básica de usuários/perfis, se couber.

## Não implementar

Pacientes, profissionais completos, agenda, financeiro ou TISS.

## Critérios de aceite

- Login retorna JWT.
- Rota protegida exige token.
- Permissão negada retorna 403.
- Login duplicado é bloqueado.
- Usuário inativo não loga.
- Auditoria registra eventos.
- Testes cobrem autenticação e autorização.

## Prompt para IA

```md
Implemente somente esta entrega: Auth, ACL e Audit. Crie login JWT, usuários, perfis, permissões, overrides, auditoria, middlewares, frontend de login e layout autenticado. Não implemente módulos clínicos.
```
