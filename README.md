# Clinica Gerenciamento

## Escopo atual do MVP (revisão 09.1)

- Agenda revisada com separação entre evento comum e atendimento agendado.
- Simulador de repasse revisado com cálculo por configuração/tabela e memória de cálculo.
- Fornecedores desativados do fluxo principal da SPA (mantidos apenas endpoints legados para compatibilidade temporária).

## Qualidade de codigo (Entrega 03)

Com Docker ativo:

- `make test`
- `make test-coverage`
- `make lint`
- `make lint-fix`
- `make phpstan`

Sem Docker (local):

- `cd backend && composer test`
- `cd backend && composer test:coverage`
- `cd backend && composer lint`
- `cd backend && composer lint:fix`
- `cd backend && composer phpstan`
