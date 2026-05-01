# Ordem geral de implementação incremental

## Objetivo

Transformar as specs em partes entregáveis, pequenas e testáveis, para construir o sistema com IA sem pedir “construa tudo de uma vez”.

## Ordem recomendada

1. `01_PROMPT_MESTRE_E_REGRAS_GLOBAIS.md`
2. `02_FUNDACAO_TECNICA_DOCKER_BACKEND.md`
3. `03_QUALIDADE_TESTES_LINT_CI.md`
4. `04_FRONTEND_DESIGN_SYSTEM.md`
5. `05_AUTH_ACL_AUDITORIA_SEGURANCA.md`
6. `06_EMPRESA_CONFIGURACOES_ARQUIVOS.md`
7. `07_PESSOAS_PACIENTES_PROFISSIONAIS.md`
8. `08_PROFISSIONAIS_CONTRATOS_REPASSES.md`
9. `09_AGENDA_ROBUSTA.md`
10. `09_1_REVISAO_AGENDA_FORNECEDORES_REPASSE.md`
11. `10_ATENDIMENTOS_PRONTUARIO_TIMELINE.md`
12. `11_MODELOS_QUESTIONARIOS_BUILDER.md`
13. `12_FINANCEIRO_OPERACIONAL_REPASSES.md`
14. `13_IMPORTACAO_DADOS_LEGADOS.md`
15. `14_TISS_MVP_FATURAMENTO.md`
16. `15_LEMBRETES_TAREFAS_DASHBOARDS.md`
17. `16_REVISAO_FINAL_HARDENING_DOCUMENTACAO.md`

## Por que essa ordem

A base técnica vem antes dos módulos. O design system vem antes das telas. Auth/ACL/auditoria vêm antes de dados sensíveis. Pessoas vêm antes de agenda. Agenda vem antes de atendimentos. Atendimentos vêm antes de financeiro automático. Financeiro e dados clínicos vêm antes do TISS. Importação vem depois das entidades existirem.

## Regra de validação entre entregas

Ao concluir cada arquivo, a IA implementadora deve responder com:

```md
## Entrega concluída
- Arquivos criados/alterados
- Como rodar
- Como testar
- Resultado dos testes
- Pendências reais
- O que não foi implementado por pertencer a etapas futuras
```

## O que não fazer

- Não implementar TISS antes da base clínica e financeira.
- Não criar telas de negócio antes do design system.
- Não deixar ACL para depois.
- Não expor ID numérico.
- Não usar React/Vue/Angular.
- Não usar Laravel.
- Não criar campos JSON brutos para usuário final.
