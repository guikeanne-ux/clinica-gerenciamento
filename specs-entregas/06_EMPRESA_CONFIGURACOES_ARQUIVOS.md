# Entrega 06 — Empresa, configurações e arquivos base


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

Implementar dados da clínica e base de arquivos. Inicialmente, arquivos devem ser salvos em BLOB no banco.

## Módulos

```txt
/backend/app/Modules/Company
/backend/app/Modules/Files
```

## Empresa

Tabela `companies`:
- uuid;
- legal_name;
- trade_name;
- document;
- state_registration;
- municipal_registration;
- email;
- phone;
- website;
- endereço completo;
- document_footer_text;
- bank_data_json;
- business_hours_json;
- timezone;
- status;
- timestamps;
- deleted_at.

Endpoints:
```txt
GET /api/v1/company
PUT /api/v1/company
```

## Arquivos

Tabela `files`:
- uuid;
- original_name;
- internal_name;
- mime_type;
- extension;
- size_bytes;
- checksum_hash;
- content_blob;
- optimized;
- related_module;
- related_entity_type;
- related_entity_uuid;
- uploaded_by_user_uuid;
- classification;
- status;
- timestamps;
- deleted_at.

Endpoints:
```txt
POST /api/v1/files/upload
GET  /api/v1/files/{uuid}
GET  /api/v1/files/{uuid}/download
DELETE /api/v1/files/{uuid}
```

## Regras

- Validar extensão, MIME e tamanho.
- Calcular checksum.
- Proteger download por permissão.
- Usar exclusão lógica.
- Não expor caminho físico.
- Salvar BLOB no banco.
- Otimizar imagens com GD quando possível.
- PDF/áudio podem ter otimizador stub seguro se ferramenta não existir.

## Frontend

- Configurações da empresa.
- Upload/preview de logo.
- Lista simples de arquivos da empresa.

## Permissões

```txt
company.view
company.update
files.upload
files.view
files.download
files.delete
```

## Não implementar

Paciente completo, prontuário, timeline, agenda ou TISS.

## Critérios de aceite

- Empresa pode ser editada.
- Logo salva em BLOB e exibe.
- Upload inválido é bloqueado.
- Download respeita permissão.
- Auditoria registra upload/download/exclusão.

## Prompt para IA

```md
Implemente somente esta entrega: Company e Files. Arquivos em BLOB, validação, checksum, permissões, auditoria e frontend de empresa/logo. Não implemente paciente, agenda ou TISS.
```
