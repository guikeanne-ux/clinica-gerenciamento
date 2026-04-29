# .codex

Esta pasta contém configuração e prompts reutilizáveis para trabalhar com Codex neste repositório.

## Arquivos principais

- `.codex/config.toml`: configuração de projeto do Codex.
- `.codex/prompts/`: prompts fixos para execução, revisão e correção.
- `.codex/checklists/`: checklists para validar entregas.
- `.codex/mcp/optional-mcps.toml`: MCPs opcionais, desligados por padrão.

## Fluxo recomendado

1. Coloque as specs fragmentadas na pasta `specs-entregas/`.
2. Mantenha `AGENTS.md` na raiz.
3. Abra o projeto no VS Code.
4. Inicie o Codex no repositório.
5. Use um prompt de `.codex/prompts/`.
6. Implemente uma entrega por vez.
7. Rode testes.
8. Faça commit.
9. Só então avance para a próxima entrega.

## Ordem

- `01_PROMPT_MESTRE_E_REGRAS_GLOBAIS.md` vira `AGENTS.md`.
- A primeira entrega de implementação real é `02_FUNDACAO_TECNICA_DOCKER_BACKEND.md`.

## Regra

Nunca peça para o Codex ler todas as specs e construir tudo.
