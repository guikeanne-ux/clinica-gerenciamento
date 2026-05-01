import { http } from '../../core/services/http.js';
import { permissionService } from '../../core/auth/permission-service.js';
import { toast } from '../../core/js/toast.js';

const CATEGORY_LABELS = {
  atendimento: 'Atendimento',
  reuniao: 'Reunião',
  bloqueio: 'Bloqueio',
  ferias: 'Férias',
  feriado: 'Feriado',
  evento_interno: 'Evento Interno',
  lembrete: 'Lembrete',
  outro: 'Outro',
};

const CATEGORIES = Object.entries(CATEGORY_LABELS);

function escapeHtml(value) {
  return String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

function isHexColor(value) {
  return /^#[0-9A-Fa-f]{6}$/.test(String(value || '').trim());
}

function colorSwatch(color) {
  const safe = isHexColor(color) ? color : '#cccccc';
  return `<span style="display:inline-block;width:14px;height:14px;border-radius:3px;background:${safe};border:1px solid rgba(0,0,0,.15);vertical-align:middle;margin-right:4px;" title="${escapeHtml(safe)}"></span>`;
}

let _formMode = null;
let _editingUuid = null;
let _container = null;

function _canCreate() { return permissionService.has('schedule.event_types.create'); }
function _canUpdate() { return permissionService.has('schedule.event_types.update'); }
function _canDelete() { return permissionService.has('schedule.event_types.delete'); }

function _renderFormHtml(data = {}) {
  const isEdit = _formMode === 'edit';
  const val = (field, fallback = '') => escapeHtml(String(data[field] ?? fallback));
  const checked = (field, defaultVal = false) => (data[field] ?? defaultVal) ? 'checked' : '';

  return `
    <div class="section" id="apt-form-section">
      <div class="section__header">
        <h2>${isEdit ? 'Editar tipo de compromisso' : 'Novo tipo de compromisso'}</h2>
      </div>
      <form id="apt-form">
        <div class="form-grid">
          <div class="field" style="grid-column:1/-1;">
            <label>Nome <span style="color:var(--error);">*</span></label>
            <input class="input" id="apt-name" name="name" placeholder="Ex: Consulta, Reunião de equipe…" value="${val('name')}" required />
            <div class="field-error" id="apt-err-name" style="display:none;"></div>
          </div>
          <div class="field">
            <label>Categoria <span style="color:var(--error);">*</span></label>
            <select class="input" id="apt-category" name="category">
              <option value="">Selecione</option>
              ${CATEGORIES.map(([v, l]) => `<option value="${v}" ${val('category') === v ? 'selected' : ''}>${l}</option>`).join('')}
            </select>
            <div class="field-error" id="apt-err-category" style="display:none;"></div>
          </div>
          <div class="field">
            <label>Status</label>
            <select class="input" id="apt-status" name="status">
              <option value="ativo" ${val('status', 'ativo') === 'ativo' ? 'selected' : ''}>Ativo</option>
              <option value="inativo" ${val('status') === 'inativo' ? 'selected' : ''}>Inativo</option>
            </select>
          </div>
          <div class="field">
            <label>Cor de identificação</label>
            <div style="display:flex;align-items:center;gap:0.5rem;">
              <input type="color" id="apt-color-picker" value="${isHexColor(data.color) ? data.color : '#157470'}" style="width:38px;height:38px;padding:2px;border:1px solid var(--border);border-radius:var(--r-sm);cursor:pointer;" />
              <input class="input" id="apt-color" name="color" placeholder="#157470" value="${val('color')}" maxlength="7" style="flex:1;" />
            </div>
            <div class="hint">Cor exibida nos compromissos desta categoria na agenda.</div>
            <div class="field-error" id="apt-err-color" style="display:none;"></div>
          </div>
          <div class="field" style="grid-column:1/-1;">
            <label>Descrição</label>
            <textarea class="input" id="apt-description" name="description" placeholder="Descrição opcional…" rows="2">${val('description')}</textarea>
          </div>
        </div>

        <div class="form-group" style="margin-top:1rem;">
          <div class="form-group__title">Configurações de comportamento</div>
          <div style="display:flex;flex-wrap:wrap;gap:1rem;margin-top:0.5rem;">
            <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer;">
              <input type="checkbox" id="apt-requires-patient" name="requires_patient" value="1" ${checked('requires_patient')} />
              Exige paciente
            </label>
            <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer;">
              <input type="checkbox" id="apt-requires-professional" name="requires_professional" value="1" ${checked('requires_professional')} />
              Exige profissional
            </label>
            <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer;">
              <input type="checkbox" id="apt-can-attendance" name="can_generate_attendance" value="1" ${checked('can_generate_attendance')} />
              Pode gerar atendimento
            </label>
            <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer;">
              <input type="checkbox" id="apt-can-financial" name="can_generate_financial_entry" value="1" ${checked('can_generate_financial_entry')} />
              Pode gerar lançamento financeiro
            </label>
          </div>
        </div>

        <div style="margin-top:1rem;display:flex;gap:0.5rem;">
          <button type="submit" class="btn btn-primary btn-md" id="apt-btn-save">
            ${isEdit ? 'Salvar alterações' : 'Cadastrar'}
          </button>
          <button type="button" class="btn btn-secondary btn-md" id="apt-btn-cancel">Cancelar</button>
        </div>
      </form>
    </div>`;
}

function _bindFormEvents() {
  document.getElementById('apt-color-picker')?.addEventListener('input', (e) => {
    const colorInput = document.getElementById('apt-color');
    if (colorInput) colorInput.value = e.target.value;
  });

  document.getElementById('apt-color')?.addEventListener('input', (e) => {
    const picker = document.getElementById('apt-color-picker');
    if (picker && isHexColor(e.target.value)) picker.value = e.target.value;
  });

  document.getElementById('apt-form')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    await _save();
  });

  document.getElementById('apt-btn-cancel')?.addEventListener('click', () => {
    _hideForm();
  });
}

function _clearErrors() {
  ['apt-err-name', 'apt-err-category', 'apt-err-color'].forEach((id) => {
    const el = document.getElementById(id);
    if (el) { el.textContent = ''; el.style.display = 'none'; }
  });
}

function _showError(fieldId, message) {
  const el = document.getElementById(fieldId);
  if (!el) return;
  el.textContent = message;
  el.style.display = '';
}

function _showForm(mode, data = {}) {
  _formMode = mode;
  _editingUuid = data.uuid || null;

  const slot = document.getElementById('apt-form-slot');
  if (!slot) return;

  slot.innerHTML = _renderFormHtml(data);
  _bindFormEvents();
  document.getElementById('apt-name')?.focus();

  document.getElementById('apt-btn-new')?.setAttribute('disabled', 'true');
}

function _hideForm() {
  _formMode = null;
  _editingUuid = null;

  const slot = document.getElementById('apt-form-slot');
  if (slot) slot.innerHTML = '';

  document.getElementById('apt-btn-new')?.removeAttribute('disabled');
}

async function _save() {
  _clearErrors();

  const name = document.getElementById('apt-name')?.value.trim() || '';
  const category = document.getElementById('apt-category')?.value || '';
  const status = document.getElementById('apt-status')?.value || 'ativo';
  const colorRaw = document.getElementById('apt-color')?.value.trim() || '';
  const description = document.getElementById('apt-description')?.value.trim() || '';
  const requiresPatient = document.getElementById('apt-requires-patient')?.checked ?? false;
  const requiresProfessional = document.getElementById('apt-requires-professional')?.checked ?? false;
  const canAttendance = document.getElementById('apt-can-attendance')?.checked ?? false;
  const canFinancial = document.getElementById('apt-can-financial')?.checked ?? false;

  let valid = true;

  if (!name) {
    _showError('apt-err-name', 'Nome é obrigatório.');
    valid = false;
  }

  if (!category) {
    _showError('apt-err-category', 'Categoria é obrigatória.');
    valid = false;
  }

  if (colorRaw && !isHexColor(colorRaw)) {
    _showError('apt-err-color', 'Cor deve ser um hexadecimal válido, ex: #FF5733.');
    valid = false;
  }

  if (!valid) return;

  const payload = {
    name,
    category,
    status,
    description: description || null,
    color: isHexColor(colorRaw) ? colorRaw : null,
    requires_patient: requiresPatient,
    requires_professional: requiresProfessional,
    can_generate_attendance: canAttendance,
    can_generate_financial_entry: canFinancial,
  };

  const btn = document.getElementById('apt-btn-save');
  if (btn) { btn.disabled = true; btn.textContent = 'Salvando…'; }

  const res = _editingUuid
    ? await http.put(`/api/v1/schedule/event-types/${_editingUuid}`, payload)
    : await http.post('/api/v1/schedule/event-types', payload);

  if (btn) {
    btn.disabled = false;
    btn.textContent = _formMode === 'edit' ? 'Salvar alterações' : 'Cadastrar';
  }

  if (!res.success) {
    const errors = Array.isArray(res.errors) ? res.errors : [];
    errors.forEach(({ field, message }) => {
      if (field === 'name') _showError('apt-err-name', message);
      else if (field === 'category') _showError('apt-err-category', message);
      else if (field === 'color') _showError('apt-err-color', message);
    });

    if (!errors.length) {
      toast.error(res.message || 'Não foi possível salvar o tipo de compromisso.');
    }
    return;
  }

  toast.success(_editingUuid ? 'Tipo atualizado com sucesso.' : 'Tipo cadastrado com sucesso.');
  _hideForm();
  await _load();
}

async function _load() {
  const list = document.getElementById('apt-list');
  if (!list) return;

  list.innerHTML = `<div class="skeleton" style="height:80px;border-radius:var(--r-sm);"></div>`;

  const res = await http.get('/api/v1/schedule/event-types?per_page=100&sort=name&direction=asc');

  if (!res.success) {
    list.innerHTML = `<div class="empty-state"><div class="empty-state__title">Não foi possível carregar os tipos de compromisso.</div></div>`;
    toast.error(res.message || 'Erro ao carregar tipos de compromisso.');
    return;
  }

  const items = Array.isArray(res.data?.items) ? res.data.items : (Array.isArray(res.data) ? res.data : []);

  if (!items.length) {
    list.innerHTML = `
      <div class="empty-state">
        <div class="empty-state__title">Nenhum tipo cadastrado</div>
        <div class="empty-state__desc">Cadastre o primeiro tipo de compromisso para usar na agenda.</div>
      </div>`;
    return;
  }

  const canEdit = _canUpdate();
  const canDel = _canDelete();

  list.innerHTML = `
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Nome</th>
            <th>Categoria</th>
            <th>Cor</th>
            <th>Status</th>
            <th style="width:120px;"></th>
          </tr>
        </thead>
        <tbody>
          ${items.map((item) => {
            const statusBadge = item.status === 'ativo'
              ? `<span class="badge badge-success">Ativo</span>`
              : `<span class="badge badge-neutral">Inativo</span>`;

            return `
              <tr>
                <td>
                  <strong>${escapeHtml(item.name)}</strong>
                  ${item.description ? `<div class="text-sm text-muted" style="margin-top:2px;">${escapeHtml(item.description)}</div>` : ''}
                  <div style="display:flex;flex-wrap:wrap;gap:4px;margin-top:4px;">
                    ${item.requires_patient ? `<span class="badge badge-info" style="font-size:0.68rem;">Exige paciente</span>` : ''}
                    ${item.requires_professional ? `<span class="badge badge-info" style="font-size:0.68rem;">Exige profissional</span>` : ''}
                    ${item.can_generate_attendance ? `<span class="badge badge-primary" style="font-size:0.68rem;">Gera atendimento</span>` : ''}
                    ${item.can_generate_financial_entry ? `<span class="badge badge-primary" style="font-size:0.68rem;">Gera financeiro</span>` : ''}
                  </div>
                </td>
                <td>${escapeHtml(CATEGORY_LABELS[item.category] || item.category)}</td>
                <td>${colorSwatch(item.color)}${escapeHtml(item.color || '—')}</td>
                <td>${statusBadge}</td>
                <td style="text-align:right;">
                  <div style="display:flex;gap:0.4rem;justify-content:flex-end;">
                    ${canEdit ? `<button class="btn btn-ghost btn-xs apt-btn-edit" data-uuid="${escapeHtml(item.uuid)}">Editar</button>` : ''}
                    ${canDel ? `<button class="btn btn-danger btn-xs apt-btn-delete" data-uuid="${escapeHtml(item.uuid)}" data-name="${escapeHtml(item.name)}">Excluir</button>` : ''}
                  </div>
                </td>
              </tr>`;
          }).join('')}
        </tbody>
      </table>
    </div>`;

  if (canEdit) {
    list.querySelectorAll('.apt-btn-edit').forEach((btn) => {
      btn.addEventListener('click', async () => {
        const uuid = btn.dataset.uuid;
        const getRes = await http.get(`/api/v1/schedule/event-types/${uuid}`);
        if (!getRes.success) {
          toast.error('Não foi possível carregar os dados para edição.');
          return;
        }
        _showForm('edit', getRes.data);
        document.getElementById('apt-form-slot')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
      });
    });
  }

  if (canDel) {
    list.querySelectorAll('.apt-btn-delete').forEach((btn) => {
      btn.addEventListener('click', async () => {
        const uuid = btn.dataset.uuid;
        const name = btn.dataset.name;
        if (!confirm(`Excluir o tipo "${name}"? Esta ação não pode ser desfeita.`)) return;
        await _delete(uuid);
      });
    });
  }
}

async function _delete(uuid) {
  const res = await http.delete(`/api/v1/schedule/event-types/${uuid}`);
  if (!res.success) {
    toast.error(res.message || 'Não foi possível excluir o tipo de compromisso.');
    return;
  }
  toast.success('Tipo excluído com sucesso.');
  await _load();
}

export default {
  async mount(container) {
    _container = container;

    const canCreate = _canCreate();

    container.innerHTML = `
      <div class="page-header">
        <div class="page-header__info">
          <h1>Tipos de compromisso</h1>
          <p class="subtitle">Configure as categorias de compromisso disponíveis na agenda.</p>
        </div>
        <div class="page-header__actions">
          ${canCreate ? `
            <button class="btn btn-primary btn-md" id="apt-btn-new">
              <i data-lucide="plus" style="width:15px;height:15px;"></i>
              Novo tipo
            </button>` : ''}
        </div>
      </div>

      <div id="apt-form-slot"></div>

      <div class="section">
        <div class="section__header">
          <h2>Tipos cadastrados</h2>
          <button class="btn btn-ghost btn-sm" id="apt-btn-reload">Atualizar</button>
        </div>
        <div id="apt-list">
          <div class="skeleton" style="height:80px;border-radius:var(--r-sm);"></div>
        </div>
      </div>`;

    window.lucide?.createIcons();

    if (canCreate) {
      document.getElementById('apt-btn-new')?.addEventListener('click', () => {
        _showForm('create');
        document.getElementById('apt-form-slot')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
      });
    }

    document.getElementById('apt-btn-reload')?.addEventListener('click', () => _load());

    await _load();
  },

  unmount() {
    _formMode = null;
    _editingUuid = null;
    _container = null;
  },
};
