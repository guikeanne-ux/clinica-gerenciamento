# Entrega 08.2 — Tratamento de Erros, Exceptions, Toasts Globais e Responsividade

## Objetivo geral

Corrigir a base de tratamento de erros do backend e do frontend para que o sistema pare de exibir tudo como “Erro interno do servidor” e passe a retornar mensagens claras, status HTTP corretos, erros por campo, mensagens amigáveis, logs técnicos adequados, feedback visual por toast e responsividade real.

Esta entrega deve acontecer antes da Entrega 09 — Agenda Robusta.

A Agenda terá muitos fluxos críticos: conflito de horário, permissão, recorrência, paciente obrigatório, profissional obrigatório, falta, cancelamento, início de atendimento e validações de agenda. Se o tratamento de erro e feedback visual estiver ruim, a Agenda nascerá com UX ruim e será mais caro corrigir depois.

---

## Contexto

O backend está modular e funcional, mas muitos erros ainda aparecem de forma genérica como “Erro interno do servidor”.

O frontend melhorou com a reestruturação da SPA, mas ainda precisa tratar melhor:

- erros de API;
- validação;
- permissão;
- sessão expirada;
- conflitos;
- registros não encontrados;
- falhas reais de servidor;
- feedbacks de sucesso;
- feedbacks de upload;
- mensagens globais;
- responsividade.

Também foi identificado que mensagens em alerts fixos no topo ou no fim do wrapper não são adequadas, porque o usuário pode não estar naquela região da tela, especialmente em telas pequenas ou páginas longas.

A partir desta entrega, o padrão global de mensagens do sistema deve ser toast.

---

# Regras obrigatórias

- Não implementar Agenda.
- Não implementar Atendimentos.
- Não implementar Prontuário.
- Não implementar Financeiro operacional completo.
- Não implementar TISS.
- Não implementar Importação.
- Não implementar Lembretes/Tarefas.
- Não implementar novas telas de negócio.
- Não reescrever a API.
- Não quebrar endpoints existentes.
- Não alterar contratos de sucesso desnecessariamente.
- Não usar Laravel.
- Não usar React.
- Não usar Vue.
- Não usar Angular.
- Não usar framework frontend.
- Usar PHP 8.3 puro.
- Usar JavaScript vanilla.
- Preservar UUID como identificador público.
- Não expor `id` numérico.
- Não esconder erro técnico retornando sempre 500.
- Não exibir stack trace para o usuário final.
- Não exibir SQL, caminhos internos ou dados sensíveis na response.
- Logs técnicos devem ficar no backend.
- Mensagens ao usuário devem ser claras, seguras e úteis.
- Não usar `alert()` nativo como mecanismo de feedback.
- Não usar alertas globais presos no topo ou rodapé do wrapper como mecanismo principal de feedback.
- Mensagens globais devem ser exibidas por toast.
- Validações de campo devem continuar aparecendo inline perto do campo.

---

# Escopo da entrega

## 1. Backend — Exceptions específicas

Criar ou revisar a estrutura central de exceptions em:

```txt
/backend/app/Core/Exceptions
```

Criar classes de exceção específicas, no mínimo:

```txt
ValidationException
AuthenticationException
AuthorizationException
NotFoundException
ConflictException
BusinessRuleException
InvalidPayloadException
UploadException
RateLimitException
InternalServerException
```

Cada exception deve carregar, quando aplicável:

- message;
- statusCode;
- errorCode;
- errors por campo;
- context interno opcional para log;
- previous exception.

---

## 2. Backend — Mapeamento HTTP obrigatório

Implementar mapeamento correto de status HTTP.

### 400 Bad Request

Usar para:

- payload inválido;
- JSON malformado;
- formato inesperado;
- query string inválida;
- parâmetros incompatíveis.

### 401 Unauthorized

Usar para:

- token ausente;
- token inválido;
- token expirado;
- sessão expirada;
- login inválido.

### 403 Forbidden

Usar para:

- usuário autenticado sem permissão;
- perfil sem acesso;
- tentativa de ação não autorizada;
- acesso a recurso sensível sem permissão.

### 404 Not Found

Usar para:

- recurso não encontrado;
- rota inexistente;
- entidade por UUID inexistente.

### 409 Conflict

Usar para:

- login duplicado;
- CPF/CNPJ duplicado;
- e-mail duplicado;
- conflito de regra de negócio;
- configuração vigente duplicada;
- conflitos futuros de agenda.

### 422 Unprocessable Entity

Usar para:

- validação de campos;
- campo obrigatório ausente;
- CPF inválido;
- CNPJ inválido;
- e-mail inválido;
- telefone inválido;
- data inválida;
- valor monetário inválido;
- formato válido de payload, mas semanticamente inválido.

### 429 Too Many Requests

Usar para:

- limite de requisições;
- muitas tentativas;
- preparação futura para rate limit.

Se rate limit ainda não existir, criar estrutura de exception e response, sem implementar um rate limiter completo se isso extrapolar o escopo.

### 500 Internal Server Error

Usar somente para:

- erro inesperado real;
- falha não prevista;
- exceção não tratada.

---

## 3. Backend — Response padrão de erro

Todas as respostas de erro devem seguir o padrão global:

```json
{
  "success": false,
  "message": "Mensagem clara para o usuário.",
  "data": null,
  "meta": {
    "request_id": "opcional",
    "error_code": "VALIDATION_ERROR"
  },
  "errors": [
    {
      "field": "email",
      "message": "E-mail inválido."
    }
  ]
}
```

Regras:

- `message` deve ser amigável e útil.
- `errors` deve conter detalhes por campo quando for validação.
- `error_code` deve ser estável para o frontend poder reagir.
- Não expor stack trace.
- Não expor SQL.
- Não expor caminho interno do servidor.
- Não expor dados sensíveis.
- `request_id` deve existir quando houver estrutura para isso.
- Se ainda não existir request_id, implementar de forma simples e segura no handler/middleware.

---

## 4. Backend — Error codes mínimos

Criar constantes, enum ou estrutura equivalente para error codes:

```txt
VALIDATION_ERROR
AUTHENTICATION_FAILED
TOKEN_EXPIRED
UNAUTHORIZED
FORBIDDEN
NOT_FOUND
CONFLICT
DUPLICATE_LOGIN
DUPLICATE_DOCUMENT
DUPLICATE_EMAIL
BUSINESS_RULE_VIOLATION
INVALID_PAYLOAD
INVALID_UPLOAD
UPLOAD_TOO_LARGE
UNSUPPORTED_FILE_TYPE
INVALID_MIME_TYPE
RATE_LIMIT_EXCEEDED
INTERNAL_SERVER_ERROR
```

Regras:

- Error codes devem ser estáveis.
- Frontend pode usá-los para tratamento específico.
- Não usar textos variáveis como código.

---

## 5. Backend — Handler global de exceptions

Revisar ou criar handler global de exceptions para:

- capturar exceptions conhecidas;
- converter exceptions conhecidas para response JSON padronizada;
- capturar exceptions desconhecidas como 500;
- logar erro técnico com request_id;
- retornar request_id no meta quando possível;
- diferenciar ambiente local/dev de produção sem expor stack trace ao usuário final;
- manter contrato global de response;
- garantir `Content-Type: application/json` nas respostas de API.

Regras:

- Em ambiente local, detalhes técnicos podem ir para log, não para response pública.
- A response pública deve continuar segura.
- Handler não deve engolir erros silenciosamente.

---

## 6. Backend — Revisão dos módulos existentes

Revisar validators/services/controllers/middlewares já existentes para garantir que lancem exceptions corretas em vez de erro genérico.

Aplicar pelo menos nos módulos:

```txt
Auth
ACL
Audit, se aplicável
Company
Files
Person
Professional Payment
```

Casos importantes:

- login duplicado deve retornar 409 com `DUPLICATE_LOGIN`;
- CPF/CNPJ duplicado deve retornar 409 com `DUPLICATE_DOCUMENT`;
- e-mail duplicado deve retornar 409 com `DUPLICATE_EMAIL`;
- validação de campo deve retornar 422 com `VALIDATION_ERROR`;
- recurso não encontrado deve retornar 404 com `NOT_FOUND`;
- permissão negada deve retornar 403 com `FORBIDDEN`;
- token ausente/inválido deve retornar 401 com `UNAUTHORIZED` ou `TOKEN_EXPIRED`;
- upload inválido deve retornar erro específico;
- regra de negócio deve retornar 409 ou 422 conforme o caso;
- erro inesperado real deve retornar 500 com `INTERNAL_SERVER_ERROR`.

---

# 7. Frontend — Sistema global de mensagens por Toast

O sistema não deve exibir mensagens importantes apenas em alerts fixos no topo, no rodapé ou dentro de wrappers que dependem da rolagem da tela.

## Problema atual

- Algumas mensagens aparecem no topo ou fundo do conteúdo.
- Em telas pequenas ou longas, o usuário pode não ver a mensagem.
- Isso prejudica a UX e passa sensação de sistema incompleto.

## Regra obrigatória

Toda mensagem global para o usuário deve ser exibida por toast.

Isso inclui:

- sucesso;
- erro;
- alerta;
- informação;
- sessão expirada;
- acesso negado;
- falha de carregamento;
- erro inesperado;
- ação concluída;
- exclusão realizada;
- cadastro salvo;
- upload concluído;
- upload inválido;
- conflito de dados;
- erro de permissão;
- limite de requisições.

Criar ou revisar um componente global:

```txt
frontend/core/components/toast.js
```

Ou equivalente, respeitando a estrutura atual do projeto.

## Toast deve suportar

- tipo `success`;
- tipo `error`;
- tipo `warning`;
- tipo `info`;
- título opcional;
- mensagem;
- tempo automático de fechamento;
- botão de fechar;
- múltiplos toasts empilhados;
- posição fixa visível;
- desktop: preferencialmente canto superior direito;
- mobile: topo da tela com largura quase total;
- z-index alto;
- acessibilidade mínima com `aria-live`;
- não depender da rolagem da página;
- não ficar escondido atrás de modal, sidebar ou header.

## Regras de UX dos toasts

- Mensagens de sucesso devem ser curtas e claras.
- Mensagens de erro devem explicar o que aconteceu.
- Mensagens de validação podem ter resumo em toast, mas o erro de campo também deve aparecer perto do campo.
- Erros inesperados devem mostrar mensagem segura.
- Se houver request_id no backend, o toast pode exibir discretamente: “Código para suporte: XXXXX”.
- Toast não deve substituir estado visual de campo inválido.
- Toast não deve substituir tela 403 ou 404 quando o usuário navegar diretamente para uma rota proibida/inexistente.
- Toast deve complementar feedback, não poluir a interface.

## Exemplos de mensagens

- “Paciente salvo com sucesso.”
- “Revise os campos destacados.”
- “Você não tem permissão para acessar esta área.”
- “Sua sessão expirou. Faça login novamente.”
- “Já existe um cadastro com este CPF.”
- “Arquivo inválido. Verifique o tipo e tente novamente.”
- “Não foi possível concluir a ação agora. Tente novamente em alguns instantes.”

## Remover ou substituir alerts globais antigos

- Remover alerts fixos no topo/fundo quando usados para mensagens globais.
- Não deixar mensagens importantes presas dentro de áreas roláveis.
- Manter mensagens inline apenas para validação específica de campos ou estados locais de componentes.

---

# 8. Frontend — Tratamento de erros com Toast

Revisar o HTTP service centralizado, provavelmente em:

```txt
frontend/core/services/http.js
```

Ele deve tratar:

- 400;
- 401;
- 403;
- 404;
- 409;
- 422;
- 429;
- 500.

## Comportamento esperado

### 401

- limpar sessão;
- redirecionar para `/login`;
- mostrar toast: “Sua sessão expirou. Faça login novamente.”

### 403

- se for navegação para rota proibida, mostrar tela `/403`;
- também pode mostrar toast: “Você não tem permissão para acessar esta área.”

### 404

- se for rota inexistente, mostrar tela `/404`;
- se for recurso não encontrado em ação do usuário, mostrar toast amigável.

### 409

- mostrar toast com mensagem de conflito;
- exemplo: “Já existe um cadastro com este CPF.”

### 422

- mostrar toast: “Revise os campos destacados.”
- preencher erros inline nos campos correspondentes.

### 429

- mostrar toast: “Muitas tentativas. Aguarde alguns instantes e tente novamente.”

### 500

- mostrar toast seguro:
  “Não foi possível concluir a ação agora. Tente novamente em alguns instantes.”
- se houver request_id, mostrar de forma discreta para suporte.

### Sucesso

Ações de criação, edição, exclusão lógica, upload e simulação devem exibir toast de sucesso.

---

# 9. Frontend — Componentes de erro e feedback

Criar ou revisar componentes:

```txt
ErrorState
FormErrorSummary
FieldError
Toast
Tela 403
Tela 404
Estado de falha de carregamento
Estado de sessão expirada
```

Regras:

- Não mostrar “Erro interno do servidor” para tudo.
- Não mostrar mensagens técnicas cruas.
- Não mostrar JSON bruto.
- Erros de validação devem aparecer perto dos campos.
- Erros gerais devem aparecer em toast.
- Estados persistentes de tela podem usar ErrorState.
- O usuário deve entender o que aconteceu e o que pode fazer.

---

# 10. Frontend — Responsividade real obrigatória

O sistema não está completamente responsivo. Corrigir isso como parte desta entrega.

Não basta “não quebrar”. O sistema deve ser realmente utilizável em telas menores.

## Breakpoints mínimos sugeridos

```txt
mobile: até 640px
tablet: 641px a 1024px
desktop: acima de 1024px
```

## Layout geral

Validar e ajustar:

- sidebar deve colapsar ou virar menu mobile;
- header não deve quebrar;
- conteúdo não deve gerar overflow horizontal;
- container principal deve respeitar padding adequado em mobile;
- botões principais devem continuar acessíveis;
- breadcrumbs muito longos devem quebrar ou truncar corretamente;
- layout deve continuar usável com teclado e toque.

## Tabelas

Tabelas não podem estourar a tela.

Em mobile, usar uma destas estratégias:

- scroll horizontal bem sinalizado;
- cards responsivos por registro;
- colunas essenciais visíveis e detalhes em expansão.

Regras:

- ações por linha devem continuar acessíveis;
- paginação deve funcionar bem no mobile;
- cabeçalho não deve esmagar conteúdo;
- textos longos devem truncar ou quebrar de forma controlada.

## Formulários

- campos devem empilhar em mobile;
- labels devem continuar visíveis;
- mensagens de erro por campo devem aparecer próximas ao campo;
- inputs devem ter altura confortável;
- botões devem ter área de toque adequada;
- grupos de formulário devem ter espaçamento claro;
- formulários longos devem ser divididos em seções claras;
- botões de ação devem continuar visíveis e acessíveis.

## Modais

- modal deve respeitar altura da tela;
- header e footer devem permanecer acessíveis;
- corpo deve rolar internamente;
- em mobile, modal pode ocupar quase toda a tela;
- botão de fechar deve estar sempre visível;
- modal não deve ficar escondido atrás de header/sidebar.

## Toasts

- desktop: canto superior direito ou inferior direito;
- mobile: topo da tela, largura quase total;
- nunca depender de rolagem;
- nunca ficar escondido por sidebar/header/modal.

## Sidebar/mobile

- em mobile, sidebar deve virar drawer/menu;
- deve haver botão claro para abrir/fechar;
- ao clicar em item do menu, sidebar deve fechar;
- overlay deve impedir clique no conteúdo quando menu estiver aberto;
- escape/click fora deve fechar quando aplicável;
- foco visual deve ser preservado.

## Páginas que precisam ser revisadas obrigatoriamente

- login;
- dashboard;
- company;
- patients list;
- patient detail;
- patient form;
- professionals list;
- professional form;
- suppliers list;
- supplier form;
- payment tables;
- design system.

---

# Testes backend obrigatórios

Criar ou ajustar testes para:

- validação retorna 422;
- erro por campo aparece em `errors`;
- login duplicado retorna 409;
- CPF/CNPJ duplicado retorna 409;
- e-mail duplicado retorna 409;
- recurso inexistente retorna 404;
- sem token retorna 401;
- sem permissão retorna 403;
- upload inválido retorna erro específico;
- exception desconhecida retorna 500 padronizado;
- stack trace não aparece na response;
- request_id aparece no meta quando implementado;
- erro mantém o padrão global de response.

---

# Validação manual obrigatória

Testar manualmente em desktop e mobile:

1. Login com erro.
2. Login com sucesso.
3. Sessão expirada ou token inválido.
4. Acesso sem permissão.
5. Cadastro com campos inválidos.
6. Cadastro com CPF inválido.
7. Cadastro com CPF duplicado.
8. Cadastro com e-mail inválido.
9. Cadastro salvo com sucesso.
10. Upload inválido.
11. Upload bem-sucedido.
12. Exclusão lógica com sucesso.
13. Acessar UUID inexistente.
14. Simular erro inesperado, se houver forma segura.
15. Frontend exibindo erros por campo.
16. Frontend exibindo toast amigável.
17. Frontend redirecionando em 401.
18. Abrir e fechar sidebar mobile.
19. Navegar pelo menu mobile.
20. Usar formulário em tela pequena.
21. Visualizar tabela em tela pequena.
22. Abrir modal em tela pequena.
23. Confirmar que nenhum feedback importante fica escondido por rolagem.

---

# Comandos obrigatórios

Ao terminar, rodar:

```bash
make test
make lint
make phpstan
```

Se algum comando falhar, corrigir a causa real.

Não remover testes para fazer passar.

Não reduzir regra de lint ou PHPStan sem justificativa forte.

---

# Critérios de aceite

A entrega só será aceita se:

- backend não retornar tudo como 500;
- exceptions específicas existirem;
- error codes estáveis existirem;
- handler global retornar response padronizada;
- validações retornarem 422;
- conflitos retornarem 409;
- não encontrado retornar 404;
- sem autenticação retornar 401;
- sem permissão retornar 403;
- erro inesperado real retornar 500 seguro;
- stack trace não aparecer para usuário final;
- mensagens globais forem exibidas por toast;
- alerts antigos de topo/rodapé forem removidos ou substituídos;
- erros de validação tiverem toast de resumo e erro inline por campo;
- sucessos forem exibidos por toast;
- toasts funcionarem em desktop e mobile;
- toast não depender de rolagem;
- layout não gerar overflow horizontal em mobile;
- sidebar funcionar bem no mobile;
- tabelas não estourarem a tela;
- formulários forem usáveis em mobile;
- modais forem usáveis em mobile;
- rotas principais forem testadas em largura mobile;
- comandos de qualidade passarem.

---

# O que não implementar nesta entrega

- Agenda.
- Atendimentos.
- Prontuário.
- Timeline real.
- Financeiro operacional completo.
- TISS.
- Importação.
- Lembretes/tarefas.
- Rate limiter completo, salvo se já existir estrutura simples.
- Redesign completo de todas as telas.
- Novos módulos de negócio.
- Novas regras de domínio fora de erros/feedbacks.

---

# Resposta esperada ao finalizar

Ao terminar, responder no seguinte formato:

```md
# Entrega 08.2 concluída

## Arquivos criados/alterados

## Exceptions criadas/revisadas

## Error codes criados

## Handler global revisado

## Módulos ajustados

## Tratamento frontend implementado

## Sistema de toast implementado

## Componentes de erro/feedback criados

## Ajustes de responsividade realizados

## Como testar manualmente

## Resultado dos comandos executados

## Pendências reais

## O que não foi implementado porque pertence a entregas futuras
```
