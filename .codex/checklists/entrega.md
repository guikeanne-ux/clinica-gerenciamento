# Checklist de validação por entrega

Antes de avançar para a próxima entrega, validar:

## Escopo

- [ ] Implementou somente a entrega atual.
- [ ] Não antecipou módulo futuro.
- [ ] Não deixou item obrigatório sem explicar.
- [ ] Não mudou a stack.

## Backend

- [ ] Migrations criadas.
- [ ] Models criados.
- [ ] Services criados.
- [ ] Controllers finos.
- [ ] Validators/DTOs quando aplicável.
- [ ] Policies/middlewares quando aplicável.
- [ ] UUID nas rotas/responses.
- [ ] Nenhum `id` numérico exposto.
- [ ] Responses seguem padrão global.
- [ ] Erros seguem padrão global.

## Segurança

- [ ] Entrada validada.
- [ ] Permissões aplicadas.
- [ ] Auditoria aplicada quando crítico.
- [ ] Dados sensíveis protegidos.
- [ ] Upload validado, quando existir.
- [ ] Soft delete quando aplicável.

## Frontend

- [ ] Usa design system.
- [ ] Responsivo.
- [ ] Sem componente HTML cru como experiência final.
- [ ] Máscaras aplicadas quando necessário.
- [ ] Estados de erro/sucesso/loading.
- [ ] Não expõe dado sensível para perfil indevido.

## Testes e qualidade

- [ ] Testes unitários de services.
- [ ] Testes de integração de endpoints críticos.
- [ ] Testes de permissão.
- [ ] `make test` passa.
- [ ] `make lint` passa.
- [ ] `make phpstan` passa.
- [ ] Coverage adequado quando aplicável.

## Git

- [ ] Revisou diff.
- [ ] Commit feito.
- [ ] Mensagem de commit clara.
