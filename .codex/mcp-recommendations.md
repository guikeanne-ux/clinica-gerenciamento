# Recomendações de MCP para este projeto

## Recomendação principal

Comece com apenas um MCP ativo:

- Context7: documentação de bibliotecas e frameworks.

Motivo:
- O projeto usa várias bibliotecas PHP/JS/Docker.
- O agente pode consultar documentação atualizada sem você precisar copiar docs no prompt.
- É útil desde a fundação técnica.

## MCPs opcionais

### Playwright MCP

Ative quando chegar em:
- Design system.
- Agenda visual.
- Modais.
- Multiselect.
- Fluxos frontend.
- Testes manuais em browser.

Não precisa ativar desde o início.

### GitHub MCP

Ative se:
- O repositório estiver no GitHub.
- Você quiser que o agente leia issues, PRs e contexto remoto.
- Você quiser automatizar revisão de PR.

No início, prefira read-only.

### MCP de documentação privada

Só vale a pena se você tiver:
- Wiki interna;
- documentação de API privada;
- base de conhecimento da clínica;
- specs fora do repositório.

Para este projeto, melhor versionar tudo no próprio repositório primeiro.

## MCPs que eu NÃO recomendo no começo

### Filesystem MCP

Não precisa. O Codex já trabalha no workspace.

### Git MCP

Não precisa no começo. O agente já pode usar comandos `git` no terminal, respeitando aprovação.

### Banco de dados MCP

Não recomendo inicialmente. Para este projeto, é mais seguro o agente interagir com o banco por migrations, seeders e testes.

### Browser genérico com login

Não recomendo no começo. Aumenta risco e distração. Use Playwright MCP apenas quando houver UI para testar.

## Regra de segurança

Quanto mais MCP, maior a superfície de erro.

Ative MCP só quando houver ganho claro.
