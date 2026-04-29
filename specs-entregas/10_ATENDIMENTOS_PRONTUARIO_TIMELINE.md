# Entrega 10 — Atendimentos, prontuário, evolução, anamnese e timeline


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

Implementar o núcleo clínico: atendimento, evolução, anamnese, prontuário livre, áudio e timeline do paciente.

## Entidades

### attendances
- uuid;
- patient_uuid;
- professional_uuid;
- schedule_event_uuid;
- starts_at;
- ends_at;
- duration_minutes;
- status;
- attendance_type;
- modality;
- financial_table_snapshot_json;
- calculated_payout_value;
- internal_notes;
- created_by_user_uuid;
- updated_by_user_uuid;
- finalized_at;
- finalized_by_user_uuid;
- timestamps;
- deleted_at.

Status:
- rascunho;
- em_andamento;
- finalizado;
- cancelado;
- falta.

### clinical_records
- uuid;
- patient_uuid;
- professional_uuid;
- attendance_uuid;
- record_type;
- title;
- content_markdown;
- status;
- version;
- created_by_user_uuid;
- finalized_at;
- timestamps;
- deleted_at.

Tipos:
- evolução;
- anamnese;
- prontuario_livre.

### audio_records
- uuid;
- patient_uuid;
- professional_uuid;
- attendance_uuid;
- title;
- file_uuid;
- duration_seconds;
- status;
- timestamps;
- deleted_at.

## Regras

- Atendimento pode nascer da agenda.
- Não duplicar atendimento para mesmo evento.
- Profissional escreve em tempo real enquanto não finalizado.
- Finalizado fica imutável.
- Complemento posterior vira novo registro.
- Falta/cancelado não gera repasse automático.
- Profissional só edita registros próprios, salvo permissão superior.
- Financeiro não acessa conteúdo clínico detalhado sem permissão.

## Autosave

- Salvar periodicamente ou ao sair do campo.
- Mostrar salvando/salvo/erro.
- Evitar perda de texto.

## Frontend

- Tela de atendimento iniciada pela agenda.
- Editor markdown.
- Botão finalizar.
- Marcar falta.
- Timeline do paciente.
- Gravação de áudio com título, gravar, ouvir e salvar.
- Player de áudio na timeline.

## Permissões

```txt
attendance.view
attendance.create
attendance.update_own
attendance.finalize_own
attendance.cancel
clinical_record.view
clinical_record.create
clinical_record.update_own
clinical_record.finalize_own
clinical_record.view_patient_timeline
audio_record.create
audio_record.play
```

## Não implementar

Builder de questionário, PDF, financeiro real ou TISS.

## Critérios de aceite

- Atendimento nasce da agenda.
- Autosave funciona.
- Finalizado é imutável.
- Áudio salva e reproduz.
- Timeline mostra eventos.
- Permissões funcionam.

## Prompt para IA

```md
Implemente somente esta entrega: atendimentos, prontuário, evolução, anamnese, áudio e timeline. Integre com agenda. Não implemente builder, financeiro real ou TISS.
```
