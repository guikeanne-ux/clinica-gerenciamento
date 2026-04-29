# Entrega 02 — Fundação técnica, Docker e skeleton backend


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

Criar a base técnica do projeto: Docker, estrutura de pastas, Composer, bootstrap PHP, Eloquent sem Laravel, dotenv, roteamento, resposta JSON, tratamento de erro e healthcheck.

## Estrutura inicial

```txt
/backend
  /app
    /Core
      /Http
      /Database
      /Security
      /Support
      /Exceptions
      /Contracts
    /Modules
      /Shared
  /config
  /database
    /migrations
    /seeders
    /factories
  /routes
  /storage
    /logs
    /cache
    /uploads
  /tests
  /docs
/frontend
  /assets
  /core
    /css
    /js
    /components
    /services
    /router
    /store
    /utils
  /modules
/docker
```

## Docker obrigatório

- PHP 8.3.
- MariaDB.
- phpMyAdmin.
- Volume persistente.
- `.env.example`.

## Makefile

Criar:

```bash
make up
make down
make destroy
make composer-install
make test
make test-coverage
make lint
make lint-fix
make phpstan
make migrate
make seed
```

## Composer

Instalar:
- illuminate/database
- illuminate/events
- symfony/routing
- symfony/validator
- vlucas/phpdotenv
- firebase/php-jwt
- fakerphp/faker
- pestphp/pest
- phpstan/phpstan
- squizlabs/php_codesniffer

## Backend mínimo

- Bootstrap da aplicação.
- Conexão Eloquent.
- Roteador Symfony.
- Dispatcher.
- Response JSON padronizada.
- Tratamento centralizado de exceções.
- Rota `GET /api/v1/health`.

## Testes mínimos

- Healthcheck retorna 200.
- Response segue padrão global.

## Não implementar

Login, usuários, pacientes, agenda, financeiro, TISS ou telas de negócio.

## Critérios de aceite

- `make up` sobe ambiente.
- `GET /api/v1/health` funciona.
- Eloquent conecta no MariaDB.
- Erros retornam JSON.
- Composer roda dentro do container.
- `make down` e `make destroy` funcionam.

## Prompt para IA

```md
Implemente somente esta entrega: fundação técnica, Docker e skeleton backend. Crie estrutura, Docker, Composer, bootstrap, Eloquent sem Laravel, dotenv, roteamento Symfony, response JSON, tratamento de erro e healthcheck. Não implemente módulos de negócio. Rode testes e entregue resultados.
```
