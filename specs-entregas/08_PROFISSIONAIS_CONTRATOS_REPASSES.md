# Entrega 08 — Profissionais, contratos e regras de repasse


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

Implementar configuração financeira sigilosa dos profissionais.

## Entidades

### payment_tables
- uuid;
- name;
- description;
- status;
- calculation_type;
- effective_start_date;
- effective_end_date;
- timestamps;
- deleted_at.

### payment_table_items
- uuid;
- payment_table_uuid;
- specialty;
- appointment_type;
- health_plan_uuid;
- procedure_code;
- fixed_value;
- percentage;
- duration_minutes;
- threshold_quantity;
- extra_value;
- rules_json;
- vigência;
- timestamps.

### professional_payment_configs
- uuid;
- professional_uuid;
- payment_mode;
- payment_table_uuid;
- fixed_monthly_amount;
- fixed_per_attendance_amount;
- hybrid_base_amount;
- hybrid_threshold_quantity;
- hybrid_extra_amount_per_attendance;
- effective_start_date;
- effective_end_date;
- status;
- notes.

## Modos de pagamento

1. Fixo por atendimento.
2. Fixo mensal.
3. Híbrido: fixo + adicional após quantidade definida.

## Services

- CreatePaymentTableService.
- UpdatePaymentTableService.
- AssignPaymentConfigToProfessionalService.
- ResolveProfessionalPaymentRuleService.
- SimulateProfessionalPayoutService.

## Regra de sigilo

Profissional clínico não pode acessar informações contratuais, nem dele mesmo. Somente administrador/direção com permissão.

## Permissões

```txt
professional_payment.view
professional_payment.create
professional_payment.update
professional_payment.delete
professional_payment.simulate
```

## Não implementar

Contas reais, geração financeira automática, TISS.

## Critérios de aceite

- Admin configura pagamento.
- Profissional clínico não acessa valores.
- Vigência funciona.
- Simulação dos 3 modos funciona.
- Auditoria registra alteração de valor.

## Prompt para IA

```md
Implemente somente esta entrega: tabelas de pagamento e configuração sigilosa de repasse dos profissionais. Inclua modos fixo por atendimento, fixo mensal e híbrido. Não gere financeiro real ainda.
```
