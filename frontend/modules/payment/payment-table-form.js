import { http } from '../../core/services/http.js';
import { navigate } from '../../core/router/router.js';
import { toast } from '../../core/js/toast.js';
import { initMasks } from '../../core/js/masks.js';

function parseMoney(value) {
  const normalized = String(value || '').replace(/[^\d,.-]/g, '').replace(/\./g, '').replace(',', '.');
  if (normalized.trim() === '') return null;
  const num = Number(normalized);
  return Number.isNaN(num) ? null : num;
}

function formatMoney(value) {
  if (value === null || value === undefined || value === '') return '';
  const num = Number(value);
  if (Number.isNaN(num)) return '';
  return num.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
}

function paymentMessage(message) {
  const raw = String(message || '');
  if (raw.includes('calculation_type')) return 'Tipo de cálculo inválido. Selecione uma opção válida.';
  if (raw.includes('effective_start_date')) return 'Informe a data de início da vigência.';
  if (raw.includes('name')) return 'Informe o nome da tabela de pagamento.';
  return 'Não foi possível salvar a tabela. Revise os dados e tente novamente.';
}

function paymentErrorFromResponse(res) {
  const field = res?.errors?.[0]?.field || '';
  if (field === 'name') return 'Informe o nome da tabela de pagamento.';
  if (field === 'calculation_type') return 'Selecione um tipo de cálculo válido.';
  if (field === 'effective_start_date') return 'Informe a data de início da vigência.';
  if (field === 'status') return 'Status da tabela inválido.';
  return paymentMessage(res?.message);
}

export default {
  async mount(container, params) {
    const uuid = params?.uuid;
    const isEdit = !!uuid;

    container.innerHTML = `
      <div class="page-header">
        <div class="page-header__info">
          <h1>${isEdit ? 'Editar tabela' : 'Nova tabela de pagamento'}</h1>
        </div>
        <div class="page-header__actions">
          <button class="btn btn-secondary btn-md" id="btn-back-pt">← Voltar</button>
        </div>
      </div>

      <div class="section">
        <form id="pt-form">
          <input type="hidden" name="uuid" value="${uuid || ''}" />

          <div class="form-group">
            <div class="form-group__title">Identificação</div>
            <div class="form-grid">
              <div class="field" style="grid-column:1/-1;">
                <label>Nome da tabela <span style="color:var(--error);">*</span></label>
                <input class="input" name="name" required placeholder="Ex: Tabela Padrão 2025" />
              </div>
              <div class="field">
                <label>Tipo de cálculo</label>
                <select class="input" name="calculation_type">
                  <option value="fixed_per_attendance">Fixo por atendimento</option>
                  <option value="fixed_monthly">Fixo mensal</option>
                  <option value="hybrid">Híbrido</option>
                  <option value="custom">Customizado</option>
                </select>
              </div>
              <div class="field">
                <label>Status</label>
                <select class="input" name="status">
                  <option value="active">Ativa</option>
                  <option value="inactive">Inativa</option>
                </select>
              </div>
            </div>
          </div>

          <div class="form-group">
            <div class="form-group__title">Vigência</div>
            <div class="form-grid">
              <div class="field">
                <label>Início da vigência</label>
                <input class="input" name="effective_start_date" type="date" />
              </div>
              <div class="field">
                <label>Fim da vigência</label>
                <input class="input" name="effective_end_date" type="date" />
                <div class="hint">Deixe em branco para vigência aberta</div>
              </div>
            </div>
          </div>

          <div class="form-group">
            <div class="form-group__title">Regras padrão</div>
            <div class="form-grid">
              <div class="field">
                <label>Percentual padrão (%)</label>
                <input class="input" name="default_percentage" type="number" min="0" max="100" step="0.01" placeholder="Ex: 50.00" />
              </div>
              <div class="field">
                <label>Valor fixo padrão (R$)</label>
                <input class="input" name="default_fixed_amount" data-mask="money" placeholder="R$ 0,00" />
              </div>
            </div>
            <div class="field" style="margin-top:0.75rem;">
              <label>Observações</label>
              <textarea class="input" name="notes" placeholder="Observações sobre esta tabela…"></textarea>
            </div>
          </div>
        </form>

        <div style="margin-top:1rem;display:flex;gap:0.5rem;">
          <button class="btn btn-primary btn-md" id="btn-save-pt">${isEdit ? 'Salvar alterações' : 'Criar tabela'}</button>
          <button class="btn btn-secondary btn-md" id="btn-cancel-pt">Cancelar</button>
          ${isEdit ? `<button class="btn btn-danger btn-md" id="btn-delete-pt" style="margin-left:auto;">Excluir</button>` : ''}
        </div>
      </div>`;

    initMasks();
    if (isEdit) await this._load(uuid);

    document.getElementById('btn-back-pt')?.addEventListener('click', () => navigate('/payment-tables'));
    document.getElementById('btn-cancel-pt')?.addEventListener('click', () => navigate('/payment-tables'));
    document.getElementById('btn-save-pt')?.addEventListener('click', () => this._save(isEdit, uuid));
    document.getElementById('btn-delete-pt')?.addEventListener('click', () => this._delete(uuid));
  },

  async _load(uuid) {
    const res = await http.get(`/api/v1/payment-tables/${uuid}`);
    if (!res.success) return;
    const form = document.getElementById('pt-form');
    const data = { ...(res.data || {}) };
    if (typeof data.description === 'string') {
      data.notes = data.description;
    }

    Object.entries(data).forEach(([k, v]) => {
      const el = form?.querySelector(`[name="${k}"]`);
      if (el && v != null) {
        if (k === 'default_fixed_amount') {
          el.value = formatMoney(v);
        } else {
          el.value = v;
        }
      }
    });
  },

  async _save(isEdit, uuid) {
    const form = document.getElementById('pt-form');
    const btn = document.getElementById('btn-save-pt');
    const raw = Object.fromEntries(new FormData(form));
    const data = {
      name: raw.name,
      calculation_type: raw.calculation_type,
      status: raw.status || 'active',
      default_percentage: raw.default_percentage || null,
      default_fixed_amount: parseMoney(raw.default_fixed_amount),
      effective_start_date: raw.effective_start_date,
      effective_end_date: raw.effective_end_date || null,
      description: raw.notes || null,
    };
    Object.keys(data).forEach((k) => {
      if (data[k] === '' || data[k] === undefined) {
        delete data[k];
      }
    });

    btn.disabled = true; btn.textContent = 'Salvando…';
    const res = isEdit
      ? await http.put(`/api/v1/payment-tables/${uuid}`, data)
      : await http.post('/api/v1/payment-tables', data);
    btn.disabled = false; btn.textContent = isEdit ? 'Salvar alterações' : 'Criar tabela';

    if (res.success) {
      toast.success('Tabela salva com sucesso.');
      if (!isEdit && res.data?.uuid) setTimeout(() => navigate(`/payment-tables/${res.data.uuid}`), 800);
    } else {
      toast.error(paymentErrorFromResponse(res));
    }
  },

  async _delete(uuid) {
    if (!confirm('Excluir esta tabela de pagamento?')) return;
    const res = await http.delete(`/api/v1/payment-tables/${uuid}`);
    if (res.success) {
      toast.success('Tabela removida com sucesso.');
      navigate('/payment-tables');
      return;
    }

    toast.error(paymentErrorFromResponse(res));
  },

  unmount() {},
};
