# Entrega 13 — Importação de dados legados


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

Permitir importação assistida de dados antigos.

## Entidades

### import_batches
- uuid;
- name;
- entity_type;
- source_type;
- file_uuid;
- status;
- dry_run;
- total_rows;
- valid_rows;
- invalid_rows;
- imported_rows;
- failed_rows;
- created_by_user_uuid;
- started_at;
- finished_at;
- created_at.

### import_batch_rows
- uuid;
- import_batch_uuid;
- row_number;
- raw_data_json;
- mapped_data_json;
- validation_errors_json;
- status;
- target_entity_uuid;
- created_at.

### import_mappings
- uuid;
- name;
- entity_type;
- mapping_json;
- created_by_user_uuid;
- timestamps.

## Tipos iniciais

- CSV.
- JSON.
- Planilha se infraestrutura permitir.

## Entidades prioritárias

- pacientes;
- profissionais;
- usuários;
- agenda;
- atendimentos;
- evoluções;
- anamneses;
- metadados de arquivos.

## Fluxo

1. Upload.
2. Detectar colunas.
3. Mapear colunas.
4. Dry-run.
5. Pré-validação.
6. Relatório de erros.
7. Importação real.
8. Log de lote.
9. Rollback lógico quando viável.

## Regras

- Toda importação gera log.
- Validar duplicidade.
- Validar integridade referencial.
- Importação sensível exige permissão.
- Não destruir dados existentes sem confirmação.

## Frontend

Wizard:
- entidade;
- upload;
- mapeamento;
- dry-run;
- erros;
- confirmação;
- resultado.

## Permissões

```txt
import.view
import.create
import.dry_run
import.execute
import.rollback
```

## Não implementar

Importador específico do Zenfisio sem layout real, OCR, TISS legado avançado.

## Critérios de aceite

- CSV de pacientes importa com dry-run.
- Erros por linha aparecem.
- Duplicidade de CPF é detectada.
- Importação real cria registros.
- Lote fica rastreável.

## Prompt para IA

```md
Implemente somente esta entrega: importação assistida com lote, mapeamento, dry-run, validação, relatório de erros, execução e rollback lógico. Comece por CSV de pacientes.
```
