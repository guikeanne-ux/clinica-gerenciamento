import { http } from '../../core/services/http.js';
import { navigate } from '../../core/router/router.js';
import { initMasks } from '../../core/js/masks.js';
import { toast } from '../../core/js/toast.js';

export default {
  _savedEmail: '',
  _paymentTables: [],
  _activePaymentConfigUuid: null,

  async mount(container, params) {
    const uuid = params?.uuid;
    const isEdit = !!uuid;

    container.innerHTML = `
      <div class="page-header">
        <div class="page-header__info">
          <h1>${isEdit ? 'Editar profissional' : 'Novo profissional'}</h1>
          <p class="subtitle">${isEdit ? 'Atualize os dados do profissional' : 'Preencha os dados do profissional'}</p>
        </div>
        <div class="page-header__actions">
          <button class="btn btn-secondary btn-md" id="btn-back-prof">← Voltar</button>
        </div>
      </div>

      <div class="section">
        <form id="prof-form">
          <input type="hidden" name="uuid" value="${uuid || ''}" />

          <div class="form-group">
            <div class="form-group__title">Identificação</div>
            <div class="form-grid">
              <div class="field" style="grid-column:1/-1;">
                <label>Nome completo <span style="color:var(--error);">*</span></label>
                <input class="input" name="full_name" required placeholder="Nome completo" />
              </div>
              <div class="field">
                <label>CPF</label>
                <input class="input" name="cpf" data-mask="cpf" placeholder="000.000.000-00" />
              </div>
              <div class="field">
                <label>Data de entrada</label>
                <input class="input" name="entry_date" type="date" />
              </div>
            </div>
          </div>

          <div class="form-group">
            <div class="form-group__title">Dados profissionais</div>
            <div class="form-grid">
              <div class="field">
                <label>Tipo de conselho</label>
                <select class="input" name="council_type">
                  <option value="">Selecione</option>
                  <option value="CRP">CRP — Psicologia</option>
                  <option value="CRFa">CRFa — Fonoaudiologia</option>
                  <option value="CREFa">CREFa — Fonoaudiologia (alternativo)</option>
                  <option value="CREFITO">CREFITO — Fisioterapia</option>
                  <option value="CRM">CRM — Medicina</option>
                  <option value="CRO">CRO — Odontologia</option>
                  <option value="COREN">COREN — Enfermagem</option>
                  <option value="CRN">CRN — Nutrição</option>
                  <option value="CRESS">CRESS — Serviço Social</option>
                </select>
              </div>
              <div class="field">
                <label>Número do registro</label>
                <input class="input" name="council_number" placeholder="Ex: 12345" />
              </div>
              <div class="field">
                <label>UF do conselho</label>
                <input class="input" name="registry_state" placeholder="Ex: SP" maxlength="2" style="text-transform:uppercase;" />
              </div>
            </div>

            <div class="field" style="margin-top:0.75rem;">
              <label>Especialidades</label>
              <div id="specialties-container">
                <div class="skeleton" style="height:48px;border-radius:var(--r-sm);"></div>
              </div>
              <div style="margin-top:0.5rem;display:flex;gap:0.5rem;align-items:center;">
                <input class="input" id="new-specialty-input" placeholder="Nova especialidade…" style="flex:1;" />
                <button type="button" class="btn btn-secondary btn-sm" id="btn-add-specialty">Adicionar</button>
              </div>
            </div>
          </div>

          <div class="form-group">
            <div class="form-group__title">Contato</div>
            <div class="form-grid">
              <div class="field">
                <label>E-mail</label>
                <input class="input" name="email" type="email" placeholder="email@clinica.com.br" />
              </div>
              <div class="field">
                <label>Telefone</label>
                <input class="input" name="phone" data-mask="phone" placeholder="(00) 00000-0000" />
              </div>
            </div>
          </div>

          <div class="form-group">
            <div class="form-group__title">Acesso ao sistema</div>
            ${!isEdit ? `
            <div class="field">
              <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer;">
                <input type="checkbox" name="also_user" value="1" />
                Criar usuário de acesso ao sistema automaticamente
              </label>
              <div class="hint">Usuário será criado com login no e-mail do profissional.</div>
            </div>` : `
            <div id="linked-user-box" class="text-sm text-muted">Carregando vínculo de usuário…</div>
            `}
          </div>

          ${isEdit ? `
          <div class="form-group">
            <div class="form-group__title">Configuração de repasse</div>
            <p class="text-sm text-muted" style="margin-bottom:0.6rem;">
              Escolha uma regra pronta da clínica ou personalize o repasse deste profissional.
            </p>
            <div id="professional-payment-config-box">
              <div class="skeleton" style="height:120px;border-radius:var(--r-sm);"></div>
            </div>
          </div>` : ''}

        </form>

          <div style="margin-top:1rem;display:flex;gap:0.5rem;flex-wrap:wrap;">
          <button class="btn btn-primary btn-md" id="btn-save-prof">
            ${isEdit ? 'Salvar alterações' : 'Cadastrar profissional'}
          </button>
          <button class="btn btn-secondary btn-md" id="btn-cancel-prof">Cancelar</button>
          ${isEdit ? `
            <button class="btn btn-outline btn-md" id="btn-create-user-prof">Criar usuário de acesso</button>
            <button class="btn btn-danger btn-md" id="btn-delete-prof" style="margin-left:auto;">Excluir</button>
          ` : ''}
        </div>
      </div>`;

    initMasks();
    await this._loadSpecialties();
    if (isEdit) await this._load(uuid);

    document.getElementById('btn-back-prof')?.addEventListener('click', () => navigate('/professionals'));
    document.getElementById('btn-cancel-prof')?.addEventListener('click', () => navigate('/professionals'));
    document.getElementById('btn-save-prof')?.addEventListener('click', () => this._save(isEdit, uuid));
    document.getElementById('btn-delete-prof')?.addEventListener('click', () => this._delete(uuid));
    document.getElementById('btn-create-user-prof')?.addEventListener('click', () => this._createUser(uuid));
    document.getElementById('btn-add-specialty')?.addEventListener('click', () => this._addSpecialty());
    document.getElementById('new-specialty-input')?.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') { e.preventDefault(); this._addSpecialty(); }
    });
    document.getElementById('btn-save-payment-config')?.addEventListener('click', () => this._savePaymentConfig(uuid));
    document.getElementById('payment-source-mode')?.addEventListener('change', () => this._togglePaymentSourceFields());
    document.getElementById('payment-mode')?.addEventListener('change', () => this._togglePaymentModeFields());
    document.getElementById('btn-open-payment-simulator')?.addEventListener('click', () => navigate(`/professional-payment?professional_uuid=${uuid}`));
  },

  _specialties: [],
  _selectedSpecialties: [],

  async _loadSpecialties() {
    const res = await http.get('/api/v1/specialties?status=active');
    this._specialties = res.success ? (res.data || []) : [];
    this._renderSpecialties();
  },

  _renderSpecialties() {
    const container = document.getElementById('specialties-container');
    if (!container) return;

    if (!this._specialties.length) {
      container.innerHTML = `<div class="text-sm text-muted">Nenhuma especialidade cadastrada. Use o campo abaixo para adicionar.</div>`;
      return;
    }

    container.innerHTML = `<div style="display:flex;flex-wrap:wrap;gap:0.4rem;padding:0.5rem 0;">
      ${this._specialties.map((s) => {
        const sel = this._selectedSpecialties.includes(s.name);
        return `<button type="button" class="btn ${sel ? 'btn-primary' : 'btn-ghost'} btn-sm specialty-chip" data-name="${s.name}" style="border-radius:999px;">
          ${s.name}
        </button>`;
      }).join('')}
    </div>`;

    container.querySelectorAll('.specialty-chip').forEach((chip) => {
      chip.addEventListener('click', () => {
        const name = chip.dataset.name;
        if (this._selectedSpecialties.includes(name)) {
          this._selectedSpecialties = this._selectedSpecialties.filter(n => n !== name);
        } else {
          this._selectedSpecialties.push(name);
        }
        this._renderSpecialties();
      });
    });
  },

  async _addSpecialty() {
    const input = document.getElementById('new-specialty-input');
    const name = input?.value.trim();
    if (!name) return;
    const res = await http.post('/api/v1/specialties', { name });
    if (res.success) {
      input.value = '';
      await this._loadSpecialties();
      if (!this._selectedSpecialties.includes(name)) {
        this._selectedSpecialties.push(name);
        this._renderSpecialties();
      }
      toast.success('Especialidade adicionada com sucesso.');
    } else {
      toast.error(res.message || 'Não foi possível adicionar a especialidade.');
    }
  },

  async _load(uuid) {
    const res = await http.get(`/api/v1/professionals/${uuid}`);
    if (!res.success) return;
    const form = document.getElementById('prof-form');
    const d = res.data || {};
    this._savedEmail = String(d.email || '').trim();
    Object.entries(d).forEach(([k, v]) => {
      const el = form?.querySelector(`[name="${k}"]`);
      if (el && v != null) el.value = v;
    });

    // Split professional_registry back into council_type + council_number
    if (d.professional_registry) {
      const parts = d.professional_registry.split(' ');
      const knownTypes = ['CRP','CRFa','CREFa','CREFITO','CRM','CRO','COREN','CRN','CRESS'];
      if (knownTypes.includes(parts[0])) {
        const typeEl = form?.querySelector('[name="council_type"]');
        const numEl = form?.querySelector('[name="council_number"]');
        if (typeEl) typeEl.value = parts[0];
        if (numEl) numEl.value = parts.slice(1).join(' ');
      } else {
        const numEl = form?.querySelector('[name="council_number"]');
        if (numEl) numEl.value = d.professional_registry;
      }
    }

    // Load selected specialties from secondary_specialties_json
    try {
      const stored = d.secondary_specialties_json ? JSON.parse(d.secondary_specialties_json) : [];
      this._selectedSpecialties = Array.isArray(stored) ? stored : [];
    } catch {
      this._selectedSpecialties = [];
    }
    // Also include main_specialty if set and not already in list
    if (d.main_specialty && !this._selectedSpecialties.includes(d.main_specialty)) {
      this._selectedSpecialties.unshift(d.main_specialty);
    }
    this._renderSpecialties();

    const linkedBox = document.getElementById('linked-user-box');
    if (linkedBox) {
      if (d.linked_user) {
        linkedBox.innerHTML = `
          <div class="card card--active">
            <div><strong>Usuário vinculado:</strong> ${d.linked_user.name || 'Sem nome'}</div>
            <div><strong>Login:</strong> ${d.linked_user.login || 'Não informado'}</div>
            <div><strong>E-mail:</strong> ${d.linked_user.email || 'Não informado'}</div>
            <div><strong>Status:</strong> ${d.linked_user.status === 'active' ? 'Ativo' : 'Inativo'}</div>
          </div>`;
      } else {
        linkedBox.innerHTML = '<div class="text-sm text-muted">Nenhum usuário de sistema vinculado ainda.</div>';
      }
    }

    await this._loadPaymentConfigSection(uuid);
  },

  async _loadPaymentConfigSection(uuid) {
    const box = document.getElementById('professional-payment-config-box');
    if (!box) return;

    const [tablesRes, configsRes] = await Promise.all([
      http.get('/api/v1/payment-tables?status=active&per_page=100'),
      http.get(`/api/v1/professionals/${uuid}/payment-configs?status=active&per_page=1&sort=effective_start_date&direction=desc`),
    ]);

    this._paymentTables = tablesRes.success ? (tablesRes.data?.items || []) : [];
    const activeConfig = configsRes.success ? (configsRes.data?.items || [])[0] : null;
    this._activePaymentConfigUuid = activeConfig?.uuid || null;

    box.innerHTML = `
      <div class="card card--active" style="padding:0.75rem;">
        <div class="form-grid">
          <div class="field" style="grid-column:1/-1;">
            <label>Como deseja configurar o repasse?</label>
            <select id="payment-source-mode" class="input">
              <option value="table" ${activeConfig?.payment_table_uuid ? 'selected' : ''}>Usar tabela de pagamento da clínica</option>
              <option value="custom" ${activeConfig && !activeConfig.payment_table_uuid ? 'selected' : ''}>Criar regra específica para este profissional</option>
            </select>
            <div class="hint">A tabela da clínica evita retrabalho. A regra específica sobrescreve apenas este profissional.</div>
          </div>
          <div class="field" id="field-payment-table">
            <label>Tabela de pagamento da clínica</label>
            <select id="payment-table-uuid" class="input">
              <option value="">Selecione uma tabela</option>
              ${this._paymentTables.map((table) => `<option value="${table.uuid}" ${activeConfig?.payment_table_uuid === table.uuid ? 'selected' : ''}>${table.name}</option>`).join('')}
            </select>
            <div class="hint">Essa tabela será usada como base para o cálculo do repasse.</div>
          </div>
          <div class="field">
            <label>Modo de pagamento</label>
            <select id="payment-mode" class="input">
              <option value="fixed_per_attendance" ${activeConfig?.payment_mode === 'fixed_per_attendance' ? 'selected' : ''}>Fixo por atendimento</option>
              <option value="fixed_monthly" ${activeConfig?.payment_mode === 'fixed_monthly' ? 'selected' : ''}>Fixo mensal</option>
              <option value="hybrid" ${activeConfig?.payment_mode === 'hybrid' ? 'selected' : ''}>Híbrido</option>
            </select>
          </div>
          <div class="field" id="field-payment-status">
            <label>Status</label>
            <select id="payment-status" class="input">
              <option value="active" ${activeConfig?.status !== 'inactive' ? 'selected' : ''}>Ativo</option>
              <option value="inactive" ${activeConfig?.status === 'inactive' ? 'selected' : ''}>Inativo</option>
            </select>
          </div>
          <div class="field" id="field-payment-effective-start-date">
            <label>Início da vigência</label>
            <input id="payment-effective-start-date" class="input" type="date" value="${activeConfig?.effective_start_date || ''}" />
          </div>
          <div class="field" id="field-payment-effective-end-date">
            <label>Fim da vigência</label>
            <input id="payment-effective-end-date" class="input" type="date" value="${activeConfig?.effective_end_date || ''}" />
          </div>
          <div class="field payment-custom-field" id="field-fixed-per-attendance">
            <label>Valor por atendimento</label>
            <input id="payment-fixed-per-attendance-amount" class="input" data-mask="money" placeholder="R$ 0,00" value="${this._formatMoney(activeConfig?.fixed_per_attendance_amount)}" />
            <div class="hint">Exemplo: R$ 50,00 por atendimento realizado.</div>
          </div>
          <div class="field payment-custom-field" id="field-fixed-monthly">
            <label>Valor mensal fixo</label>
            <input id="payment-fixed-monthly-amount" class="input" data-mask="money" placeholder="R$ 0,00" value="${this._formatMoney(activeConfig?.fixed_monthly_amount)}" />
            <div class="hint">Exemplo: R$ 6.500,00 por mês.</div>
          </div>
          <div class="field payment-custom-field" id="field-hybrid-base">
            <label>Base híbrida</label>
            <input id="payment-hybrid-base-amount" class="input" data-mask="money" placeholder="R$ 0,00" value="${this._formatMoney(activeConfig?.hybrid_base_amount)}" />
          </div>
          <div class="field payment-custom-field" id="field-hybrid-threshold">
            <label>Limite híbrido (atendimentos)</label>
            <input id="payment-hybrid-threshold-quantity" class="input" type="number" min="0" step="1" value="${activeConfig?.hybrid_threshold_quantity ?? ''}" />
          </div>
          <div class="field payment-custom-field" id="field-hybrid-extra">
            <label>Valor extra por excedente</label>
            <input id="payment-hybrid-extra-amount" class="input" data-mask="money" placeholder="R$ 0,00" value="${this._formatMoney(activeConfig?.hybrid_extra_amount_per_attendance)}" />
            <div class="hint">Valor pago por atendimento acima do limite híbrido.</div>
          </div>
          <div class="field" style="grid-column:1/-1;">
            <label>Observações</label>
            <textarea id="payment-notes" class="input" placeholder="Observações da configuração">${activeConfig?.notes || ''}</textarea>
          </div>
        </div>
        <div style="margin-top:0.75rem;display:flex;gap:0.5rem;flex-wrap:wrap;">
          <button type="button" class="btn btn-primary btn-sm" id="btn-save-payment-config">Salvar repasse</button>
          <button type="button" class="btn btn-secondary btn-sm" id="btn-open-payment-simulator">Abrir simulador</button>
        </div>
      </div>`;

    initMasks();
    document.getElementById('btn-save-payment-config')?.addEventListener('click', () => this._savePaymentConfig(uuid));
    document.getElementById('payment-source-mode')?.addEventListener('change', () => this._togglePaymentSourceFields());
    document.getElementById('payment-mode')?.addEventListener('change', () => this._togglePaymentModeFields());
    this._togglePaymentSourceFields();
    this._togglePaymentModeFields();
  },

  _togglePaymentSourceFields() {
    const sourceMode = document.getElementById('payment-source-mode')?.value;
    const tableField = document.getElementById('field-payment-table');
    const customFields = document.querySelectorAll('.payment-custom-field');
    const tableSelect = document.getElementById('payment-table-uuid');
    const modeField = document.getElementById('payment-mode')?.closest('.field');
    const statusField = document.getElementById('field-payment-status');
    const startField = document.getElementById('field-payment-effective-start-date');
    const endField = document.getElementById('field-payment-effective-end-date');

    const usingTable = sourceMode === 'table';
    if (tableField) tableField.style.display = usingTable ? '' : 'none';
    if (modeField) modeField.style.display = usingTable ? 'none' : '';
    if (statusField) statusField.style.display = usingTable ? 'none' : '';
    if (startField) startField.style.display = usingTable ? 'none' : '';
    if (endField) endField.style.display = usingTable ? 'none' : '';
    customFields.forEach((field) => {
      field.style.display = usingTable ? 'none' : '';
    });

    if (usingTable) {
      this._clearMoneyInput('payment-fixed-per-attendance-amount');
      this._clearMoneyInput('payment-fixed-monthly-amount');
      this._clearMoneyInput('payment-hybrid-base-amount');
      this._clearMoneyInput('payment-hybrid-extra-amount');
      const hybridThreshold = document.getElementById('payment-hybrid-threshold-quantity');
      if (hybridThreshold) hybridThreshold.value = '';
      document.getElementById('payment-mode').value = 'fixed_per_attendance';
    } else if (tableSelect) {
      tableSelect.value = '';
    }
  },

  _togglePaymentModeFields() {
    const sourceMode = document.getElementById('payment-source-mode')?.value;
    if (sourceMode === 'table') {
      ['field-fixed-per-attendance', 'field-fixed-monthly', 'field-hybrid-base', 'field-hybrid-threshold', 'field-hybrid-extra']
        .forEach((id) => {
          const el = document.getElementById(id);
          if (el) el.style.display = 'none';
        });
      return;
    }

    const mode = document.getElementById('payment-mode')?.value;
    const show = (id, shouldShow) => {
      const el = document.getElementById(id);
      if (el) el.style.display = shouldShow ? '' : 'none';
    };

    show('field-fixed-per-attendance', mode === 'fixed_per_attendance');
    show('field-fixed-monthly', mode === 'fixed_monthly');
    show('field-hybrid-base', mode === 'hybrid');
    show('field-hybrid-threshold', mode === 'hybrid');
    show('field-hybrid-extra', mode === 'hybrid');
  },

  _clearMoneyInput(id) {
    const input = document.getElementById(id);
    if (input) input.value = '';
  },

  _formatMoney(value) {
    if (value === null || value === undefined || value === '') return '';
    const num = Number(value);
    if (Number.isNaN(num)) return '';
    return num.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
  },

  _parseMoney(value) {
    const normalized = String(value || '').replace(/[^\d,.-]/g, '').replace(/\./g, '').replace(',', '.');
    if (normalized.trim() === '') return null;
    const num = Number(normalized);
    return Number.isNaN(num) ? null : num;
  },

  async _savePaymentConfig(professionalUuid) {
    const sourceMode = document.getElementById('payment-source-mode')?.value || 'table';
    const paymentMode = document.getElementById('payment-mode')?.value || 'fixed_per_attendance';
    const effectiveStartDate = document.getElementById('payment-effective-start-date')?.value || '';
    const effectiveEndDate = document.getElementById('payment-effective-end-date')?.value || '';

    const data = {
      payment_mode: paymentMode,
      status: document.getElementById('payment-status')?.value || 'active',
      effective_start_date: effectiveStartDate,
      effective_end_date: effectiveEndDate || null,
      notes: document.getElementById('payment-notes')?.value?.trim() || null,
    };

    if (sourceMode === 'table') {
      const tableUuid = document.getElementById('payment-table-uuid')?.value || '';
      if (!tableUuid) {
        toast.warning('Selecione uma tabela de pagamento da clínica para continuar.');
        return;
      }
      const table = this._paymentTables.find((item) => item.uuid === tableUuid) || null;
      data.payment_table_uuid = tableUuid;
      data.payment_mode = (table?.calculation_type && table.calculation_type !== 'custom')
        ? table.calculation_type
        : 'fixed_per_attendance';
      data.status = table?.status || 'active';
      data.effective_start_date = table?.effective_start_date || '';
      data.effective_end_date = table?.effective_end_date || null;
      if (!data.effective_start_date) {
        toast.warning('A tabela selecionada não possui início de vigência. Ajuste a tabela antes de usar.');
        return;
      }
    } else if (paymentMode === 'fixed_per_attendance') {
      data.fixed_per_attendance_amount = this._parseMoney(document.getElementById('payment-fixed-per-attendance-amount')?.value);
    } else if (paymentMode === 'fixed_monthly') {
      data.fixed_monthly_amount = this._parseMoney(document.getElementById('payment-fixed-monthly-amount')?.value);
    } else if (paymentMode === 'hybrid') {
      data.hybrid_base_amount = this._parseMoney(document.getElementById('payment-hybrid-base-amount')?.value);
      data.hybrid_threshold_quantity = Number(document.getElementById('payment-hybrid-threshold-quantity')?.value || 0);
      data.hybrid_extra_amount_per_attendance = this._parseMoney(document.getElementById('payment-hybrid-extra-amount')?.value);
    }

    Object.keys(data).forEach((key) => {
      if (data[key] === '' || data[key] === null || Number.isNaN(data[key])) {
        delete data[key];
      }
    });

    if (sourceMode !== 'table' && !data.effective_start_date) {
      toast.warning('Informe a data de início da vigência do repasse.');
      return;
    }

    const endpoint = this._activePaymentConfigUuid
      ? `/api/v1/professional-payment-configs/${this._activePaymentConfigUuid}`
      : `/api/v1/professionals/${professionalUuid}/payment-configs`;
    const action = this._activePaymentConfigUuid ? http.put : http.post;
    let res = await action(endpoint, data);

    if (!res.success && res?.meta?.error_code === 'CONFLICT') {
      const referenceMonth = String(data.effective_start_date || '').slice(0, 7);
      const fallback = await http.get(
        `/api/v1/professionals/${professionalUuid}/payment-configs?status=active&per_page=1`
        + `${referenceMonth ? `&reference_month=${encodeURIComponent(referenceMonth)}` : ''}`
      );
      const currentActive = fallback.success ? (fallback.data?.items || [])[0] : null;

      if (currentActive?.uuid) {
        res = await http.put(`/api/v1/professional-payment-configs/${currentActive.uuid}`, data);
      }
    }

    if (!res.success) {
      toast.error(this._friendlyPaymentConfigError(res));
      return;
    }

    toast.success('Configuração de repasse salva com sucesso.');
    await this._loadPaymentConfigSection(professionalUuid);
  },

  _friendlyPaymentConfigError(res) {
    const field = res?.errors?.[0]?.field || '';
    const code = res?.meta?.error_code || '';

    if (code === 'CONFLICT' || field.includes('vigência')) {
      return 'Já existe uma configuração ativa nesse período. Ajuste as datas de vigência.';
    }

    if (field === 'payment_mode') {
      return 'Selecione um modo de pagamento válido.';
    }

    if (field === 'fixed_monthly_amount') {
      return 'Informe um valor mensal fixo válido para esse modo de pagamento.';
    }

    if (field === 'fixed_per_attendance_amount') {
      return 'Informe um valor por atendimento válido.';
    }

    if (field === 'hybrid_base_amount' || field === 'hybrid_threshold_quantity' || field === 'hybrid_extra_amount_per_attendance') {
      return 'Preencha corretamente os campos do modo híbrido.';
    }

    if (field === 'payment_table_uuid') {
      return 'A tabela selecionada não foi encontrada. Atualize a página e selecione novamente.';
    }

    return 'Não foi possível salvar o repasse agora. Revise os dados e tente novamente.';
  },

  async _save(isEdit, uuid) {
    const form = document.getElementById('prof-form');
    const btn = document.getElementById('btn-save-prof');
    const data = Object.fromEntries(new FormData(form));
    if (!data.uuid) delete data.uuid;
    if (data.also_user) data.also_user = true; else delete data.also_user;

    // Combine council_type + council_number into professional_registry
    const councilType = (data.council_type || '').trim();
    const councilNum = (data.council_number || '').trim();
    const combined = [councilType, councilNum].filter(Boolean).join(' ');
    if (combined) data.professional_registry = combined;
    else delete data.professional_registry;
    delete data.council_type;
    delete data.council_number;

    // Store specialties
    data.main_specialty = this._selectedSpecialties[0] || null;
    data.secondary_specialties_json = JSON.stringify(this._selectedSpecialties);

    // Strip empty strings — empty dates cause SQL errors on DATE columns
    Object.keys(data).forEach(k => { if (data[k] === '') delete data[k]; });

    btn.disabled = true;
    btn.textContent = 'Salvando…';

    const res = isEdit
      ? await http.put(`/api/v1/professionals/${uuid}`, data)
      : await http.post('/api/v1/professionals', data);

    btn.disabled = false;
    btn.textContent = isEdit ? 'Salvar alterações' : 'Cadastrar profissional';

    if (res.success) {
      toast.success('Profissional salvo com sucesso.');
      if (!isEdit && res.data?.uuid) setTimeout(() => navigate(`/professionals/${res.data.uuid}`), 800);
    } else {
      toast.error(res.message || 'Não foi possível salvar o profissional.');
    }
  },

  async _createUser(uuid) {
    const currentEmail = String(
      document.querySelector('#prof-form [name="email"]')?.value || ''
    ).trim();

    if (!currentEmail) {
      toast.warning('Informe um e-mail válido para o profissional e salve antes de criar o usuário.');
      return;
    }

    if (currentEmail !== this._savedEmail) {
      toast.warning('Você alterou o e-mail. Salve o profissional antes de criar o usuário de acesso.');
      return;
    }

    const res = await http.post(`/api/v1/professionals/${uuid}/create-user`);
    if (res.success) {
      const email = (res.data?.email || '').trim();
      const login = (res.data?.email || '').trim();
      const message = email
        ? `Usuário criado. Login: ${login}. Senha provisória: alterar123.`
        : (res.message || 'Usuário criado com sucesso.');
      toast.success(message);
      await this._load(uuid);
      return;
    }

    toast.error(res.message || 'Não foi possível criar o usuário.');
  },

  async _delete(uuid) {
    if (!confirm('Tem certeza que deseja excluir este profissional?')) return;
    const res = await http.delete(`/api/v1/professionals/${uuid}`);
    if (res.success) {
      toast.success('Profissional excluído com sucesso.');
      navigate('/professionals');
      return;
    }

    toast.error(res.message || 'Não foi possível excluir o profissional.');
  },

  unmount() {
    this._selectedSpecialties = [];
    this._specialties = [];
    this._paymentTables = [];
    this._activePaymentConfigUuid = null;
  },
};
