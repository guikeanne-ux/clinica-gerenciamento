# Prompt mestre e regras globais da IA implementadora


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


## Contexto

Você vai implementar um ERP clínico enxuto para clínica multiprofissional especializada em pacientes neurodivergentes. O sistema precisa resolver operação real: cadastros, agenda, atendimentos, prontuário, arquivos, financeiro, repasses, importação e TISS.

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
- Sem framework.

## Arquitetura obrigatória

- Monolito modular.
- Separação por módulos de negócio.
- Camadas: Presentation, Application, Domain, Infrastructure e Tests.
- Services concentram regra de negócio.
- Controllers chamam services.
- Policies/guards/middlewares controlam acesso.
- Eventos internos podem ser usados para auditoria e integrações futuras.

## UUID obrigatório

- Todo registro novo deve ter UUID v4.
- APIs públicas trafegam UUID.
- Logs e auditorias registram UUID.
- `id` numérico, se existir internamente, nunca aparece em rota, response ou frontend.
- Preferência: criar tabelas já com UUID como chave primária.

## Segurança obrigatória

- Hash forte para senhas.
- JWT com expiração.
- Middleware de autenticação.
- Middleware de autorização.
- Validação de entrada.
- Auditoria em ações sensíveis.
- Soft delete em entidades críticas.
- Proteção de upload.
- Nunca logar senha/token puro.
- Mascarar dados sensíveis quando necessário.

## Padrão de resposta da API

```json
{
  "success": true,
  "message": "Operação realizada com sucesso.",
  "data": {},
  "meta": {},
  "errors": []
}
```

## Padrão de erro

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

## Instrução fixa para cada entrega

Implemente apenas o arquivo recebido. Não antecipe etapas futuras. Rode testes, lint e análise estática. Entregue resumo objetivo com arquivos, comandos, resultado dos testes e pendências reais.
