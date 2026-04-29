# Entrega 14 — TISS MVP de faturamento


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

Implementar MVP TISS como módulo crítico de receita, não como simples exportação XML.

## Premissas

- Separar padrão TISS de regras operacionais por operadora.
- Token/senha/autorização é configurável por operadora, não regra universal.
- Tudo deve ser versionado.
- Lote fechado não muda sem auditoria/reabertura.
- Atendimentos não faturáveis são bloqueados.

## Entidades principais

- tiss_versions
- operators
- operator_tiss_configs
- beneficiary_coverages
- tiss_procedure_mappings
- tiss_guides
- tiss_batches
- tiss_batch_guides
- tiss_glosas

## Services

- ValidateAttendanceForBillingService.
- ResolveOperatorRulesService.
- GenerateGuideNumberService.
- BuildTissGuidePayloadService.
- BuildTissBatchPayloadService.
- ValidateTissBusinessRulesService.
- ValidateTissXmlService.
- ExportTissXmlService.
- RegisterSubmissionProtocolService.
- OpenGlosaService.
- ReprocessGuideService.
- ScheduleTissReminderService.

## Bloqueios mínimos

Bloquear faturamento quando:
- atendimento não finalizado;
- cancelado/falta/remarcado;
- paciente sem convênio elegível;
- profissional inválido;
- procedimento sem TUSS;
- operadora exige token/senha/autorização e não tem;
- atendimento já está em guia/lote ativo;
- lote fechado está sendo alterado sem permissão.

## Frontend

- Operadoras.
- Configuração TISS.
- Carteira do paciente.
- Mapeamento TUSS.
- Pendências de faturamento.
- Guias.
- Lotes.
- Exportação XML.
- Protocolo manual.
- Glosa inicial.
- Dashboard TISS.

## Dashboard

- pendentes de faturamento;
- bloqueados;
- aguardando token/autorização;
- guias prontas;
- guias rejeitadas;
- lotes fechados;
- lotes enviados;
- aguardando retorno;
- glosas abertas;
- valor potencial;
- valor faturado;
- valor glosado.

## Permissões

```txt
tiss.view
tiss.operator.manage
tiss.config.manage
tiss.mapping.manage
tiss.guide.generate
tiss.guide.validate
tiss.batch.generate
tiss.batch.close
tiss.batch.reopen
tiss.xml.export
tiss.protocol.register
tiss.glosa.manage
```

## Não implementar

Web service real, upload automático em portal, retorno avançado, conciliação financeira avançada.

## Critérios de aceite

- Operadora/configuração existem.
- Carteira elegível existe.
- Procedimento mapeado para TUSS.
- Atendimento elegível gera guia.
- Pendência bloqueia guia.
- Lote agrupa guias.
- XML exporta e registra hash.
- Glosa inicial funciona.
- Dashboard mostra pendências.

## Prompt para IA

```md
Implemente somente esta entrega: TISS MVP. Crie operadoras, configurações, carteiras, mapeamento TUSS, validação de atendimento faturável, guias, lotes, XML, protocolo manual, glosa, dashboard, auditoria e testes. Separe padrão TISS de regra por operadora.
```
