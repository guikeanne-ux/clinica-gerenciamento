# Entrega 04 — Frontend Design System vanilla


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

Criar o design system antes das telas de negócio.

## Estrutura sugerida

```txt
/frontend
  /assets
  /core
    /css
      tokens.css
      reset.css
      base.css
      layout.css
      components.css
      utilities.css
    /js
      app.js
      dom.js
      events.js
      masks.js
      validators.js
      http.js
    /components
      button.js
      input.js
      select.js
      multiselect.js
      autocomplete.js
      modal.js
      toast.js
      datepicker.js
      tabs.js
      table.js
      file-input.js
      rich-text-markdown.js
```

## Página showcase

Criar `frontend/design-system.html` ou rota `/#/design-system`.

## Componentes obrigatórios

- input texto;
- select customizado;
- multiselect customizado;
- autocomplete;
- file input com preview;
- textarea;
- rich text com markdown;
- modal com scroll interno;
- alertas;
- badges;
- botões small/medium/large/xl;
- botões com mesmo visual em `a`, `button`, `input`;
- títulos, subtítulos e parágrafos;
- skeleton;
- overlay loading;
- datepicker;
- datetime picker;
- tabela responsiva;
- cards;
- abas;
- dropdown;
- paginação;
- breadcrumbs;
- toasts;
- sidebar/menu;
- header;
- filtros;
- empty states;
- erro/sucesso.

## Correções obrigatórias

- Não usar `<select multiple>` padrão.
- Modal não pode extrapolar altura da tela.
- Máscaras para CPF, CNPJ, telefone, data, dinheiro e CEP.
- Campo opcional deve validar se preenchido.
- Componentes devem ser reutilizáveis.

## Não implementar

Telas reais de pacientes, agenda, financeiro ou TISS.

## Critérios de aceite

- Showcase abre sem build.
- Componentes funcionam.
- Responsivo.
- Sem React/Vue/Angular.
- Multiselect tem busca, tags e remoção.
- Modal tem header/footer acessíveis.

## Prompt para IA

```md
Implemente somente esta entrega: Design System frontend em HTML, CSS e JS vanilla. Crie showcase com todos os componentes. Dê atenção a multiselect, modal, máscaras e validações. Não implemente telas de negócio.
```
