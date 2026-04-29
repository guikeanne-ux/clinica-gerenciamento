# Entrega 07 — Pessoas, pacientes, responsáveis, profissionais e fornecedores


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

Implementar a base cadastral central. Paciente deve ser completo, com abas, e não um cadastro genérico raso.

## Módulo

```txt
/backend/app/Modules/Person
/frontend/modules/patients
/frontend/modules/professionals
/frontend/modules/suppliers
```

## Paciente

Campos:
- uuid;
- full_name;
- social_name;
- birth_date;
- gender;
- cpf;
- rg;
- cns;
- cid;
- health_plan_card_number;
- health_plan_name;
- email;
- father_name;
- mother_name;
- phones;
- endereço completo;
- general_notes;
- status;
- origin;
- timestamps;
- deleted_at.

Obrigatórios:
- nome completo;
- data de nascimento.

Regras:
- CPF único quando preenchido.
- Campos opcionais devem validar se preenchidos.
- Busca por nome, CPF, telefone e e-mail.
- Paginação.
- Soft delete.

## Responsáveis

Campos:
- uuid;
- patient_uuid;
- name;
- kinship;
- cpf;
- phone;
- email;
- endereço;
- notes;
- is_financial_responsible;
- is_primary_contact.

## Profissional

Campos:
- uuid;
- full_name;
- cpf;
- professional_registry;
- registry_state;
- main_specialty;
- secondary_specialties;
- phone;
- email;
- endereço;
- entry_date;
- status;
- contract_type;
- bank_data;
- schedule_color;
- availability_config;
- timestamps;
- deleted_at.

Regras:
- CPF único.
- Cor de agenda.
- Pode também ser usuário do sistema.
- Se não virar usuário no cadastro, pode virar depois.
- Não duplicar e-mail profissional/e-mail de login.

## Fornecedor

Campos:
- uuid;
- name_or_legal_name;
- document;
- contact_name;
- phone;
- email;
- endereço;
- category;
- notes;
- status.

## Frontend

Pacientes:
- listagem com busca/filtros/paginação;
- formulário único para criar e editar;
- detalhe com abas:
  - informações gerais;
  - linha do tempo placeholder;
  - prontuário placeholder;
  - arquivos placeholder.

Profissionais:
- CRUD;
- cor de agenda;
- opção “também é usuário”;
- ação para criar usuário posteriormente.

Fornecedores:
- CRUD básico.

## Permissões

```txt
patients.view
patients.create
patients.update
patients.delete
professionals.view
professionals.create
professionals.update
professionals.delete
suppliers.view
suppliers.create
suppliers.update
suppliers.delete
```

## Não implementar

Agenda real, atendimentos, financeiro, contratos detalhados ou TISS.

## Critérios de aceite

- CRUD completo.
- CPF/CNPJ duplicado bloqueado.
- Formulário de paciente cria/edita igual.
- Profissional pode virar usuário.
- Permissões funcionam.
- Auditoria registra alterações sensíveis.

## Prompt para IA

```md
Implemente somente esta entrega: módulo Person com pacientes, responsáveis, profissionais e fornecedores. Crie backend, frontend, validações, permissões, auditoria e testes. Não implemente agenda, atendimento, financeiro ou TISS.
```
