# Entrega 11 — Modelos, questionários e builder visual


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

Criar modelos de prontuário/questionários com builder visual. Usuário nunca deve editar JSON bruto.

## Entidades

### templates
- uuid;
- name;
- description;
- destination_module;
- template_type;
- owner_user_uuid;
- owner_professional_uuid;
- sharing_scope;
- status;
- version;
- structure_json;
- timestamps;
- deleted_at.

### template_versions
- uuid;
- template_uuid;
- version;
- structure_json;
- created_by_user_uuid;
- created_at.

### template_answers
- uuid;
- template_uuid;
- template_version_uuid;
- patient_uuid;
- professional_uuid;
- attendance_uuid;
- answers_json;
- rendered_document_markdown;
- status;
- timestamps;
- finalized_at.

## Blocos mínimos

- Título.
- Subtítulo.
- Parágrafo.
- Texto curto.
- Texto longo.
- Número.
- Data.
- Sim/não.
- Múltipla escolha.
- Escolha única.
- Checkbox.
- Escala.
- Seção.
- Campo obrigatório.
- Texto de ajuda.

## Regras

- Estrutura interna pode ser JSON versionado.
- Usuário não vê JSON.
- Respostas antigas preservam versão.
- Admin cria modelo global.
- Profissional cria modelo próprio.
- Ao finalizar resposta, gerar markdown consolidado.

## Frontend

- Listagem de modelos.
- Builder visual.
- Adicionar/remover/duplicar/reordenar bloco.
- Configurar obrigatoriedade e opções.
- Preview.
- Responder modelo no prontuário.

## Permissões

```txt
templates.view
templates.create_own
templates.update_own
templates.create_global
templates.update_global
templates.delete
templates.answer
templates.finalize_answer
```

## Não implementar

PDF, financeiro ou TISS.

## Critérios de aceite

- Builder funciona.
- JSON não aparece ao usuário.
- Versionamento preserva respostas antigas.
- Permissões funcionam.

## Prompt para IA

```md
Implemente somente esta entrega: modelos e questionários com builder visual. Salve JSON internamente, mas não exponha ao usuário. Não implemente PDF, financeiro ou TISS.
```
