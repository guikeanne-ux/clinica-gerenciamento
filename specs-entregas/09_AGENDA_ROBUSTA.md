# Entrega 09 — Agenda robusta


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

Implementar a agenda como pilar do sistema.

## Tipos

### Evento comum
Pode não ter paciente: reunião, entrevista, feriado, férias, lembrete, bloqueio, evento do dia inteiro.

### Atendimento agendado
Precisa de paciente, profissional e horário.

## Entidades

### schedule_event_types
- uuid;
- name;
- category;
- color;
- requires_patient;
- requires_professional;
- can_generate_attendance;
- can_generate_financial_entry;
- status.

### schedule_events
- uuid;
- title;
- description;
- event_type_uuid;
- patient_uuid;
- professional_uuid;
- starts_at;
- ends_at;
- all_day;
- status;
- origin;
- recurrence_rule;
- recurrence_group_uuid;
- room_or_location;
- color_override;
- created_by_user_uuid;
- updated_by_user_uuid;
- canceled_at;
- canceled_by_user_uuid;
- cancel_reason;
- timestamps;
- deleted_at.

Status:
- agendado;
- confirmado;
- realizado;
- cancelado;
- falta;
- remarcado;
- bloqueado.

## Recorrência

Implementar semanal, dias da semana, data final e intervalo.

## Regras

- Evitar conflito do mesmo profissional.
- Permitir conflito só com permissão especial.
- Evento bloqueado impede atendimento.
- Cancelado não gera atendimento/repasse.
- Alterações relevantes são auditadas.

## Frontend

- Visão dia.
- Visão semana.
- Visão mês.
- Filtros por profissional, paciente, data, tipo e status.
- Cores por profissional/tipo.
- Legenda.
- Modal de criação/edição.
- Evento comum.
- Atendimento agendado.
- Recorrência.
- Cancelamento.
- Marcar falta.
- Botão “Iniciar atendimento”.

## Permissões

```txt
schedule.view
schedule.view_all
schedule.create
schedule.update
schedule.cancel
schedule.delete
schedule.override_conflict
schedule.create_attendance
```

## Não implementar

Atendimento clínico completo, financeiro real ou TISS.

## Critérios de aceite

- Dia/semana/mês funcionam.
- Evento comum sem paciente funciona.
- Atendimento exige paciente/profissional.
- Recorrência funciona.
- Conflito bloqueia.
- Profissional visualiza conforme permissão.
- Recepcionista cria.

## Prompt para IA

```md
Implemente somente esta entrega: agenda robusta com eventos comuns, atendimentos agendados, recorrência, conflitos, filtros, permissões, auditoria e frontend. Não implemente atendimento clínico completo ou TISS.
```
