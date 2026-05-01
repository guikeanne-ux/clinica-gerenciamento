# Entrega 09.1 — Revisão obrigatória antes de prosseguir

## Como usar esta entrega

1. Envie este arquivo para a IA implementadora **antes de avançar para as próximas entregas**.
2. Peça para revisar, corrigir e estabilizar apenas os pontos descritos aqui.
3. Não permitir avanço para Atendimento, Prontuário, Financeiro operacional, TISS, Importação ou Lembretes enquanto esta revisão não estiver validada.
4. Exigir código completo, migrations quando necessárias, ajustes de frontend, testes automatizados e validação manual.
5. Se a IA tentar implementar módulos futuros, interrompa e mande voltar ao escopo desta entrega.

Esta entrega existe porque alguns pontos das entregas anteriores ficaram conceitualmente frágeis ou geraram comportamento ruim na prática. Antes de continuar, é melhor corrigir a base de Agenda, remover Fornecedores do escopo e revisar a lógica do simulador de repasse.

---

## Contexto

O sistema está sendo construído de forma incremental, com entregas pequenas e testáveis.

A ordem original coloca:

- Pessoas, pacientes, responsáveis, profissionais e fornecedores antes da agenda;
- Profissionais, contratos e regras de repasse antes da agenda;
- Agenda antes de atendimentos;
- Atendimentos antes do financeiro operacional.

Agora, antes de continuar a partir da agenda, é necessário criar uma etapa intermediária:

```txt
09.1_REVISAO_AGENDA_FORNECEDORES_REPASSE.md
```

Esta etapa deve corrigir problemas reais percebidos durante o uso inicial do sistema.

---

# Objetivo geral

Revisar e estabilizar três áreas antes de prosseguir:

1. Agenda;
2. Fornecedores;
3. Simulador de repasse profissional.

A prioridade é transformar essas áreas em módulos coerentes, simples de usar, com regras de negócio claras e sem decisões contraditórias.

---

# Regras globais obrigatórias

Manter as regras do projeto:

- PHP 8.3 puro no backend;
- Eloquent sem Laravel;
- Frontend em HTML, CSS e JavaScript vanilla;
- Monolito modular;
- API versionada em `/api/v1`;
- UUID como identificador público;
- Nunca expor `id` numérico nas APIs, rotas ou frontend;
- Services concentram regra de negócio;
- Controllers finos;
- Validators/DTOs para entrada;
- Policies/middlewares para autorização;
- Auditoria em ações críticas;
- Testes automatizados por entrega;
- Não quebrar endpoints existentes sem necessidade;
- Não usar Laravel;
- Não usar React, Vue ou Angular;
- Não criar solução temporária, mockada ou apenas demonstrativa.

---

# O que NÃO implementar nesta entrega

Não implementar:

- Atendimento clínico completo;
- Prontuário;
- Evolução/anamnese;
- Áudio;
- Timeline clínica real;
- Financeiro operacional completo;
- Geração real de contas a pagar/receber;
- TISS;
- Importação;
- Lembretes/tarefas;
- Dashboards avançados;
- Regras futuras que não sejam necessárias para estabilizar esta revisão.

---

# Parte 1 — Revisão completa da Agenda

## Problema percebido

A agenda ainda está complexa de usar e com decisões conflitantes.

Foram identificados problemas como:

- falta de hierarquia clara entre tipos de compromisso;
- conflito entre cor do profissional e cor do tipo de compromisso;
- obrigatoriedade de tipo de compromisso sem necessidade real em todos os casos;
- eventos usando a cor do tipo quando deveriam usar a cor do profissional;
- possibilidade de marcar dois compromissos para o mesmo profissional no mesmo horário;
- visualização mensal pouco útil quando existem muitos compromissos no mesmo dia;
- acessibilidade insuficiente;
- todos os compromissos exibindo “Iniciar atendimento”, mesmo quando não são atendimento.

A agenda precisa ser revista como um módulo central do sistema, não apenas como um calendário genérico.

---

## 1.1. Separar claramente os conceitos

A agenda deve trabalhar com dois conceitos principais:

### Evento comum

Usado para compromissos administrativos ou bloqueios de agenda.

Exemplos:

- reunião;
- entrevista;
- férias;
- feriado;
- lembrete;
- bloqueio de horário;
- evento do dia inteiro;
- atividade interna;
- compromisso sem paciente.

Regras:

- pode não ter paciente;
- pode não ter profissional, dependendo do tipo;
- não deve exibir botão “Iniciar atendimento”;
- não deve gerar atendimento;
- não deve gerar repasse;
- pode bloquear horário se configurado para isso.

### Atendimento agendado

Usado para sessões/consultas com paciente.

Regras:

- exige paciente;
- exige profissional;
- exige horário de início e fim;
- pode futuramente gerar atendimento;
- pode futuramente gerar repasse;
- deve poder exibir “Iniciar atendimento” somente quando a etapa de Atendimento estiver disponível ou quando já existir endpoint seguro para isso;
- deve respeitar conflito de horário do profissional;
- deve respeitar bloqueios de agenda.

---

## 1.2. Rever obrigatoriedade do tipo de compromisso

O tipo de compromisso não deve atrapalhar o cadastro.

Hoje existe um conflito: o usuário precisa selecionar um tipo de compromisso, mas o tipo também possui cor, o que compete com a cor do profissional.

A IA deve revisar o modelo e aplicar uma destas estratégias:

### Estratégia preferencial

Manter `event_type_uuid` como opcional para eventos simples, mas obrigatório quando a regra de negócio exigir.

Regras sugeridas:

- para atendimento agendado, pode existir um tipo padrão interno, como `atendimento`;
- para evento comum, o tipo pode ser opcional;
- tipos especiais, como `bloqueio`, `feriado` ou `férias`, podem ser usados quando o usuário quiser categorizar melhor;
- se o usuário não selecionar tipo, o sistema deve usar comportamento padrão seguro;
- a ausência de tipo não pode quebrar listagem, edição, filtros ou visualização.

### Alternativa aceitável

Manter tipo obrigatório somente se existir um tipo padrão automático e invisível para o usuário em fluxos simples.

Exemplo:

- “Atendimento” como tipo padrão para atendimento agendado;
- “Evento comum” como tipo padrão para evento sem paciente;
- usuário não precisa escolher manualmente sempre.

---

## 1.3. Definir hierarquia de cores

A cor exibida no calendário deve seguir uma regra objetiva.

### Regra principal

Quando o evento tiver profissional, a cor principal do compromisso deve ser a cor do profissional.

Isso é essencial para leitura visual da agenda.

Exemplo:

- profissional Ana tem cor azul;
- profissional Bruno tem cor verde;
- todos os compromissos da Ana aparecem em azul;
- todos os compromissos do Bruno aparecem em verde;
- isso deve acontecer mesmo que o tipo do compromisso tenha outra cor.

### Hierarquia obrigatória de cor

Usar esta ordem:

```txt
1. color_override do evento, somente se for usado intencionalmente por permissão específica;
2. professional.schedule_color, quando houver profissional;
3. schedule_event_types.color, quando não houver profissional ou quando a visualização estiver explicitamente agrupada por tipo;
4. cor neutra padrão do sistema.
```

### Regras complementares

- O tipo de compromisso pode aparecer como badge, ícone, marcador secundário, borda, tag ou legenda, mas não deve sobrescrever a cor principal do profissional.
- A legenda deve deixar claro se a visualização está colorindo por profissional ou por tipo.
- Se houver filtro por profissional, a cor deve continuar igual.
- Se houver múltiplos profissionais no futuro, não implementar agora; apenas não bloquear uma futura expansão.
- O frontend e o backend devem retornar uma cor resolvida ou dados suficientes para o frontend resolver de forma consistente.

### Sugestão técnica

Criar uma regra centralizada, por exemplo:

```txt
ResolveScheduleEventColorService
```

Ou método equivalente dentro do service de agenda.

Evitar lógica duplicada em vários arquivos JS.

---

## 1.4. Corrigir conflito de horário

A agenda não pode permitir dois compromissos simultâneos para o mesmo profissional, exceto quando houver permissão explícita para sobrescrever conflito.

### Regra obrigatória

Ao criar ou editar compromisso com profissional, verificar se existe outro evento conflitante.

Deve bloquear quando houver sobreposição de intervalos:

```txt
existing.starts_at < new.ends_at
AND existing.ends_at > new.starts_at
```

Ignorar na checagem:

- evento cancelado;
- evento remarcado, se a regra atual considerar remarcado como não ativo;
- evento deletado por soft delete;
- o próprio evento sendo editado.

### Eventos bloqueantes

Evento com status ou tipo bloqueante deve impedir atendimento no mesmo horário.

Exemplos:

- bloqueio;
- férias;
- feriado profissional;
- indisponibilidade;
- evento comum marcado como bloqueante.

### Permissão especial

Permitir conflito somente com permissão específica:

```txt
schedule.override_conflict
```

Regras:

- usuário sem permissão recebe erro 409 Conflict;
- usuário com permissão pode confirmar a sobrescrita;
- sobrescrita deve ser auditada;
- frontend deve mostrar aviso claro antes de salvar;
- não fazer sobrescrita silenciosa.

### Mensagem esperada

Exemplo:

```txt
Este profissional já possui compromisso nesse horário.
```

Se possível, informar horário e título do compromisso conflitante sem expor dados indevidos.

---

## 1.5. Revisar UX da visualização mensal

A visualização mensal não pode esconder compromissos sem permitir acesso fácil.

Problema atual:

- quando há mais de 4 compromissos no mesmo dia, aparece “mais”, mas o usuário não consegue ver quais são.

### Comportamento obrigatório

Quando houver mais compromissos do que cabem no card do dia:

- exibir indicador claro, como “+3 compromissos”;
- ao clicar, abrir popover, drawer ou painel lateral com todos os compromissos daquele dia;
- permitir clicar em cada compromisso para ver/editar detalhes;
- manter acessível por teclado;
- em mobile, preferir drawer ou tela de lista do dia.

### Regras de UX

- Não esconder compromissos sem ação;
- Não depender apenas de hover;
- Não usar tooltip minúsculo para conteúdo essencial;
- A lista do dia deve mostrar pelo menos:
  - horário;
  - título;
  - profissional;
  - paciente, se houver;
  - status;
  - tipo, se houver;
  - ação de abrir detalhe.

---

## 1.6. Melhorar acessibilidade da Agenda

A agenda deve ser usável de forma mais clara e acessível.

Regras mínimas:

- contraste adequado entre texto e fundo do evento;
- evento com cor escura deve usar texto claro;
- evento com cor clara deve usar texto escuro;
- não depender somente da cor para diferenciar tipo/status;
- usar texto, badge, ícone ou marcador adicional;
- elementos clicáveis devem ser acessíveis por teclado;
- botões devem ter label claro;
- modais/drawers devem ter foco controlado;
- estados vazios devem explicar o que fazer;
- loading deve ser visível;
- erro deve aparecer por toast e/ou estado de tela;
- em telas pequenas, a agenda deve continuar usável.

---

## 1.7. Corrigir botão “Iniciar atendimento”

Nem todo compromisso é atendimento.

### Regra obrigatória

Mostrar “Iniciar atendimento” somente quando:

- o evento for um atendimento agendado;
- o tipo permitir gerar atendimento (`can_generate_attendance = true`) ou campo equivalente;
- existir paciente;
- existir profissional;
- status permitir início;
- usuário tiver permissão;
- o módulo/endpoint de atendimento estiver implementado ou houver um placeholder seguro explicitamente marcado como futuro.

### Antes da Entrega 10

Como o atendimento clínico completo pertence à próxima etapa, a agenda deve evitar simular funcionalidade real inexistente.

Opções aceitáveis:

1. Ocultar completamente o botão até a Entrega 10;
2. Exibir botão desabilitado com texto claro, por exemplo:
   ```txt
   Atendimento será liberado na próxima etapa.
   ```
3. Exibir ação apenas se já existir endpoint mínimo seguro para criação de atendimento, sem implementar prontuário completo.

### Proibido

- mostrar “Iniciar atendimento” em reunião, férias, bloqueio, feriado, lembrete ou evento comum;
- mostrar botão funcional que não faz nada;
- mostrar botão que cria dados inconsistentes;
- gerar atendimento duplicado para o mesmo evento.

---

## 1.8. Revisar status da agenda

Garantir que os status façam sentido:

```txt
agendado
confirmado
realizado
cancelado
falta
remarcado
bloqueado
```

Regras:

- `cancelado` não gera atendimento;
- `falta` não gera repasse automático;
- `bloqueado` impede atendimento no horário;
- `realizado` só deve ser usado quando houver regra clara;
- `remarcado` deve preservar histórico ou referência ao novo evento, se já houver estrutura;
- alteração de status relevante deve ser auditada.

---

## 1.9. Revisar filtros e legenda

A agenda deve ter filtros úteis, mas sem excesso.

Filtros mínimos:

- profissional;
- paciente;
- período/data;
- tipo;
- status.

Legenda mínima:

- profissionais ativos com suas cores;
- tipos de evento, se usados;
- status visual;
- explicação da regra de cor quando necessário.

A legenda não deve ocupar espaço demais. Pode ser recolhível.

---

## 1.10. Testes obrigatórios da Agenda

Criar ou ajustar testes para:

- criar evento comum sem paciente;
- criar atendimento agendado com paciente e profissional;
- impedir atendimento sem paciente;
- impedir atendimento sem profissional;
- usar cor do profissional acima da cor do tipo;
- usar cor do tipo quando não houver profissional;
- usar cor padrão quando não houver cor;
- bloquear conflito de horário do mesmo profissional;
- permitir conflito apenas com `schedule.override_conflict`;
- impedir atendimento durante bloqueio;
- não exibir “Iniciar atendimento” para evento comum;
- não gerar atendimento para evento cancelado;
- validar resposta 409 para conflito;
- auditar sobrescrita de conflito;
- listar compromissos do mês com acesso aos itens excedentes;
- validar permissões principais.

---

# Parte 2 — Remover Fornecedores do escopo atual

## Decisão de produto

O sistema não deve trabalhar com fornecedores neste momento.

A área de fornecedores pode ser removida ou desativada do escopo atual para reduzir complexidade e evitar telas que não serão usadas.

---

## 2.1. O que remover ou desativar

Revisar tudo relacionado a fornecedores:

- rotas frontend;
- menu/sidebar;
- telas SPA;
- endpoints, se já foram criados;
- permissões;
- seeds;
- testes;
- documentação;
- referências em dashboard;
- referências em busca global, se houver;
- referências em financeiro futuro, se estiverem antecipadas.

### Recomendação

Se o código já existir e removê-lo gerar risco alto, fazer uma desativação limpa:

- esconder do menu;
- remover da navegação principal;
- não exibir em dashboard;
- não criar novos fornecedores;
- marcar como módulo descontinuado/não usado;
- manter tabela/endpoints apenas se necessário por compatibilidade, mas fora da UX.

### Preferência

Se ainda estiver simples remover:

- remover módulo frontend de fornecedores;
- remover rotas SPA de fornecedores;
- remover permissões `suppliers.*` dos seeds;
- remover CRUD de fornecedor;
- atualizar documentação;
- ajustar testes.

---

## 2.2. Cuidados com dependências futuras

O financeiro operacional previsto originalmente possui referência a `supplier_uuid` em contas a pagar.

Como a decisão atual é não trabalhar com fornecedores, a IA deve:

- não antecipar financeiro agora;
- não criar dependência obrigatória de fornecedor;
- quando chegar no financeiro, tratar despesas sem fornecedor obrigatório;
- usar descrição/categoria para despesas manuais;
- deixar fornecedor como possibilidade futura, não como requisito do MVP.

---

## 2.3. Critérios de aceite para Fornecedores

- menu não exibe fornecedores;
- rotas de fornecedores não aparecem na navegação principal;
- dashboard não mostra fornecedores;
- documentação deixa claro que fornecedores saíram do MVP atual;
- permissões `suppliers.*` não são necessárias para operar o sistema;
- testes não exigem fornecedor;
- nenhuma tela principal quebra pela ausência de fornecedores.

---

# Parte 3 — Revisão do Repasse Profissional e Simulador

## Problema percebido

A tela de simulação de repasse não está fazendo sentido.

Se o usuário escolhe:

- um profissional;
- uma tabela a considerar;

então não faz sentido exigir manualmente um valor bruto se a própria tabela já possui os valores necessários para cálculo.

O simulador precisa refletir a lógica real do contrato/tabela, e não pedir dados redundantes ou contraditórios.

---

## 3.1. Separar conceitos

Existem três coisas diferentes:

### Tabela de pagamento

Define valores e regras.

Exemplos:

- especialidade;
- tipo de atendimento;
- convênio;
- procedimento;
- duração;
- valor fixo;
- percentual;
- regra extra.

### Configuração de pagamento do profissional

Define como aquele profissional recebe.

Exemplos:

- fixo por atendimento;
- fixo mensal;
- híbrido;
- tabela vinculada;
- vigência;
- observações.

### Simulador

Serve para prever quanto seria pago em um cenário.

O simulador não deve inventar regra. Ele deve usar:

- profissional;
- configuração vigente;
- tabela escolhida ou tabela vinculada ao profissional;
- cenário de atendimento;
- quantidade, quando aplicável;
- período, quando aplicável.

---

## 3.2. Regra sobre valor bruto

O campo “valor bruto” não deve ser obrigatório quando a tabela selecionada já define o valor base.

### Regra obrigatória

Se a simulação usa uma tabela com item aplicável, o sistema deve resolver o valor automaticamente.

O usuário deve selecionar ou informar apenas os dados necessários para encontrar o item correto da tabela:

- profissional;
- tabela, se não vier automaticamente da configuração;
- tipo de atendimento;
- especialidade, se aplicável;
- convênio, se aplicável;
- procedimento, se aplicável;
- duração, se aplicável;
- quantidade, se modo híbrido ou mensal exigir;
- data de referência para vigência.

### Quando valor manual pode existir

Valor manual só deve existir como campo avançado e opcional para simular cenário fora da tabela.

Regras:

- deve ter label claro: “Valor manual para simulação”;
- deve ficar em seção avançada;
- não deve ser obrigatório;
- deve exigir justificativa ou indicação visual de que está sobrescrevendo a tabela;
- deve aparecer no resultado como override;
- não deve alterar tabela nem contrato;
- deve ser auditado se a simulação for persistida.

---

## 3.3. Fluxo sugerido do simulador

### Entrada mínima

```txt
Profissional
Data de referência
Tipo de simulação
```

Tipos de simulação:

```txt
1 atendimento específico
vários atendimentos no período
mensal fixo
híbrido
```

### Para “1 atendimento específico”

Campos:

- profissional;
- data de referência;
- tipo de atendimento;
- convênio;
- procedimento;
- duração;
- tabela a considerar, opcional se profissional já tem tabela vigente;
- valor manual, opcional/avançado.

Resultado:

- regra encontrada;
- tabela usada;
- item da tabela usado;
- valor base;
- percentual, se houver;
- valor calculado;
- explicação do cálculo.

### Para “vários atendimentos no período”

Campos:

- profissional;
- período;
- quantidade de atendimentos;
- tabela/configuração;
- filtros opcionais de tipo/convênio/procedimento.

Resultado:

- valor unitário resolvido;
- quantidade;
- total previsto;
- detalhes da regra.

### Para “mensal fixo”

Campos:

- profissional;
- mês de referência.

Resultado:

- valor mensal vigente;
- vigência;
- observações.

### Para “híbrido”

Campos:

- profissional;
- período/mês;
- quantidade de atendimentos.

Resultado:

- base fixa;
- limite/franquia;
- quantidade excedente;
- valor por excedente;
- total.

---

## 3.4. Resultado do simulador deve explicar o cálculo

O resultado deve ser transparente.

Exemplo:

```txt
Profissional: Ana Silva
Configuração vigente: Híbrido
Período: Maio/2026

Base fixa: R$ 3.000,00
Atendimentos incluídos: 80
Atendimentos simulados: 95
Excedente: 15
Valor por excedente: R$ 45,00

Total estimado: R$ 3.675,00
```

Ou:

```txt
Tabela usada: Psicologia 2026
Item encontrado: Terapia individual / Particular / 50 min
Valor base: R$ 120,00
Repasse: 60%

Valor estimado do repasse: R$ 72,00
```

---

## 3.5. Backend esperado para simulação

Revisar ou criar um service central de simulação:

```txt
SimulateProfessionalPayoutService
```

Ele deve:

- receber profissional;
- receber data/período;
- resolver configuração vigente;
- resolver tabela aplicável;
- resolver item aplicável da tabela;
- calcular conforme modo de pagamento;
- retornar breakdown detalhado;
- não persistir resultado por padrão;
- não gerar financeiro real;
- não gerar repasse real;
- não alterar contrato;
- não alterar tabela.

Se já existir `ResolveProfessionalPaymentRuleService`, ele deve ser usado pelo simulador, evitando duplicação.

---

## 3.6. Frontend esperado para simulação

A tela deve ser redesenhada para ser clara.

Regras:

- não mostrar todos os campos de uma vez;
- primeiro escolher profissional e tipo de simulação;
- mostrar apenas campos necessários;
- campo de valor manual deve ficar escondido em “opções avançadas”;
- resultado deve aparecer em card/resumo claro;
- mostrar memória de cálculo;
- mostrar erros por campo;
- mostrar toast para erro geral;
- preservar responsividade;
- não parecer tela técnica de debug.

---

## 3.7. Testes obrigatórios do Repasse/Simulador

Criar ou ajustar testes para:

- simular fixo por atendimento;
- simular fixo mensal;
- simular híbrido;
- resolver tabela vigente;
- resolver configuração vigente por data;
- usar item da tabela para definir valor base;
- não exigir valor bruto quando tabela resolve valor;
- permitir valor manual apenas como override opcional;
- retornar breakdown do cálculo;
- não gerar financeiro real;
- impedir acesso de profissional clínico a valores contratuais;
- permitir acesso apenas para perfis autorizados;
- auditar alterações reais de configuração, mas não simulação simples não persistida.

---

# Parte 4 — Ajustes de documentação e ordem

## Atualizar ordem de implementação

Atualizar documentação para inserir esta entrega entre a Agenda e os próximos passos.

Ordem ajustada sugerida:

```txt
09_AGENDA_ROBUSTA.md
09.1_REVISAO_AGENDA_FORNECEDORES_REPASSE.md
10_ATENDIMENTOS_PRONTUARIO_TIMELINE.md
11_MODELOS_QUESTIONARIOS_BUILDER.md
12_FINANCEIRO_OPERACIONAL_REPASSES.md
13_IMPORTACAO_DADOS_LEGADOS.md
14_TISS_MVP_FATURAMENTO.md
15_LEMBRETES_TAREFAS_DASHBOARDS.md
16_REVISAO_FINAL_HARDENING_DOCUMENTACAO.md
```

Se a implementação atual ainda estiver no meio da Entrega 09, esta entrega 09.1 deve funcionar como correção imediata antes de considerar a agenda concluída.

---

## Atualizar docs afetadas

Revisar:

- README;
- documentação de rotas frontend;
- documentação de permissões;
- seeds de permissões;
- documentação da agenda;
- documentação de repasse;
- ordem de implementação;
- OpenAPI, se já existir;
- testes e exemplos de payload.

---

# Critérios gerais de aceite

A entrega só pode ser considerada concluída se:

## Agenda

- cor do profissional prevalece sobre cor do tipo;
- tipo de compromisso não atrapalha fluxo simples;
- evento comum funciona sem paciente;
- atendimento agendado exige paciente e profissional;
- conflito de horário é bloqueado corretamente;
- conflito só pode ser sobrescrito com permissão;
- evento bloqueado impede atendimento;
- visualização mensal permite ver todos os compromissos do dia;
- botão “Iniciar atendimento” aparece somente quando faz sentido;
- agenda é mais acessível e responsiva;
- filtros e legenda funcionam;
- testes passam.

## Fornecedores

- fornecedores foram removidos ou desativados do escopo atual;
- menu, rotas e dashboard não exibem fornecedores;
- permissões de fornecedores não são necessárias para operar;
- documentação foi atualizada;
- ausência de fornecedores não quebra módulos existentes.

## Simulador de repasse

- simulador não exige valor bruto quando tabela resolve o valor;
- tabela/configuração vigente são resolvidas corretamente;
- valor manual é opcional e avançado;
- resultado mostra memória de cálculo;
- não gera financeiro real;
- não altera contrato/tabela;
- respeita permissões;
- testes passam.

## Qualidade

- `make test` passa;
- `make lint` passa;
- `make phpstan` passa;
- migrations rodam;
- seeds rodam;
- frontend continua navegável;
- não há regressão nos módulos anteriores;
- endpoints continuam usando UUID público;
- erros usam padrão global e toasts quando aplicável.

---

# Validação manual obrigatória

Validar manualmente:

1. Criar evento comum sem paciente.
2. Criar atendimento agendado com paciente e profissional.
3. Confirmar que a cor do evento segue a cor do profissional.
4. Criar tipo de evento com cor diferente e confirmar que ele não sobrescreve a cor do profissional.
5. Tentar criar dois compromissos no mesmo horário para o mesmo profissional.
6. Confirmar erro de conflito.
7. Testar sobrescrita com usuário autorizado.
8. Criar bloqueio de agenda e tentar agendar atendimento no mesmo horário.
9. Confirmar que bloqueia.
10. Criar mais de 4 compromissos no mesmo dia.
11. Abrir a visualização mensal e confirmar que todos ficam acessíveis.
12. Confirmar que evento comum não mostra “Iniciar atendimento”.
13. Confirmar que atendimento só mostra ação adequada quando permitido.
14. Confirmar que fornecedores não aparecem no menu.
15. Confirmar que rotas de fornecedores não fazem parte do fluxo principal.
16. Simular repasse por tabela sem informar valor bruto.
17. Simular repasse com valor manual opcional.
18. Confirmar memória de cálculo.
19. Confirmar que profissional clínico não acessa valores contratuais.
20. Rodar `make test`, `make lint` e `make phpstan`.

---

# Formato esperado da resposta da IA implementadora

Ao concluir, responder exatamente neste formato:

```md
## Entrega 09.1 concluída

### Arquivos criados/alterados
- ...

### Agenda
- O que foi corrigido:
- Regras de cor:
- Regras de conflito:
- Ajustes de UX:
- O que ficou fora por pertencer à Entrega 10:

### Fornecedores
- Estratégia usada: removido ou desativado
- Arquivos/rotas/permissões afetadas:
- Observações:

### Repasse profissional
- O que mudou no simulador:
- Como a tabela resolve o valor:
- Como funciona o valor manual:
- Exemplo de cálculo validado:

### Como rodar
- ...

### Como testar
- ...

### Resultado dos testes
- make test:
- make lint:
- make phpstan:

### Validação manual realizada
- ...

### Pendências reais
- ...

### O que não foi implementado por pertencer a etapas futuras
- ...
```

---

# Prompt para IA implementadora

```md
Você deve implementar somente a Entrega 09.1 — Revisão obrigatória antes de prosseguir.

Contexto:
O sistema está sendo construído em entregas incrementais. Antes de avançar para Atendimento, Prontuário, Financeiro operacional, TISS, Importação ou Lembretes, precisamos revisar três pontos que ficaram inconsistentes: Agenda, Fornecedores e Simulador de repasse profissional.

Escopo obrigatório:

1. Revisar a Agenda:
- separar claramente evento comum de atendimento agendado;
- tornar o tipo de compromisso opcional ou automático quando fizer sentido;
- definir hierarquia de cores:
  1. color_override intencional;
  2. cor do profissional;
  3. cor do tipo;
  4. cor padrão;
- garantir que a cor do profissional prevaleça sobre a cor do tipo;
- bloquear conflito de horário do mesmo profissional;
- permitir conflito apenas com a permissão `schedule.override_conflict`;
- fazer eventos bloqueantes impedirem atendimento;
- melhorar a visualização mensal para permitir ver todos os compromissos do dia;
- melhorar acessibilidade e responsividade da agenda;
- exibir “Iniciar atendimento” somente quando o evento realmente for atendimento e quando a funcionalidade estiver disponível/permitida;
- não mostrar “Iniciar atendimento” em reunião, bloqueio, férias, feriado, lembrete ou evento comum.

2. Remover ou desativar Fornecedores do escopo atual:
- remover/esconder menu, rotas e telas de fornecedores;
- remover dependência operacional de permissões `suppliers.*`;
- atualizar documentação;
- garantir que nenhum fluxo principal quebre sem fornecedores;
- não antecipar financeiro para resolver fornecedor.

3. Revisar o simulador de repasse profissional:
- não exigir valor bruto quando uma tabela/configuração já resolve o valor;
- usar profissional, data/período, configuração vigente e tabela aplicável;
- permitir valor manual apenas como override opcional em seção avançada;
- retornar memória de cálculo detalhada;
- não gerar financeiro real;
- não alterar contrato/tabela;
- respeitar sigilo: profissional clínico não acessa valores contratuais.

Regras técnicas:
- PHP 8.3 puro;
- Eloquent sem Laravel;
- frontend em HTML, CSS e JavaScript vanilla;
- monolito modular;
- API `/api/v1`;
- UUID público obrigatório;
- não expor id numérico;
- services para regras de negócio;
- controllers finos;
- validators/DTOs;
- policies/middlewares;
- auditoria em ações críticas;
- testes automatizados;
- não usar Laravel, React, Vue ou Angular.

Não implementar:
- Atendimento clínico completo;
- Prontuário;
- Timeline;
- Financeiro operacional real;
- TISS;
- Importação;
- Lembretes;
- Dashboards avançados.

Critérios de aceite:
- `make test` passa;
- `make lint` passa;
- `make phpstan` passa;
- migrations e seeds rodam;
- agenda bloqueia conflitos corretamente;
- cor do profissional prevalece;
- visualização mensal mostra todos os compromissos;
- fornecedores não aparecem no fluxo principal;
- simulador calcula usando tabela/configuração sem exigir valor bruto;
- resultado do simulador mostra memória de cálculo;
- permissões e auditoria continuam funcionando.

Ao terminar, responda com:
- arquivos criados/alterados;
- como rodar;
- como testar;
- resultado dos testes;
- validação manual feita;
- pendências reais;
- o que não foi implementado por pertencer a etapas futuras.
```
