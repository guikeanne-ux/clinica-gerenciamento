import { http } from '../../core/services/http.js';
import { navigate } from '../../core/router/router.js';
import { initMasks } from '../../core/js/masks.js';
import { toast } from '../../core/js/toast.js';
import { applyFieldErrors, clearFieldErrors } from '../../core/components/form-errors.js';

export default {
  async mount(container, params) {
    const uuid = params?.uuid;
    const isEdit = !!uuid;

    container.innerHTML = `
      <div class="page-header">
        <div class="page-header__info">
          <h1>${isEdit ? 'Editar paciente' : 'Novo paciente'}</h1>
          <p class="subtitle">${isEdit ? 'Atualize os dados do paciente' : 'Preencha os dados do novo paciente'}</p>
        </div>
        <div class="page-header__actions">
          <button class="btn btn-secondary btn-md" id="btn-back">← Voltar</button>
        </div>
      </div>

      <div class="section">
        <form id="patient-form">
          <input type="hidden" name="uuid" value="${uuid || ''}" />

          <div class="form-group">
            <div class="form-group__title">Identificação</div>
            <div class="form-grid">
              <div class="field" style="grid-column:1/-1;">
                <label>Nome completo <span style="color:var(--error);">*</span></label>
                <input class="input" name="full_name" required placeholder="Nome completo" />
              </div>
              <div class="field">
                <label>Data de nascimento <span style="color:var(--error);">*</span></label>
                <input class="input" name="birth_date" type="date" required />
              </div>
              <div class="field">
                <label>CPF</label>
                <input class="input" name="cpf" data-mask="cpf" placeholder="000.000.000-00" />
              </div>
              <div class="field">
                <label>Nome social</label>
                <input class="input" name="social_name" placeholder="Nome social" />
              </div>
              <div class="field">
                <label>Gênero</label>
                <select class="input" name="gender">
                  <option value="">Não informado</option>
                  <option value="male">Masculino</option>
                  <option value="female">Feminino</option>
                  <option value="other">Outro</option>
                </select>
              </div>
              <div class="field">
                <label>RG</label>
                <input class="input" name="rg" placeholder="RG" />
              </div>
              <div class="field">
                <label>CNS</label>
                <input class="input" name="cns" placeholder="CNS" />
              </div>
              <div class="field">
                <label>CID</label>
                <input class="input" name="cid" placeholder="CID" />
              </div>
              <div class="field">
                <label>Origem</label>
                <input class="input" name="origin" placeholder="Origem do cadastro/encaminhamento" />
              </div>
            </div>
          </div>

          <div class="form-group">
            <div class="form-group__title">Contato</div>
            <div class="form-grid">
              <div class="field">
                <label>E-mail</label>
                <input class="input" name="email" type="email" placeholder="email@exemplo.com" />
              </div>
              <div class="field">
                <label>Telefone principal</label>
                <input class="input" name="phone_primary" data-mask="phone" placeholder="(00) 00000-0000" />
              </div>
              <div class="field">
                <label>Telefone secundário</label>
                <input class="input" name="phone_secondary" data-mask="phone" placeholder="(00) 00000-0000" />
              </div>
              <div class="field">
                <label>Nome do pai</label>
                <input class="input" name="father_name" placeholder="Nome do pai" />
              </div>
              <div class="field">
                <label>Nome da mãe</label>
                <input class="input" name="mother_name" placeholder="Nome da mãe" />
              </div>
            </div>
          </div>

          <div class="form-group">
            <div class="form-group__title">Endereço</div>
            <div class="form-grid">
              <div class="field">
                <label>CEP</label>
                <input class="input" name="address_zipcode" data-mask="cep" placeholder="00000-000" />
              </div>
              <div class="field">
                <label>Logradouro</label>
                <input class="input" name="address_street" placeholder="Rua, Avenida…" />
              </div>
              <div class="field">
                <label>Número</label>
                <input class="input" name="address_number" placeholder="Nº" />
              </div>
              <div class="field">
                <label>Complemento</label>
                <input class="input" name="address_complement" placeholder="Apto, sala…" />
              </div>
              <div class="field">
                <label>Bairro</label>
                <input class="input" name="address_district" placeholder="Bairro" />
              </div>
              <div class="field">
                <label>Cidade</label>
                <input class="input" name="address_city" placeholder="Cidade" />
              </div>
              <div class="field">
                <label>Estado</label>
                <input class="input" name="address_state" placeholder="UF" maxlength="2" style="text-transform:uppercase;" />
              </div>
            </div>
          </div>

          <div class="form-group">
            <div class="form-group__title">Informações clínicas</div>
            <div class="form-grid">
              <div class="field">
                <label>Convênio</label>
                <input class="input" name="health_plan_name" placeholder="Nome do convênio" />
              </div>
              <div class="field">
                <label>Número da carteirinha</label>
                <input class="input" name="health_plan_card_number" placeholder="Número" />
              </div>
              <div class="field">
                <label>Status</label>
                <select class="input" name="status">
                  <option value="active">Ativo</option>
                  <option value="inactive">Inativo</option>
                </select>
              </div>
            </div>
            <div class="field" style="margin-top:0.75rem;">
              <label>Observações</label>
              <textarea class="input" name="general_notes" placeholder="Observações gerais sobre o paciente…"></textarea>
            </div>
          </div>
        </form>

        <div id="patient-feedback" style="margin-top:0.75rem;"></div>

        <div style="margin-top:1rem;display:flex;gap:0.5rem;">
          <button class="btn btn-primary btn-md" id="btn-save-patient">
            ${isEdit ? 'Salvar alterações' : 'Cadastrar paciente'}
          </button>
          <button class="btn btn-secondary btn-md" id="btn-cancel-patient">Cancelar</button>
          ${isEdit ? `<button class="btn btn-danger btn-md" id="btn-delete-patient" style="margin-left:auto;">Excluir paciente</button>` : ''}
        </div>
      </div>`;

    initMasks();

    if (isEdit) await this._load(uuid);

    const backTarget = isEdit ? `/patients/${uuid}` : '/patients';
    document.getElementById('btn-back')?.addEventListener('click', () => navigate(backTarget));
    document.getElementById('btn-cancel-patient')?.addEventListener('click', () => navigate(backTarget));
    document.getElementById('btn-save-patient')?.addEventListener('click', () => this._save(isEdit, uuid));
    document.getElementById('btn-delete-patient')?.addEventListener('click', () => this._delete(uuid));
  },

  async _load(uuid) {
    const res = await http.get(`/api/v1/patients/${uuid}`);
    if (!res.success) return;
    const form = document.getElementById('patient-form');
    Object.entries(res.data || {}).forEach(([k, v]) => {
      const el = form?.querySelector(`[name="${k}"]`);
      if (el && v != null) el.value = v;
    });
  },

  async _save(isEdit, uuid) {
    const form = document.getElementById('patient-form');
    const btn = document.getElementById('btn-save-patient');
    const data = Object.fromEntries(new FormData(form));
    if (!data.uuid) delete data.uuid;
    Object.keys(data).forEach(k => { if (data[k] === '') delete data[k]; });

    btn.disabled = true;
    btn.textContent = 'Salvando…';

    const res = isEdit
      ? await http.put(`/api/v1/patients/${uuid}`, data)
      : await http.post('/api/v1/patients', data);

    btn.disabled = false;
    btn.textContent = isEdit ? 'Salvar alterações' : 'Cadastrar paciente';
    clearFieldErrors(form);

    if (res.success) {
      toast.success('Paciente salvo com sucesso.');
      if (!isEdit && res.data?.uuid) {
        setTimeout(() => navigate(`/patients/${res.data.uuid}`), 800);
      }
    } else {
      applyFieldErrors(form, res.errors || []);
    }
  },

  async _delete(uuid) {
    if (!confirm('Tem certeza que deseja excluir este paciente? Esta ação não pode ser desfeita.')) return;
    const res = await http.delete(`/api/v1/patients/${uuid}`);
    if (res.success) {
      toast.success('Paciente removido com sucesso.');
      navigate('/patients');
    }
  },

  unmount() {},
};
