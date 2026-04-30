# Entrega 08.1 — Reestruturação do Frontend Shell, Rotas e Identidade Visual

## Objetivo geral

Transformar o frontend atual, que está em páginas HTML soltas e pouco integrado, em uma SPA real, com entrada única, roteamento, layout autenticado, menu por permissões, camada HTTP centralizada, estrutura modular de frontend e identidade visual mais próxima de um produto final.

Esta entrega deve acontecer antes da Entrega 09 — Agenda Robusta.

A Agenda será uma das telas centrais do sistema. Portanto, antes de implementá-la, o frontend precisa ter uma fundação sólida de produto, e não apenas páginas HTML independentes.

## Contexto

O backend já evoluiu com uma base modular, incluindo:

- fundação técnica;
- testes/lint/PHPStan;
- design system inicial;
- Auth/ACL/Auditoria;
- Empresa/Arquivos;
- Pessoas;
- Profissionais/contratos/regras de repasse.

O frontend, porém, ainda está muito distante de um produto real. Existe um design system inicial, mas ele está genérico. Além disso, várias telas foram entregues como arquivos HTML independentes, sem um shell SPA bem definido, sem rotas consolidadas e sem sensação de sistema único.

Esta entrega existe para corrigir isso antes de avançar.

---

# Regras obrigatórias

- Não implementar Agenda.
- Não implementar Atendimentos.
- Não implementar Prontuário.
- Não implementar Financeiro operacional completo.
- Não implementar TISS.
- Não implementar Importação.
- Não implementar Lembretes/Tarefas.
- Não implementar novas regras de backend.
- Não reescrever a API.
- Não quebrar endpoints existentes.
- Não quebrar testes existentes.
- Não usar Laravel.
- Não usar React.
- Não usar Vue.
- Não usar Angular.
- Não usar framework frontend.
- Usar apenas HTML, CSS e JavaScript vanilla.
- Usar o design system existente como base, mas melhorar estrutura, acabamento visual e integração.
- Preservar compatibilidade com Auth/ACL/Auditoria.
- Preservar compatibilidade com Company, Files, Person e Professional Payment.
- Não expor `id` numérico em rotas, payloads ou frontend.
- Não remover funcionalidades existentes sem substituir por equivalente melhor dentro da SPA.
- Não criar solução temporária ou demonstrativa.
- Esta base deve servir para o produto real.

---

# Escopo da entrega

## 1. Entrada única da SPA

Criar ou consolidar:

```txt
frontend/index.html
```

Esse arquivo deve ser a entrada principal do sistema.

Ele deve conter:

- root/app container;
- carregamento dos CSS globais;
- carregamento do JavaScript principal;
- fallback visual mínimo de loading;
- estrutura compatível com roteamento client-side.

Arquivos HTML antigos podem ser mantidos temporariamente apenas como legado ou referência, mas o fluxo principal do sistema deve passar por `frontend/index.html`.

O usuário não deve precisar abrir vários arquivos `.html` diferentes para usar o sistema.

---

## 2. Estrutura base do frontend

Organizar ou consolidar uma estrutura semelhante a:

```txt
frontend/
  index.html
  assets/
    icons/
    images/
  core/
    css/
      tokens.css
      reset.css
      base.css
      layout.css
      components.css
      utilities.css
      theme.css
    js/
      app.js
      dom.js
      events.js
      masks.js
      validators.js
    router/
      router.js
      routes.js
      guards.js
    layout/
      app-layout.js
      sidebar.js
      header.js
      breadcrumb.js
      page-container.js
    auth/
      auth-service.js
      session-store.js
      permission-service.js
    services/
      http.js
      api-config.js
    components/
      button.js
      input.js
      select.js
      multiselect.js
      autocomplete.js
      modal.js
      toast.js
      loading.js
      empty-state.js
      table.js
      pagination.js
      tabs.js
      card.js
      badge.js
  modules/
    auth/
    dashboard/
    company/
    files/
    patients/
    professionals/
    suppliers/
    payment/
    design-system/
```

Não é obrigatório seguir exatamente esses nomes se o projeto já tiver uma estrutura equivalente, mas a organização precisa ficar clara, modular e sustentável.

---

## 3. Router SPA vanilla

Criar um roteador client-side em JavaScript puro.

O router deve suportar:

- navegação sem reload;
- rotas públicas;
- rotas protegidas;
- fallback 404;
- redirecionamento para login quando não autenticado;
- checagem de permissão por rota;
- parâmetros de rota, como `/patients/:uuid`;
- função `navigate(path)`;
- interceptação de links internos com `data-link` ou equivalente;
- renderização de módulo dentro do layout correto.

Rotas mínimas obrigatórias:

```txt
/login
/dashboard
/company
/patients
/patients/new
/patients/:uuid
/professionals
/professionals/new
/professionals/:uuid
/suppliers
/suppliers/new
/suppliers/:uuid
/payment-tables
/design-system
/403
/404
```

Regras:

- `/login` deve usar layout público.
- As demais rotas devem usar layout autenticado, salvo `design-system` se já estiver público.
- Rotas protegidas sem token devem redirecionar para `/login`.
- Rotas sem permissão devem ir para `/403`.

---

## 4. Layout autenticado real

Criar um layout autenticado reutilizável com:

- sidebar lateral;
- header superior;
- área principal de conteúdo;
- breadcrumb;
- estado de loading;
- estado de erro;
- botão/menu de usuário;
- ação de logout;
- comportamento responsivo/mobile.

### Sidebar

A sidebar deve:

- ser baseada nas permissões do usuário logado;
- agrupar itens por área quando fizer sentido;
- destacar rota ativa;
- colapsar ou adaptar bem em telas menores;
- não exibir itens sem permissão.

### Header

O header deve:

- mostrar nome do usuário logado;
- mostrar contexto da página atual;
- ter ação de logout;
- estar visualmente integrado com a identidade do produto.

### Área principal

A área principal deve:

- ter container consistente;
- ter espaçamento adequado;
- usar títulos padronizados;
- suportar ações primárias da página;
- renderizar os módulos de negócio.

---

## 5. Camada de autenticação frontend

Criar ou organizar:

```txt
frontend/core/auth/auth-service.js
frontend/core/auth/session-store.js
frontend/core/auth/permission-service.js
```

Funcionalidades mínimas:

- salvar token JWT;
- recuperar token;
- remover token;
- salvar dados básicos do usuário logado;
- recuperar usuário logado;
- chamar `/api/v1/auth/me`;
- verificar se está autenticado;
- verificar se possui permissão;
- fazer logout;
- limpar sessão em caso de 401;
- redirecionar para `/login` quando necessário.

Regras:

- Não armazenar informações sensíveis desnecessárias.
- Não confiar apenas no frontend para segurança; o backend continua sendo a fonte final de autorização.
- O frontend deve apenas melhorar UX, escondendo menus e prevenindo navegação indevida.

---

## 6. Camada HTTP centralizada

Criar ou organizar:

```txt
frontend/core/services/http.js
frontend/core/services/api-config.js
```

A camada HTTP deve:

- ter `baseURL` configurável;
- enviar `Authorization: Bearer <token>` automaticamente quando existir token;
- tratar respostas no padrão global da API;
- tratar 401 limpando sessão e redirecionando para login;
- tratar 403 mostrando tela ou feedback amigável;
- tratar erros de validação;
- expor helpers para:
  - GET;
  - POST;
  - PUT;
  - DELETE;
- evitar duplicação de `fetch` espalhado pelos módulos.

---

## 7. Migração das telas existentes para módulos SPA

Reorganizar as telas já criadas para serem renderizadas dentro da SPA.

Migrar/adaptar, conforme existirem no projeto:

- login;
- dashboard simples;
- empresa/configurações;
- arquivos da empresa;
- pacientes;
- detalhe de paciente;
- formulário de paciente;
- profissionais;
- formulário de profissional;
- fornecedores;
- formulário de fornecedor;
- tabelas de pagamento;
- configuração de pagamento do profissional;
- simulação de repasse;
- design system.

Estrutura sugerida:

```txt
frontend/modules/auth/login.js
frontend/modules/dashboard/dashboard.js
frontend/modules/company/company.js
frontend/modules/files/files-list.js
frontend/modules/patients/patients-list.js
frontend/modules/patients/patient-form.js
frontend/modules/patients/patient-detail.js
frontend/modules/professionals/professionals-list.js
frontend/modules/professionals/professional-form.js
frontend/modules/suppliers/suppliers-list.js
frontend/modules/suppliers/supplier-form.js
frontend/modules/payment/payment-tables.js
frontend/modules/payment/payment-table-form.js
frontend/modules/payment/professional-payment-config.js
frontend/modules/design-system/design-system.js
```

Regras:

- Não precisa recriar tudo do zero.
- Reaproveitar HTML/CSS/JS já existente quando fizer sentido.
- As telas devem ser renderizadas dentro do shell SPA.
- O usuário não deve navegar entre arquivos `.html` soltos para usar o sistema.

---

## 8. Dashboard inicial simples

Criar a rota:

```txt
/dashboard
```

Com visual de produto, ainda que com dados parcialmente mockados ou placeholders.

Deve conter cards para:

- compromissos do dia;
- pacientes ativos;
- pendências;
- atalhos rápidos;
- lembretes futuros ou placeholder;
- financeiro/resumo futuro ou placeholder.

Se já existirem endpoints simples que possam ser usados sem criar backend novo, pode consumir. Caso contrário, usar placeholder bem sinalizado.

Não implementar regras novas de backend para o dashboard nesta entrega.

---

## 9. Melhorar identidade visual do produto

O design system atual pode ser mantido como base, mas precisa ficar com mais cara de produto real.

Direção visual desejada:

- clínica moderna;
- acolhedora;
- confiável;
- organizada;
- leve;
- visual limpo;
- tons suaves;
- sensação de cuidado e profissionalismo;
- nada com cara de Bootstrap genérico;
- nada com cara de sistema administrativo antigo.

Ajustar ou criar:

- paleta de cores mais autoral;
- tema com tons suaves, por exemplo variações de teal, azul, verde, lavanda ou areia;
- background geral mais refinado;
- cards com bom espaçamento;
- botões com hierarquia visual clara;
- inputs mais elegantes;
- tabelas menos cruas;
- sidebar com identidade;
- header com acabamento;
- empty states com personalidade;
- estados de erro/sucesso mais amigáveis;
- loading states;
- modais;
- badges;
- breadcrumbs;
- ícones, se já houver estrutura para isso.

Não precisa criar arte final perfeita, mas precisa deixar a base visual muito mais próxima de um SaaS clínico moderno.

---

## 9.1. Diretrizes obrigatórias de UX

O frontend deve ser pensado como um produto profissional real, não como um painel administrativo genérico.

A prioridade é criar uma experiência:

- limpa;
- intuitiva;
- fluida;
- fácil de lembrar;
- fácil de navegar;
- visualmente leve;
- sem excesso de informação;
- sem lixo visual;
- sem telas poluídas;
- sem elementos competindo pela atenção;
- completa, mas progressiva;
- com boa hierarquia visual.

## Princípios de UX obrigatórios

### 1. Clareza antes de densidade

Não jogar todas as informações na tela ao mesmo tempo.

Cada tela deve mostrar primeiro o que o usuário mais precisa para tomar uma decisão ou executar uma ação.

Informações secundárias devem ficar em:
- abas;
- seções recolhíveis;
- detalhes;
- modais;
- drawers;
- tooltips;
- páginas de detalhe;
- progressive disclosure.

### 2. Caminhos fáceis de lembrar

O usuário deve conseguir lembrar o caminho das principais ações:

- cadastrar paciente;
- editar paciente;
- ver detalhes do paciente;
- acessar responsáveis;
- acessar arquivos;
- cadastrar profissional;
- acessar configurações da empresa;
- acessar tabelas de pagamento;
- fazer logout.

Evitar navegações escondidas demais ou caminhos inconsistentes.

### 3. Ação principal evidente

Cada tela deve ter uma ação principal clara.

Exemplos:
- Lista de pacientes: “Novo paciente”.
- Detalhe do paciente: “Editar paciente”.
- Profissionais: “Novo profissional”.
- Empresa: “Salvar alterações”.
- Tabelas de pagamento: “Nova tabela”.

A ação principal deve ter destaque visual maior que ações secundárias.

### 4. Hierarquia visual consistente

Usar hierarquia clara:

- título da página;
- descrição curta;
- ação principal;
- filtros essenciais;
- conteúdo principal;
- ações secundárias;
- informações complementares.

Evitar colocar muitos botões com o mesmo peso visual.

### 5. Menos ruído visual

Evitar:
- excesso de bordas;
- excesso de sombras;
- excesso de cores;
- excesso de badges;
- excesso de ícones;
- muitas divisórias;
- tabelas muito densas;
- cards demais na mesma tela;
- textos longos sem necessidade;
- toolbars lotadas;
- modais gigantes.

### 6. Completo, mas progressivo

O sistema não pode deixar de ter informações importantes, mas elas não precisam aparecer todas ao mesmo tempo.

Use:
- resumo primeiro;
- detalhe sob demanda;
- abas;
- filtros recolhíveis;
- seções avançadas;
- “ver mais”;
- campos opcionais agrupados.

### 7. Formulários bem organizados

Formulários devem ser divididos em grupos lógicos.

Exemplo para paciente:
- Identificação;
- Contato;
- Endereço;
- Convênio;
- Família/responsáveis;
- Observações.

Regras:
- não criar formulário visualmente gigante sem organização;
- destacar campos obrigatórios;
- mostrar validações próximas ao campo;
- usar máscaras;
- manter espaçamento confortável;
- evitar muitos campos na mesma linha em telas pequenas;
- preservar dados ao navegar quando possível.

### 8. Tabelas mais humanas

Tabelas devem ser limpas e fáceis de escanear.

Regras:
- mostrar apenas colunas essenciais na listagem;
- dados secundários ficam no detalhe;
- ações por linha devem ser discretas;
- status deve ser visual, mas sem exagero;
- filtros devem ser úteis, não excessivos;
- paginação clara;
- empty state amigável.

### 9. Feedback constante

Toda ação deve ter feedback claro:

- salvando;
- salvo;
- erro;
- campo inválido;
- carregando;
- nenhum registro encontrado;
- sessão expirada;
- acesso negado.

O usuário nunca deve ficar sem saber se algo aconteceu.

### 10. Consistência de padrões

A mesma ação deve parecer e se comportar da mesma forma em todo o sistema.

Exemplos:
- botão primário;
- botão secundário;
- botão destrutivo;
- filtros;
- paginação;
- modal;
- confirmação de exclusão;
- toast de sucesso;
- erro de validação;
- empty state;
- breadcrumb;
- abas.

### 11. Mobile friendly real

Não basta “não quebrar” no celular.

O sistema deve ser usável em telas menores:
- sidebar adaptável;
- tabelas responsivas;
- filtros recolhíveis;
- botões com área de toque adequada;
- formulários empilhados;
- modais com scroll interno;
- ações importantes acessíveis.

### 12. Linguagem simples

Textos da interface devem ser claros e humanos.

Evitar termos técnicos desnecessários.

Exemplos:
- “Paciente salvo com sucesso.”
- “Você não tem permissão para acessar esta área.”
- “Nenhum paciente encontrado.”
- “Revise os campos destacados.”
- “Sua sessão expirou. Faça login novamente.”

### 13. Não transformar tudo em modal

Modais devem ser usados com cuidado.

Usar modal para:
- confirmação;
- criação rápida;
- edição curta;
- ações pontuais.

Evitar modal para:
- formulários muito longos;
- telas complexas;
- fluxos com muitas etapas;
- detalhes ricos de paciente/profissional.

### 14. Fluxos principais devem ser óbvios

Nesta entrega, garantir que os principais fluxos fiquem claros:

- login;
- dashboard;
- pacientes;
- detalhe de paciente;
- profissionais;
- fornecedores;
- empresa;
- tabelas de pagamento;
- logout.

### 15. Aparência de produto final

O resultado não deve parecer um protótipo técnico.

Mesmo que algumas informações ainda sejam placeholders, a interface deve parecer parte de um produto real, com:
- espaçamento consistente;
- tipografia consistente;
- componentes reaproveitáveis;
- layout coeso;
- cores bem escolhidas;
- estados vazios bem cuidados;
- navegação previsível;
- telas menos poluídas.

---

## 10. Views por perfil

Preparar o layout para comportar diferentes perfis.

Perfis esperados:

- Administrador;
- Direção;
- Financeiro;
- Secretária/Recepção;
- Profissional clínico;
- Contas médicas;
- RH;
- Auditor/leitura.

Nesta entrega, não é necessário criar dashboards totalmente diferentes por perfil, mas a arquitetura deve permitir:

- menus diferentes por permissão;
- rotas bloqueadas por permissão;
- futuras homes específicas por perfil;
- ocultar seções sensíveis;
- impedir visualização de dados contratuais por profissional clínico.

---

## 11. Integração com módulos existentes

A SPA deve usar, quando aplicável, os endpoints já existentes.

### Auth

```txt
POST /api/v1/auth/login
POST /api/v1/auth/logout
GET  /api/v1/auth/me
POST /api/v1/auth/change-password
```

### Company

```txt
GET /api/v1/company
PUT /api/v1/company
```

### Files

```txt
POST /api/v1/files/upload
GET  /api/v1/files/{uuid}
GET  /api/v1/files/{uuid}/download
DELETE /api/v1/files/{uuid}
```

### Patients

```txt
GET /api/v1/patients
POST /api/v1/patients
GET /api/v1/patients/{uuid}
PUT /api/v1/patients/{uuid}
DELETE /api/v1/patients/{uuid}
```

### Professionals

```txt
GET /api/v1/professionals
POST /api/v1/professionals
GET /api/v1/professionals/{uuid}
PUT /api/v1/professionals/{uuid}
DELETE /api/v1/professionals/{uuid}
POST /api/v1/professionals/{uuid}/create-user
```

### Suppliers

```txt
GET /api/v1/suppliers
POST /api/v1/suppliers
GET /api/v1/suppliers/{uuid}
PUT /api/v1/suppliers/{uuid}
DELETE /api/v1/suppliers/{uuid}
```

### Payment Tables

```txt
GET /api/v1/payment-tables
POST /api/v1/payment-tables
GET /api/v1/payment-tables/{uuid}
PUT /api/v1/payment-tables/{uuid}
DELETE /api/v1/payment-tables/{uuid}
```

Não alterar contratos da API salvo se absolutamente necessário. Se precisar alterar algo, explicar claramente e manter testes passando.

---

## 12. Documentação frontend

Atualizar `README.md` ou criar:

```txt
frontend/README.md
```

Explicar:

- como abrir o frontend;
- estrutura de rotas;
- como criar novo módulo frontend;
- como usar componentes;
- como usar o HTTP service;
- como proteger uma rota;
- como adicionar item no menu;
- como verificar permissão;
- como executar validação manual básica.

---

# Validações obrigatórias

Se houver testes frontend, rodar.

Também rodar os comandos existentes do projeto:

```bash
make test
make lint
make phpstan
```

Se algum comando falhar, corrigir a causa real.

Não remover testes para fazer passar.

Não reduzir regra de lint ou PHPStan sem justificativa forte.

---

# Validação manual obrigatória

Validar manualmente:

1. Abrir `/login`.
2. Fazer login.
3. Ir para `/dashboard`.
4. Navegar pelo menu.
5. Acessar `/company`.
6. Acessar `/patients`.
7. Acessar detalhe de paciente, se houver paciente cadastrado.
8. Acessar `/professionals`.
9. Acessar `/suppliers`.
10. Acessar `/payment-tables`, se o usuário tiver permissão.
11. Fazer logout.
12. Tentar acessar rota protegida sem token.
13. Confirmar redirecionamento para `/login`.
14. Acessar rota sem permissão e confirmar tela `/403`.
15. Acessar rota inexistente e confirmar tela `/404`.
16. Testar responsividade básica em largura mobile.

---

# Critérios de aceite

A entrega só será considerada concluída se:

- existir entrada única em `frontend/index.html`;
- houver router SPA funcional;
- login funcionar dentro da SPA;
- layout autenticado estiver consolidado;
- sidebar respeitar permissões;
- rotas protegidas funcionarem;
- rotas sem permissão mostrarem 403;
- rota inexistente mostrar 404;
- telas existentes principais tiverem sido migradas para módulos SPA;
- não for mais necessário navegar por arquivos HTML soltos;
- HTTP service centralizado existir e ser usado pelos módulos principais;
- identidade visual tiver melhorado claramente;
- frontend estiver mais próximo de um SaaS clínico real;
- comandos de qualidade existentes continuarem passando;
- documentação frontend estiver atualizada.

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
- Gráficos avançados.
- Dashboard real com métricas de backend novo.
- Notificações externas.
- Integração com calendário.
- Storage em nuvem.
- Multi-tenant.

---

# Resposta esperada ao finalizar

Ao terminar, responder no seguinte formato:

```md
# Entrega 08.1 concluída

## Arquivos criados/alterados

## Nova estrutura frontend

## Rotas implementadas

## Telas migradas para SPA

## Melhorias visuais aplicadas

## Integração com Auth/ACL

## Como testar manualmente

## Resultado dos comandos executados

## Pendências reais

## O que não foi implementado porque pertence a entregas futuras
```
