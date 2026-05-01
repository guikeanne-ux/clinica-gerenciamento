import { http } from '../../core/services/http.js';
import { navigate } from '../../core/router/router.js';
import { toast } from '../../core/js/toast.js';
import { initMasks } from '../../core/js/masks.js';

function paymentConfigMessage(res, fallback) {
  const field = res?.errors?.[0]?.field || '';
  const code = res?.meta?.error_code || '';

  if (code === 'NOT_FOUND') return 'Não existe configuração de repasse vigente para o período informado.';
  if (code === 'CONFLICT') return 'Já existe uma configuração ativa nesse período para este profissional.';
  if (field === 'effective_start_date') return 'Informe a data de início da vigência.';
  if (field === 'payment_mode') return 'Selecione um modo de pagamento válido.';
  if (field === 'fixed_monthly_amount') return 'Informe um valor mensal válido.';
  if (field === 'fixed_per_attendance_amount') return 'Informe um valor por atendimento válido.';
  if (field === 'manual_override_reason') return 'Informe a justificativa do valor manual.';
  if (field === 'hybrid_base_amount' || field === 'hybrid_threshold_quantity' || field === 'hybrid_extra_amount_per_attendance') {
    return 'Preencha corretamente os campos do modo híbrido.';
  }

  return fallback;
}

function formatCurrencyBRL(value) {
  return Number(value || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
}

function buildReferenceDateFromMonth(referenceMonth) {
  const [y, m] = String(referenceMonth || '').split('-').map(Number);
  if (!y || !m) return null;
  const lastDay = new Date(y, m, 0).getDate();
  return `${String(y).padStart(4, '0')}-${String(m).padStart(2, '0')}-${String(lastDay).padStart(2, '0')}`;
}

function simulationTypeLabel(type) {
  const labels = {
    single_attendance: '1 atendimento específico',
    period_attendances: 'Vários atendimentos no período',
    monthly_fixed: 'Mensal fixo',
    hybrid: 'Híbrido',
  };
  return labels[type] || type || 'Simulação';
}

export default {
  _selectedProfessional: null,
  _selectedTable: null,

  async mount(container) {
    container.innerHTML = `
      <div class="page-header">
        <div class="page-header__info">
          <h1>Repasse profissional</h1>
          <p class="subtitle">Configure regras de repasse e simule valores com clareza</p>
        </div>
      </div>

      <div class="dashboard-bottom">

        <!-- Tabelas ativas -->
        <div class="section" id="professional-payment-config-section">
          <div class="section__header">
            <h2>Tabelas ativas</h2>
            <a href="/payment-tables" data-link class="btn btn-ghost btn-sm">Gerenciar</a>
          </div>
          <div class="filters-bar" style="margin-bottom:0.75rem;">
            <div class="field"><input id="pt-search" class="input" placeholder="Buscar tabela…" /></div>
            <button class="btn btn-secondary btn-sm" id="btn-search-pt">Buscar</button>
          </div>
          <div id="tables-list"><div class="skeleton" style="height:120px;border-radius:var(--r-sm);"></div></div>
        </div>

        <!-- Simulação -->
        <div class="section">
          <div class="section__header"><h2>Simular repasse</h2></div>
          <p class="text-sm text-muted" style="margin-bottom:0.75rem;">
            Use esta simulação para validar cenários antes do fechamento. Nenhum lançamento financeiro real será gerado aqui.
          </p>

          <!-- Busca de profissional -->
          <div class="field" style="margin-bottom:0.75rem;">
            <label>Profissional</label>
            <div style="display:flex;gap:0.5rem;">
              <input class="input" id="prof-search-input" placeholder="Buscar por nome…" style="flex:1;" />
              <button class="btn btn-secondary btn-sm" id="btn-search-prof">Buscar</button>
            </div>
            <div id="prof-search-results" style="margin-top:0.4rem;"></div>
            <div id="prof-selected" style="margin-top:0.4rem;display:none;">
              <div class="card card--active" style="display:flex;align-items:center;justify-content:space-between;padding:0.5rem 0.75rem;">
                <span id="prof-selected-name" class="font-semibold text-sm"></span>
                <button class="btn btn-ghost btn-xs" id="btn-clear-prof">Trocar</button>
              </div>
            </div>
          </div>

          <!-- Tabela selecionada -->
          <div class="field" style="margin-bottom:0.75rem;">
            <label>Tabela de pagamento</label>
            <div id="table-selected-info">
              <div class="text-sm text-muted" style="padding:0.5rem 0;">Se necessário, selecione uma tabela ao lado para usar como referência.</div>
            </div>
          </div>

          <div class="form-grid" style="margin-bottom:0.75rem;">
            <div class="field">
              <label>Tipo de simulação</label>
              <select class="input" id="sim-type">
                <option value="single_attendance">1 atendimento específico</option>
                <option value="period_attendances">Vários atendimentos no período</option>
                <option value="monthly_fixed">Mensal fixo</option>
                <option value="hybrid">Híbrido</option>
              </select>
            </div>
            <div class="field">
              <label>Mês de referência</label>
              <input class="input" id="sim-reference-month" type="month" />
              <div class="hint">Se não informar, será usado o mês atual.</div>
            </div>
          </div>

          <div class="form-grid" style="margin-bottom:0.75rem;">
            <div class="field">
              <label>Quantidade de atendimentos</label>
              <input class="input" id="sim-attendances" type="number" min="0" step="1" placeholder="Ex: 42" />
              <div class="hint">Use para simulações por atendimento/período.</div>
            </div>
            <div class="field" style="display:flex;align-items:flex-end;">
              <button type="button" class="btn btn-ghost btn-sm" id="sim-toggle-advanced">Mostrar opções avançadas</button>
            </div>
          </div>

          <div id="sim-advanced" style="display:none;margin-bottom:0.75rem;border:1px dashed var(--border);border-radius:var(--r-sm);padding:0.75rem;">
            <div class="form-grid">
              <div class="field">
                <label>Valor manual para simulação (override)</label>
                <input class="input" id="sim-manual-base" type="number" min="0" step="0.01" placeholder="Opcional" />
              </div>
              <div class="field">
                <label>Justificativa do override</label>
                <input class="input" id="sim-manual-reason" maxlength="180" placeholder="Obrigatória quando usar valor manual" />
              </div>
            </div>
          </div>

          <button type="button" class="btn btn-primary btn-md" id="btn-simulate">Simular repasse</button>
          <div id="simulate-result" style="margin-top:0.75rem;"></div>
        </div>

      </div>`;

    this._selectedProfessional = null;
    this._selectedTable = null;
    initMasks();
    const monthInput = document.getElementById('sim-reference-month');
    if (monthInput) monthInput.value = new Date().toISOString().slice(0, 7);

    this._loadTables();
    this._mountEvents();
    await this._initProfessionalFromQuery();
  },

  _mountEvents() {
    document.getElementById('btn-search-pt')?.addEventListener('click', () => this._loadTables());
    document.getElementById('pt-search')?.addEventListener('keydown', (e) => { if (e.key === 'Enter') this._loadTables(); });
    document.getElementById('btn-search-prof')?.addEventListener('click', () => this._searchProfessionals());
    document.getElementById('prof-search-input')?.addEventListener('keydown', (e) => { if (e.key === 'Enter') this._searchProfessionals(); });
    document.getElementById('btn-clear-prof')?.addEventListener('click', () => this._clearProfessional());
    document.getElementById('sim-type')?.addEventListener('change', () => this._syncSimulationTypeUi());
    document.getElementById('sim-toggle-advanced')?.addEventListener('click', () => this._toggleAdvancedOptions());
    document.getElementById('btn-simulate')?.addEventListener('click', () => this._simulate());
    this._syncSimulationTypeUi();
  },

  async _loadTables() {
    const container = document.getElementById('tables-list');
    if (!container) return;
    const search = document.getElementById('pt-search')?.value || '';
    const res = await http.get(`/api/v1/payment-tables?search=${encodeURIComponent(search)}&status=active&per_page=10`);
    if (!res.success) {
      toast.error('Não foi possível carregar as tabelas ativas agora.');
      container.innerHTML = '<div class="empty-state"><div class="empty-state__title">Não foi possível carregar as tabelas ativas.</div></div>';
      return;
    }
    const items = res.data?.items || [];
    if (!items.length) { container.innerHTML = `<div class="empty-state"><div class="empty-state__title">Nenhuma tabela ativa</div></div>`; return; }

    container.innerHTML = items.map((t) => `
      <div class="card" style="margin-bottom:0.5rem;display:flex;align-items:center;justify-content:space-between;">
        <div>
          <div class="font-semibold text-sm">${t.name}</div>
          <div class="text-xs text-muted">${this._typeLabel(t.calculation_type)} · vigência: ${t.effective_start_date || '—'}</div>
        </div>
        <button class="btn btn-ghost btn-xs" data-table-uuid="${t.uuid}" data-table-name="${t.name}" data-table-type="${t.calculation_type || ''}" data-table-start="${t.effective_start_date || ''}" data-table-end="${t.effective_end_date || ''}" data-table-status="${t.status || 'active'}">Usar</button>
      </div>`).join('');

    container.querySelectorAll('[data-table-uuid]').forEach((btn) => {
      btn.addEventListener('click', () => this._selectTable({
        uuid: btn.dataset.tableUuid,
        name: btn.dataset.tableName,
        calculationType: btn.dataset.tableType,
        effectiveStartDate: btn.dataset.tableStart,
        effectiveEndDate: btn.dataset.tableEnd || null,
        status: btn.dataset.tableStatus || 'active',
      }));
    });
  },

  _selectTable(table) {
    this._selectedTable = table;
    const info = document.getElementById('table-selected-info');
    if (info) {
      info.innerHTML = `
        <div class="card card--active" style="display:flex;align-items:center;justify-content:space-between;padding:0.5rem 0.75rem;">
          <span class="font-semibold text-sm">${table.name}</span>
          <button class="btn btn-ghost btn-xs" id="btn-clear-table">Trocar</button>
        </div>`;
      document.getElementById('btn-clear-table')?.addEventListener('click', () => {
        this._selectedTable = null;
        info.innerHTML = `<div class="text-sm text-muted" style="padding:0.5rem 0;">Clique em "Usar" em uma tabela ao lado para selecioná-la.</div>`;
      });
    }
  },

  async _searchProfessionals() {
    const q = document.getElementById('prof-search-input')?.value.trim();
    if (!q) return;
    const res = await http.get(`/api/v1/professionals?search=${encodeURIComponent(q)}&status=active&per_page=8`);
    const resultsEl = document.getElementById('prof-search-results');
    if (!resultsEl) return;
    if (!res.success) {
      toast.error('Não foi possível buscar profissionais agora.');
      resultsEl.innerHTML = '<div class="text-sm text-muted">Não foi possível buscar profissionais agora.</div>';
      return;
    }
    const items = res.data?.items || [];
    if (!items.length) { resultsEl.innerHTML = `<div class="text-sm text-muted">Nenhum profissional encontrado.</div>`; return; }
    const uniqueItems = Array.from(new Map(items.map((p) => [p.uuid, p])).values());
    resultsEl.innerHTML = uniqueItems.map((p) => `
      <div class="card" style="margin-bottom:0.35rem;padding:0.5rem 0.75rem;display:flex;align-items:center;justify-content:space-between;cursor:pointer;" data-prof-uuid="${p.uuid}" data-prof-name="${p.full_name}">
        <div>
          <div class="font-semibold text-sm">${p.full_name}</div>
          <div class="text-xs text-muted">${p.main_specialty || p.professional_registry || 'Sem especialidade'}${p.email ? ` · ${p.email}` : ''}</div>
        </div>
        <button class="btn btn-ghost btn-xs" data-prof-uuid="${p.uuid}" data-prof-name="${p.full_name}">Selecionar</button>
      </div>`).join('');

    resultsEl.querySelectorAll('[data-prof-uuid]').forEach((btn) => {
      btn.addEventListener('click', () => this._selectProfessional(btn.dataset.profUuid, btn.dataset.profName));
    });
  },

  _selectProfessional(uuid, name) {
    this._selectedProfessional = { uuid, name };
    document.getElementById('prof-search-results').innerHTML = '';
    const sel = document.getElementById('prof-selected');
    const nameEl = document.getElementById('prof-selected-name');
    if (sel) sel.style.display = '';
    if (nameEl) nameEl.textContent = name;
    const input = document.getElementById('prof-search-input');
    if (input) input.value = '';
  },

  _clearProfessional() {
    this._selectedProfessional = null;
    const sel = document.getElementById('prof-selected');
    if (sel) sel.style.display = 'none';
  },

  async _initProfessionalFromQuery() {
    const params = new URLSearchParams(window.location.search);
    const professionalUuid = params.get('professional_uuid');
    const mode = params.get('mode');
    if (!professionalUuid) return;

    const res = await http.get(`/api/v1/professionals/${professionalUuid}`, { context: 'page' });
    if (!res.success || !res.data?.uuid) return;

    this._selectProfessional(res.data.uuid, res.data.full_name || 'Profissional');
    if (mode === 'config') {
      toast.info('Profissional pré-selecionado. A configuração de repasse deve ser feita no cadastro do profissional.');
      navigate(`/professionals/${res.data.uuid}`);
      return;
    }
    toast.info('Profissional pré-selecionado para simular repasse.');
  },

  _syncSimulationTypeUi() {
    const type = document.getElementById('sim-type')?.value || 'single_attendance';
    const attendances = document.getElementById('sim-attendances');
    if (!attendances) return;

    const needsQuantity = type === 'period_attendances' || type === 'hybrid';
    attendances.disabled = !needsQuantity;
    if (!needsQuantity) {
      attendances.value = '';
    }
  },

  _toggleAdvancedOptions() {
    const panel = document.getElementById('sim-advanced');
    const button = document.getElementById('sim-toggle-advanced');
    if (!panel || !button) return;

    const isHidden = panel.style.display === 'none';
    panel.style.display = isHidden ? '' : 'none';
    button.textContent = isHidden ? 'Ocultar opções avançadas' : 'Mostrar opções avançadas';
  },

  async _simulate() {
    const result = document.getElementById('simulate-result');
    if (!this._selectedProfessional) {
      toast.warning('Selecione um profissional para simular.');
      return;
    }

    const typeInput = document.getElementById('sim-type');
    const monthInput = document.getElementById('sim-reference-month');
    const attendancesInput = document.getElementById('sim-attendances');
    const manualBaseInput = document.getElementById('sim-manual-base');
    const manualReasonInput = document.getElementById('sim-manual-reason');
    const simulationType = typeInput?.value || 'single_attendance';
    const requestedReferenceMonth = monthInput?.value || new Date().toISOString().slice(0, 7);
    let referenceDateToSimulate = buildReferenceDateFromMonth(requestedReferenceMonth);
    const attendancesCount = Number(attendancesInput?.value || 0);
    const manualBaseAmount = manualBaseInput?.value ? Number(manualBaseInput.value) : null;
    const manualOverrideReason = String(manualReasonInput?.value || '').trim();

    if ((simulationType === 'period_attendances' || simulationType === 'hybrid') && attendancesCount < 0) {
      toast.warning('Informe uma quantidade de atendimentos válida.');
      return;
    }

    if (manualBaseAmount !== null && !manualOverrideReason) {
      toast.warning('Informe a justificativa para usar valor manual na simulação.');
      return;
    }
    let referenceMonthToSimulate = requestedReferenceMonth;
    let resolved = await http.get(
      `/api/v1/professionals/${this._selectedProfessional.uuid}/payment-rule?date=${encodeURIComponent(referenceDateToSimulate || (requestedReferenceMonth + '-01'))}`,
      { context: 'page' }
    );

    if (!resolved.success && resolved?.meta?.error_code === 'NOT_FOUND') {
      const fallbackConfig = await http.get(
        `/api/v1/professionals/${this._selectedProfessional.uuid}/payment-configs?status=active&per_page=1&sort=effective_start_date&direction=asc`,
        { context: 'page' }
      );
      const fallback = fallbackConfig.success ? (fallbackConfig.data?.items || [])[0] : null;

      if (!fallback?.effective_start_date) {
        toast.warning('Este profissional ainda não possui configuração de pagamento vigente para simulação.');
        result.innerHTML = `
          <div class="empty-state">
            <div class="empty-state__title">Configure o repasse do profissional antes de simular.</div>
            <div class="empty-state__desc">
              Abra o cadastro do profissional e defina uma regra vigente.
            </div>
            <div class="empty-state__action">
              <button type="button" class="btn btn-secondary btn-sm" id="btn-open-professional-config">Abrir profissional</button>
            </div>
          </div>`;

        document.getElementById('btn-open-professional-config')?.addEventListener('click', () => {
          navigate(`/professionals/${this._selectedProfessional.uuid}`);
        });
        return;
      }

      referenceMonthToSimulate = String(fallback.effective_start_date).slice(0, 7);
      referenceDateToSimulate = String(fallback.effective_start_date);
      if (monthInput) monthInput.value = referenceMonthToSimulate;
      toast.info(`Sem vigência em ${requestedReferenceMonth}. Simulando com a primeira vigência ativa (${referenceMonthToSimulate}).`);

      resolved = await http.get(
        `/api/v1/professionals/${this._selectedProfessional.uuid}/payment-rule?date=${encodeURIComponent(referenceDateToSimulate)}`,
        { context: 'page' }
      );
    }

    if (!resolved.success) {
      result.innerHTML = `
        <div class="empty-state">
          <div class="empty-state__title">Configure o repasse do profissional antes de simular.</div>
          <div class="empty-state__desc">
            Abra o cadastro do profissional e defina uma regra vigente para o período da simulação.
          </div>
          <div class="empty-state__action">
            <button type="button" class="btn btn-secondary btn-sm" id="btn-open-professional-config">Abrir profissional</button>
          </div>
        </div>`;

      document.getElementById('btn-open-professional-config')?.addEventListener('click', () => {
        navigate(`/professionals/${this._selectedProfessional.uuid}`);
      });
      return;
    }

    const payload = {
      simulation_type: simulationType,
      reference_month: referenceMonthToSimulate,
      reference_date: referenceDateToSimulate || undefined,
      attendances_count: attendancesCount,
      payment_table_uuid: this._selectedTable?.uuid || undefined,
      manual_base_amount: manualBaseAmount ?? undefined,
      manual_override_reason: manualBaseAmount !== null ? manualOverrideReason : undefined,
    };

    const res = await http.post(`/api/v1/professionals/${this._selectedProfessional.uuid}/simulate-payout`, payload);
    if (res.success) {
      toast.success('Simulação realizada com sucesso.');
      const d = res.data;
      const memory = Array.isArray(d.calculation_memory) ? d.calculation_memory : [];
      const tableName = d?.rule_resolution?.payment_table?.name || 'Tabela não aplicada';
      const mode = this._typeLabel(d.payment_mode);
      const manualOverrideLabel = d?.manual_override?.enabled ? formatCurrencyBRL(d?.manual_override?.manual_base_amount || 0) : 'Não';

      result.innerHTML = `
        <div class="card card--active">
          <div class="font-semibold" style="margin-bottom:0.5rem;">Resultado da simulação</div>
          <div class="text-xs text-muted" style="margin-bottom:0.5rem;">Profissional: ${this._selectedProfessional.name} · Tipo: ${simulationTypeLabel(d.simulation_type)}</div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.5rem;margin-bottom:0.5rem;">
            <div><div class="text-xs text-muted">Modo vigente</div><div class="font-semibold">${mode}</div></div>
            <div><div class="text-xs text-muted">Tabela usada</div><div class="font-semibold">${tableName}</div></div>
            <div><div class="text-xs text-muted">Valor manual</div><div class="font-semibold">${manualOverrideLabel}</div></div>
            <div><div class="text-xs text-muted">Repasse estimado</div><div class="font-semibold">${formatCurrencyBRL(d.total_amount || 0)}</div></div>
          </div>
          <div class="text-xs text-muted" style="margin-top:0.4rem;margin-bottom:0.4rem;">Memória de cálculo:</div>
          <ul style="margin:0;padding-left:1rem;display:grid;gap:0.22rem;">
            ${(memory.length ? memory : ['Não há memória detalhada para este cenário.']).map((line) => `<li class="text-sm">${line}</li>`).join('')}
          </ul>
          <div class="text-xs text-muted" style="margin-top:0.6rem;">Esta simulação não gera financeiro real e não altera contrato/tabela.</div>
        </div>`;
    } else {
      if (res?.meta?.error_code === 'NOT_FOUND') {
        toast.warning('Este profissional ainda não possui configuração de pagamento vigente para o período informado.');
        result.innerHTML = `
          <div class="empty-state">
            <div class="empty-state__title">Configure o repasse deste profissional antes de simular.</div>
          </div>`;
        return;
      }

      toast.error(paymentConfigMessage(res, 'Não foi possível concluir a simulação agora.'));
    }
  },

  _typeLabel(type) {
    const map = {
      fixed_per_attendance: 'Fixo por atendimento',
      fixed_monthly: 'Fixo mensal',
      hybrid: 'Híbrido',
      custom: 'Customizado',
    };
    return map[type] || type || 'Não informado';
  },

  unmount() {
    this._selectedProfessional = null;
    this._selectedTable = null;
  },
};
