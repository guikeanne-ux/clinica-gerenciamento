import { http } from '../../core/services/http.js';
import { permissionService } from '../../core/auth/permission-service.js';
import { toast } from '../../core/js/toast.js';

function specialtyMessage(res, fallback) {
  const field = res?.errors?.[0]?.field || '';
  if (field === 'name') return 'Informe um nome de especialidade válido.';
  return fallback;
}

export default {
  async mount(container) {
    const canEdit = permissionService.has('professionals.update');

    container.innerHTML = `
      <div class="page-header">
        <div class="page-header__info">
          <h1>Especialidades</h1>
          <p class="subtitle">Gerencie a lista de especialidades dos profissionais</p>
        </div>
      </div>

      <div class="dashboard-bottom">
        ${canEdit ? `
        <div class="section">
          <div class="section__header"><h2>Cadastrar especialidade</h2></div>
          <div class="field" style="margin-bottom:0.75rem;">
            <label>Nome da especialidade</label>
            <input class="input" id="new-spec-name" placeholder="Ex: Neuropsicologia" />
          </div>
          <button class="btn btn-primary btn-md" id="btn-add-spec">Cadastrar</button>
        </div>
        ` : ''}

        <div class="section">
          <div class="section__header">
            <h2>Especialidades cadastradas</h2>
            <button class="btn btn-ghost btn-sm" id="btn-reload-specs">Atualizar</button>
          </div>
          <div id="specs-list"><div class="skeleton" style="height:120px;border-radius:var(--r-sm);"></div></div>
        </div>
      </div>`;

    await this._load();

    if (canEdit) {
      document.getElementById('btn-add-spec')?.addEventListener('click', () => this._add());
      document.getElementById('new-spec-name')?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') this._add();
      });
    }
    document.getElementById('btn-reload-specs')?.addEventListener('click', () => this._load());
  },

  async _load() {
    const container = document.getElementById('specs-list');
    if (!container) return;
    const canEdit = permissionService.has('professionals.update');

    container.innerHTML = `<div class="skeleton" style="height:80px;border-radius:var(--r-sm);"></div>`;
    const res = await http.get('/api/v1/specialties');
    if (!res.success) {
      toast.error('Não foi possível carregar as especialidades no momento.');
      container.innerHTML = '<div class="empty-state"><div class="empty-state__title">Não foi possível carregar as especialidades.</div></div>';
      return;
    }
    const items = res.data || [];
    if (!items.length) {
      container.innerHTML = `<div class="empty-state"><div class="empty-state__title">Nenhuma especialidade cadastrada</div></div>`;
      return;
    }

    container.innerHTML = items.map((s) => `
      <div class="card" style="display:flex;align-items:center;gap:0.75rem;margin-bottom:0.4rem;padding:0.6rem 0.75rem;" id="spec-row-${s.uuid}">
        <span class="font-semibold text-sm spec-name" style="flex:1;" data-uuid="${s.uuid}">${s.name}</span>
        ${canEdit ? `
        <div style="display:flex;gap:0.4rem;">
          <button class="btn btn-ghost btn-xs btn-edit-spec" data-uuid="${s.uuid}" data-name="${s.name}">Editar</button>
          <button class="btn btn-danger btn-xs btn-delete-spec" data-uuid="${s.uuid}">Excluir</button>
        </div>` : ''}
      </div>`).join('');

    if (canEdit) {
      container.querySelectorAll('.btn-edit-spec').forEach((btn) => {
        btn.addEventListener('click', () => this._startEdit(btn.dataset.uuid, btn.dataset.name));
      });
      container.querySelectorAll('.btn-delete-spec').forEach((btn) => {
        btn.addEventListener('click', () => this._delete(btn.dataset.uuid));
      });
    }
  },

  _startEdit(uuid, currentName) {
    const row = document.getElementById(`spec-row-${uuid}`);
    if (!row) return;
    row.innerHTML = `
      <input class="input" value="${currentName}" style="flex:1;" id="edit-input-${uuid}" />
      <div style="display:flex;gap:0.4rem;">
        <button class="btn btn-primary btn-xs" id="btn-confirm-edit-${uuid}">Salvar</button>
        <button class="btn btn-ghost btn-xs" id="btn-cancel-edit-${uuid}">Cancelar</button>
      </div>`;

    const input = document.getElementById(`edit-input-${uuid}`);
    input?.focus();
    input?.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') this._confirmEdit(uuid);
      if (e.key === 'Escape') this._load();
    });
    document.getElementById(`btn-confirm-edit-${uuid}`)?.addEventListener('click', () => this._confirmEdit(uuid));
    document.getElementById(`btn-cancel-edit-${uuid}`)?.addEventListener('click', () => this._load());
  },

  async _confirmEdit(uuid) {
    const input = document.getElementById(`edit-input-${uuid}`);
    const name = input?.value.trim();
    if (!name) return;
    const res = await http.put(`/api/v1/specialties/${uuid}`, { name });
    if (res.success) {
      toast.success('Especialidade atualizada com sucesso.');
      await this._load();
    } else {
      toast.error(specialtyMessage(res, 'Não foi possível atualizar a especialidade.'));
      await this._load();
    }
  },

  async _add() {
    const input = document.getElementById('new-spec-name');
    const name = input?.value.trim();
    if (!name) return;
    const res = await http.post('/api/v1/specialties', { name });
    if (res.success) {
      toast.success('Especialidade cadastrada com sucesso.');
      if (input) input.value = '';
      await this._load();
    } else {
      toast.error(specialtyMessage(res, 'Não foi possível cadastrar a especialidade.'));
    }
  },

  async _delete(uuid) {
    if (!confirm('Excluir esta especialidade? Ela será removida de todos os profissionais que a utilizam.')) return;
    const res = await http.delete(`/api/v1/specialties/${uuid}`);
    if (res.success) {
      toast.success('Especialidade excluída com sucesso.');
      document.getElementById(`spec-row-${uuid}`)?.remove();
    } else {
      toast.error(specialtyMessage(res, 'Não foi possível excluir a especialidade.'));
    }
  },

  unmount() {},
};
