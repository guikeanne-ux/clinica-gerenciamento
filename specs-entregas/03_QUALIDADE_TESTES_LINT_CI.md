# Entrega 03 — Qualidade, testes, lint, PHPStan e CI


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

Configurar qualidade antes de crescer o projeto.

## Escopo

- PEST.
- Coverage.
- PHPStan.
- PHPCS PSR-12.
- PHPCBF.
- Scripts Composer.
- Makefile integrado.
- CI básico.

## Scripts Composer

```json
{
  "scripts": {
    "test": "pest",
    "test:coverage": "pest --coverage",
    "lint": "phpcs",
    "lint:fix": "phpcbf",
    "phpstan": "phpstan analyse"
  }
}
```

## Estrutura de testes

```txt
/backend/tests
  /Feature
  /Unit
  /Support
```

Por módulo:

```txt
/backend/app/Modules/Nome/Tests
  /Unit
  /Feature
```

## CI

Etapas:
1. composer install;
2. lint;
3. phpstan;
4. tests.

## Critérios de aceite

- `make test` funciona.
- `make test-coverage` funciona.
- `make lint` funciona.
- `make lint-fix` funciona.
- `make phpstan` funciona.
- CI executa tudo.

## Prompt para IA

```md
Implemente somente esta entrega: configure PEST, coverage, PHPStan, PHPCS PSR-12, PHPCBF, scripts Composer, Makefile e CI. Não implemente regra de negócio. Rode e corrija os comandos.
```
