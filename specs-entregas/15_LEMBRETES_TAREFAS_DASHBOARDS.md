# Entrega 15 — Lembretes, tarefas operacionais e dashboards


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

Criar tarefas operacionais para evitar esquecimentos que geram perda de faturamento e pendências clínicas/administrativas.

## Entidades

### operational_tasks
- uuid;
- title;
- description;
- task_type;
- priority;
- origin_module;
- related_entity_type;
- related_entity_uuid;
- responsible_user_uuid;
- responsible_role_uuid;
- trigger_at;
- due_at;
- recurrence_rule;
- status;
- completion_condition;
- completed_at;
- completed_by_user_uuid;
- escalation_config_json;
- timestamps;
- deleted_at.

### operational_task_history
- uuid;
- task_uuid;
- action;
- old_status;
- new_status;
- user_uuid;
- notes;
- created_at.

## Tipos

Agenda:
- token/senha/autorização;
- confirmar presença;
- solicitar documento;
- validar convênio.

Atendimento:
- confirmar comparecimento;
- finalizar atendimento;
- pendência administrativa;
- anexar evidência.

TISS:
- revisar atendimentos;
- gerar guias;
- revisar erros;
- fechar lote;
- exportar XML;
- enviar portal;
- conferir protocolo;
- tratar glosa.

Financeiro:
- contas recorrentes;
- repasses;
- conferência de recebimentos.

Geral:
- documentos;
- renovação cadastral;
- revisão de operadora;
- atualização TISS/TUSS.

## Disparadores mínimos

- Agendamento com convênio que exige token cria tarefa.
- Dia do atendimento lembra recepção.
- Atendimento sem autorização cria pendência.
- Falta TUSS cria tarefa.
- Fechamento mensal cria lembrete.
- Glosa cria tarefa.

## Regras

- Lembrete não substitui validação bloqueante.
- Tarefa concluída mantém histórico.
- Vencida destaca visualmente.
- Crítica aparece em dashboard.
- Pode ser manual ou automática.
- Pode escalar.

## Frontend

- Dashboard de pendências.
- Minhas tarefas.
- Tarefas por módulo.
- Vencidas.
- Criar manual.
- Concluir.
- Reatribuir.
- Histórico.

## Permissões

```txt
tasks.view
tasks.create
tasks.update
tasks.complete
tasks.reassign
tasks.escalate
tasks.view_all
```

## Critérios de aceite

- Tarefa manual funciona.
- Automática por token funciona.
- Vencida destaca.
- Crítica aparece no dashboard.
- Histórico funciona.
- Permissão funciona.

## Prompt para IA

```md
Implemente somente esta entrega: tarefas e lembretes operacionais. Integre com agenda, atendimento, TISS e financeiro. Garanta histórico, vencimento, prioridade e dashboard.
```
