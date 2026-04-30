import { http } from '../../core/services/http.js';
import { navigate } from '../../core/router/router.js';
import { initMasks } from '../../core/js/masks.js';
import { toast } from '../../core/js/toast.js';

function supplierMessage(res, fallback) {
  const field = res?.errors?.[0]?.field || '';

  if (field === 'name_or_legal_name') return 'Informe o nome ou razão social do fornecedor.';
  if (field === 'document') return 'CPF/CNPJ inválido ou já cadastrado para outro fornecedor.';
  if (field === 'email') return 'Informe um e-mail válido.';
  if (field === 'phone') return 'Informe um telefone válido.';

  return fallback;
}

export default {
  async mount(container, params) {
    const uuid = params?.uuid;
    const isEdit = !!uuid;

    container.innerHTML = `
      <div class="page-header">
        <div class="page-header__info">
          <h1>${isEdit ? 'Editar fornecedor' : 'Novo fornecedor'}</h1>
        </div>
        <div class="page-header__actions">
          <button class="btn btn-secondary btn-md" id="btn-back-sup">← Voltar</button>
        </div>
      </div>

      <div class="section">
        <form id="sup-form">
          <input type="hidden" name="uuid" value="${uuid || ''}" />

          <div class="form-group">
            <div class="form-group__title">Identificação</div>
            <div class="form-grid">
              <div class="field" style="grid-column:1/-1;">
                <label>Razão social / Nome <span style="color:var(--error);">*</span></label>
                <input class="input" name="name_or_legal_name" placeholder="Razão social ou nome" required />
              </div>
              <div class="field">
                <label>Categoria</label>
                <select class="input" name="category">
                  <option value="">Selecione</option>
                  <option value="pj">Pessoa Jurídica</option>
                  <option value="pf">Pessoa Física</option>
                  <option value="outro">Outro</option>
                </select>
              </div>
              <div class="field">
                <label>CNPJ / CPF</label>
                <input class="input" name="document" placeholder="00.000.000/0000-00" />
              </div>
              <div class="field">
                <label>Nome do contato</label>
                <input class="input" name="contact_name" placeholder="Pessoa de contato" />
              </div>
            </div>
          </div>

          <div class="form-group">
            <div class="form-group__title">Contato</div>
            <div class="form-grid">
              <div class="field">
                <label>E-mail</label>
                <input class="input" name="email" type="email" placeholder="contato@fornecedor.com" />
              </div>
              <div class="field">
                <label>Telefone</label>
                <input class="input" name="phone" data-mask="phone" placeholder="(00) 0000-0000" />
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
                <input class="input" name="address_street" placeholder="Rua, Av." />
              </div>
              <div class="field">
                <label>Número</label>
                <input class="input" name="address_number" />
              </div>
              <div class="field">
                <label>Cidade</label>
                <input class="input" name="address_city" />
              </div>
              <div class="field">
                <label>Estado</label>
                <input class="input" name="address_state" maxlength="2" style="text-transform:uppercase;" />
              </div>
            </div>
          </div>
        </form>

        <div style="margin-top:1rem;display:flex;gap:0.5rem;">
          <button class="btn btn-primary btn-md" id="btn-save-sup">${isEdit ? 'Salvar alterações' : 'Cadastrar fornecedor'}</button>
          <button class="btn btn-secondary btn-md" id="btn-cancel-sup">Cancelar</button>
          ${isEdit ? `<button class="btn btn-danger btn-md" id="btn-delete-sup" style="margin-left:auto;">Excluir</button>` : ''}
        </div>
      </div>`;

    initMasks();
    if (isEdit) await this._load(uuid);

    document.getElementById('btn-back-sup')?.addEventListener('click', () => navigate('/suppliers'));
    document.getElementById('btn-cancel-sup')?.addEventListener('click', () => navigate('/suppliers'));
    document.getElementById('btn-save-sup')?.addEventListener('click', () => this._save(isEdit, uuid));
    document.getElementById('btn-delete-sup')?.addEventListener('click', () => this._delete(uuid));
  },

  async _load(uuid) {
    const res = await http.get(`/api/v1/suppliers/${uuid}`);
    if (!res.success) return;
    const form = document.getElementById('sup-form');
    Object.entries(res.data || {}).forEach(([k, v]) => {
      const el = form?.querySelector(`[name="${k}"]`);
      if (el && v != null) el.value = v;
    });
  },

  async _save(isEdit, uuid) {
    const form = document.getElementById('sup-form');
    const btn = document.getElementById('btn-save-sup');
    const data = Object.fromEntries(new FormData(form));
    if (!data.uuid) delete data.uuid;
    Object.keys(data).forEach(k => { if (data[k] === '') delete data[k]; });

    btn.disabled = true; btn.textContent = 'Salvando…';
    const res = isEdit
      ? await http.put(`/api/v1/suppliers/${uuid}`, data)
      : await http.post('/api/v1/suppliers', data);
    btn.disabled = false; btn.textContent = isEdit ? 'Salvar alterações' : 'Cadastrar fornecedor';

    if (res.success) {
      toast.success('Fornecedor salvo com sucesso.');
      if (!isEdit && res.data?.uuid) setTimeout(() => navigate(`/suppliers/${res.data.uuid}`), 800);
    } else {
      toast.error(supplierMessage(res, 'Não foi possível salvar o fornecedor. Revise os dados e tente novamente.'));
    }
  },

  async _delete(uuid) {
    if (!confirm('Excluir este fornecedor?')) return;
    const res = await http.delete(`/api/v1/suppliers/${uuid}`);
    if (res.success) {
      toast.success('Fornecedor excluído com sucesso.');
      navigate('/suppliers');
      return;
    }

    toast.error(supplierMessage(res, 'Não foi possível excluir o fornecedor agora.'));
  },

  unmount() {},
};
