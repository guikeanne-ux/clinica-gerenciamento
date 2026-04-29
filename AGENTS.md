# AGENTS.md — Regras permanentes do projeto

## Contexto do projeto

Este repositório contém um sistema de gestão para clínica multiprofissional especializada em pacientes neurodivergentes.

O sistema deve ser construído incrementalmente, entrega por entrega, seguindo os arquivos da pasta `specs-entregas/`.

Nunca tente construir o sistema inteiro de uma vez.

## Stack obrigatória

Backend:
- PHP 8.3 puro.
- Eloquent ORM sem Laravel.
- symfony/routing.
- symfony/validator.
- vlucas/phpdotenv.
- firebase/php-jwt.
- PEST.
- PHPStan.
- PSR-12.
- Swagger/OpenAPI.
- MariaDB.
- Docker.

Frontend:
- HTML.
- CSS.
- JavaScript vanilla.
- SPA simples.
- Sem React, Vue, Angular ou framework equivalente.
- Design system próprio antes das telas de negócio.

Infra:
- Docker com PHP 8.3, MariaDB e phpMyAdmin.
- Makefile com comandos padronizados.

## Arquitetura obrigatória

O sistema é um monolito modular.

Organização esperada:
- Backend com API HTTP versionada em `/api/v1`.
- Módulos por domínio de negócio.
- Separação clara entre:
  - Presentation/Controllers;
  - Application/Services;
  - Domain;
  - Infrastructure/Repositories;
  - Tests.

Regras:
- Controllers devem ser finos.
- Services concentram regra de negócio.
- Repositories encapsulam consultas persistentes complexas.
- Validators/DTOs tratam entrada.
- Policies/Guards/Middlewares tratam autorização.
- Eventos internos podem ser usados para auditoria e integrações futuras.
- Não misturar regra clínica, financeira, autenticação e TISS no mesmo lugar.

## Regra obrigatória de UUID

Todas as entidades devem usar UUID como identificador público.

Regras:
- Todo novo registro deve gerar UUID v4 no backend.
- APIs públicas nunca devem expor `id` numérico.
- Rotas, payloads e responses devem trafegar UUID.
- Logs e auditorias devem registrar UUID.
- Se existir `id` numérico interno por conveniência, ele nunca aparece no frontend, nas rotas ou nas responses.
- Preferência: criar tabelas já com UUID como chave primária.

## Segurança obrigatória

Este sistema lida com dados sensíveis de saúde.

Obrigatório:
- Hash forte para senhas.
- JWT com expiração.
- Middleware de autenticação.
- Middleware de autorização.
- ACL por perfil e por permissão.
- Auditoria em ações críticas.
- Soft delete em entidades relevantes.
- Validação forte de entrada.
- Proteção contra SQL injection.
- Proteção contra XSS.
- Validação de upload.
- Mascaramento de dados sensíveis quando necessário.
- Nunca logar senha, token puro ou conteúdo sensível sem necessidade explícita.

## Padrão de response da API

Sucesso:

```json
{
  "success": true,
  "message": "Operação realizada com sucesso.",
  "data": {},
  "meta": {},
  "errors": []
}
```

Erro:

```json
{
  "success": false,
  "message": "Erro de validação.",
  "data": null,
  "meta": {},
  "errors": [
    {
      "field": "email",
      "message": "E-mail inválido."
    }
  ]
}
```

## Regras de execução por entrega

Ao receber uma entrega:

1. Leia este `AGENTS.md`.
2. Leia somente o arquivo de entrega solicitado em `specs-entregas/`.
3. Implemente apenas o escopo daquele arquivo.
4. Não antecipe módulos futuros.
5. Crie código completo, migrations, seeders/factories e testes quando aplicável.
6. Rode os comandos possíveis.
7. Corrija erros reais.
8. Entregue resumo com:
   - arquivos criados/alterados;
   - como rodar;
   - como testar;
   - resultado dos testes;
   - pendências reais;
   - o que não foi implementado por pertencer a etapas futuras.

## Comandos esperados

Quando existirem no projeto, use:

```bash
make up
make down
make destroy
make composer-install
make migrate
make seed
make test
make test-coverage
make lint
make lint-fix
make phpstan
```

Se algum comando ainda não existir porque pertence a uma entrega futura, explique isso sem inventar resultado.

## Critério de parada

Não avance para a próxima entrega se:
- testes estiverem quebrados;
- lint estiver quebrado;
- PHPStan estiver quebrado;
- migrations não rodarem;
- escopo da entrega atual não estiver concluído.

## Restrições fortes

Nunca:
- trocar a stack;
- usar Laravel;
- usar framework frontend;
- criar “exemplo simplificado” em vez de implementação real;
- deixar ACL para depois;
- expor `id` numérico;
- colocar regra de negócio no controller;
- criar campo JSON bruto para usuário final;
- implementar TISS antes da base clínica/financeira;
- implementar telas de negócio antes do design system;
- instalar dependência de produção sem justificar.
