# Entrega 16 — Revisão final, hardening e documentação


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

Estabilizar o MVP antes de uso real.

## Segurança

Revisar:
- JWT;
- hash;
- expiração;
- permissões;
- policies contextuais;
- uploads;
- downloads sensíveis;
- XSS;
- CSRF conforme arquitetura;
- SQL injection;
- headers;
- logs sensíveis;
- mascaramento;
- soft delete.

## LGPD

Verificar:
- dados sensíveis protegidos;
- auditoria de acesso a prontuário;
- auditoria de download/exportação;
- menor privilégio por perfil;
- financeiro sem acesso clínico indevido;
- profissional sem acesso contratual.

## Testes

Garantir:
- services acima de 80%;
- permissões;
- upload;
- agenda;
- atendimento imutável;
- financeiro/snapshot;
- TISS bloqueante;
- importação.

## Documentação

Atualizar:
- README;
- subida do ambiente;
- migrations/seeders;
- testes;
- OpenAPI;
- arquitetura;
- permissões;
- módulos;
- fluxo TISS;
- importação.

## Performance

Revisar:
- índices;
- paginação;
- N+1;
- BLOBs;
- listagens;
- frontend.

## UX

Revisar:
- responsividade;
- modais;
- multiselect;
- máscaras;
- loading;
- empty states;
- views por perfil:
  - administrador;
  - profissional;
  - financeiro;
  - recepcionista;
  - contas médicas.

## Checklist

- `make up`
- `make migrate`
- `make seed`
- login
- ACL
- auditoria
- pacientes
- profissionais
- agenda
- atendimento
- prontuário/áudio
- timeline
- modelos
- financeiro
- importação
- TISS
- tarefas
- OpenAPI
- coverage
- lint
- phpstan

## Prompt para IA

```md
Implemente somente esta entrega: revisão final, hardening e documentação. Não crie módulos novos. Corrija segurança, permissões, testes, documentação, UX e performance. Rode tudo e entregue relatório final.
```
