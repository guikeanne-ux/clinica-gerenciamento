# Entrega 12 — Financeiro operacional e repasses


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

Implementar contas a pagar/receber, repasses e geração automática a partir de atendimentos finalizados.

## Entidades

### financial_categories
- uuid;
- name;
- type;
- status.

### financial_entries
- uuid;
- entry_type;
- category_uuid;
- description;
- gross_amount;
- discount_amount;
- addition_amount;
- net_amount;
- competence_date;
- due_date;
- paid_or_received_at;
- status;
- recurrence_rule;
- origin;
- patient_uuid;
- professional_uuid;
- supplier_uuid;
- attendance_uuid;
- payment_method;
- notes;
- calculation_snapshot_json;
- created_by_user_uuid;
- timestamps;
- deleted_at.

### payouts
- uuid;
- professional_uuid;
- attendance_uuid;
- financial_entry_uuid;
- calculated_amount;
- calculation_snapshot_json;
- status;
- generated_by_user_uuid;
- generated_at;
- canceled_at;
- cancel_reason.

## Regras

- Atendimento finalizado pode gerar repasse.
- Cancelado/falta não gera repasse.
- Cálculo usa regra vigente no atendimento.
- Snapshot salva contexto.
- Alteração posterior não altera lançamento antigo.
- Regenerar exige permissão.
- Sem configuração de pagamento, gerar pendência/alerta.

## Services

- CreateFinancialEntryService.
- MarkFinancialEntryAsPaidService.
- GeneratePayoutFromAttendanceService.
- ResolvePayoutValueService.
- CancelPayoutService.
- FinanceReportService.

## Relatórios mínimos

- Total recebido.
- Total a pagar.
- Total por profissional.
- Total por convênio.
- Total por categoria.
- Previsão de repasses.
- Fluxo de caixa simples.

## Frontend

- Dashboard financeiro.
- Contas a pagar.
- Contas a receber.
- Repasses.
- Categorias.
- Filtros.
- Baixa manual.

## Permissões

```txt
finance.view
finance.create
finance.update
finance.delete
finance.mark_paid
finance.reports
payout.view
payout.generate
payout.regenerate
payout.cancel
```

## Não implementar

Conciliação avançada, TISS completo, glosas avançadas.

## Critérios de aceite

- Conta manual funciona.
- Atendimento finalizado gera repasse.
- Snapshot impede alteração retroativa.
- Falta/cancelado não gera.
- Relatórios básicos funcionam.

## Prompt para IA

```md
Implemente somente esta entrega: financeiro operacional e repasses. Integre atendimentos finalizados, snapshot de cálculo, contas e relatórios. Não implemente TISS completo.
```
