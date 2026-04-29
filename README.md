# Clinica Gerenciamento

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
