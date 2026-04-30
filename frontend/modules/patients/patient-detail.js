import { http } from '../../core/services/http.js';
import { navigate } from '../../core/router/router.js';
import { permissionService } from '../../core/auth/permission-service.js';
import { toast } from '../../core/js/toast.js';

function initials(name = '') {
  return name.split(' ').filter(Boolean).slice(0, 2).map((w) => w[0].toUpperCase()).join('');
}

function escapeHtml(value) {
  return String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');
}

function fmt(value, fallback = 'Nao informado') {
  const safe = String(value ?? '').trim();
  return safe === '' ? fallback : safe;
}

function fmtDate(value) {
  if (!value) return 'Nao informado';
  const parsed = new Date(value);
  if (Number.isNaN(parsed.getTime())) return 'Nao informado';
  return parsed.toLocaleDateString('pt-BR');
}

function boolLabel(value) {
  return value ? 'Sim' : 'Nao';
}

function infoField(label, value) {
  return `<div>
    <div class="text-xs text-muted" style="margin-bottom:0.15rem;">${escapeHtml(label)}</div>
    <div class="font-medium text-sm">${escapeHtml(value)}</div>
  </div>`;
}

export default {
  async mount(container, params) {
    const { uuid } = params;

    container.innerHTML = '<div class="skeleton" style="height:300px;border-radius:var(--r);"></div>';

    const res = await http.get(`/api/v1/patients/${uuid}`, { context: 'page' });
    if (!res.success) {
      const notFound =
        (res?.meta?.error_code === 'NOT_FOUND') || /nao encontrado|não encontrado/i.test(String(res.message || ''));

      if (notFound) {
        toast.error('Paciente nao encontrado.');
        container.innerHTML = `
          <div class="error-page">
            <div class="error-page__code">404</div>
            <div class="error-page__title">Paciente nao encontrado</div>
            <p class="error-page__desc">O paciente informado nao existe ou foi removido.</p>
            <button class="btn btn-secondary btn-md" id="btn-back-pat">Voltar para lista</button>
          </div>`;
        document.getElementById('btn-back-pat')?.addEventListener('click', () => navigate('/patients'));
        return;
      }

      toast.error(res.message || 'Nao foi possivel carregar os dados do paciente.');
      container.innerHTML = `
        <div class="section">
          <div class="alert alert-error">${escapeHtml(res.message || 'Nao foi possivel carregar os dados do paciente.')}</div>
          <div class="row-wrap" style="margin-top:0.8rem;">
            <button class="btn btn-secondary btn-md" id="btn-back-pat">Voltar para lista</button>
          </div>
        </div>`;
      document.getElementById('btn-back-pat')?.addEventListener('click', () => navigate('/patients'));
      return;
    }

    const p = res.data;
    const ini = initials(p.full_name);

    container.innerHTML = `
      <div class="page-header">
        <div class="page-header__info">
          <div class="detail-hero">
            <div class="detail-hero__avatar">${escapeHtml(ini || 'PA')}</div>
            <div class="detail-hero__info">
              <div class="detail-hero__name">${escapeHtml(fmt(p.full_name, 'Paciente'))}</div>
              <div class="detail-hero__sub">
                <span class="badge badge-${p.status === 'active' ? 'success' : 'neutral'}">${p.status === 'active' ? 'Ativo' : 'Inativo'}</span>
                <span class="text-muted text-sm" style="margin-left:0.5rem;">Nascimento: ${escapeHtml(fmtDate(p.birth_date))}</span>
              </div>
            </div>
          </div>
        </div>
        <div class="page-header__actions">
          <button class="btn btn-secondary btn-md" id="btn-back-pat">Voltar</button>
          ${permissionService.has('patients.update') ? '<button class="btn btn-primary btn-md" id="btn-edit-pat">Editar paciente</button>' : ''}
        </div>
      </div>

      <div class="tabs" id="patient-detail-tabs">
        <button class="tab active" data-target="ptab-info">Informacoes gerais</button>
        <button class="tab" data-target="ptab-resp">Responsaveis</button>
        <button class="tab" data-target="ptab-timeline">Linha do tempo</button>
        <button class="tab" data-target="ptab-record">Prontuario</button>
        <button class="tab" data-target="ptab-files">Arquivos</button>
      </div>

      <div id="ptab-info">
        <div class="section">
          <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:1rem;">
            ${infoField('Nome completo', fmt(p.full_name))}
            ${infoField('Data de nascimento', fmtDate(p.birth_date))}
            ${infoField('Nome social', fmt(p.social_name))}
            ${infoField('Genero', fmt(p.gender))}
            ${infoField('CPF', fmt(p.cpf))}
            ${infoField('RG', fmt(p.rg))}
            ${infoField('CNS', fmt(p.cns))}
            ${infoField('CID', fmt(p.cid))}
            ${infoField('E-mail', fmt(p.email))}
            ${infoField('Telefone principal', fmt(p.phone_primary))}
            ${infoField('Telefone secundario', fmt(p.phone_secondary))}
            ${infoField('Nome do pai', fmt(p.father_name))}
            ${infoField('Nome da mae', fmt(p.mother_name))}
            ${infoField('CEP', fmt(p.address_zipcode))}
            ${infoField('Logradouro', fmt(p.address_street))}
            ${infoField('Numero', fmt(p.address_number))}
            ${infoField('Complemento', fmt(p.address_complement))}
            ${infoField('Bairro', fmt(p.address_district))}
            ${infoField('Cidade', fmt(p.address_city))}
            ${infoField('Estado', fmt(p.address_state))}
            ${infoField('Convenio', fmt(p.health_plan_name))}
            ${infoField('Numero da carteirinha', fmt(p.health_plan_card_number))}
            ${infoField('Origem', fmt(p.origin))}
            ${infoField('Observacoes gerais', fmt(p.general_notes))}
            ${infoField('Status', p.status === 'active' ? 'Ativo' : 'Inativo')}
          </div>
        </div>
      </div>

      <div id="ptab-resp" style="display:none;">
        <div class="section">
          ${permissionService.has('patients.update') ? `
          <div class="form-group" style="margin-bottom:0.75rem;">
            <div class="form-group__title">Cadastrar responsável</div>
            <form id="responsible-form">
              <div class="form-grid">
                <div class="field">
                  <label>Nome</label>
                  <input class="input" name="name" placeholder="Nome do responsável" required />
                </div>
                <div class="field">
                  <label>Parentesco</label>
                  <input class="input" name="kinship" placeholder="Ex: Mãe, Pai, Tio" />
                </div>
                <div class="field">
                  <label>CPF</label>
                  <input class="input" name="cpf" placeholder="000.000.000-00" />
                </div>
                <div class="field">
                  <label>Telefone</label>
                  <input class="input" name="phone" placeholder="(00) 00000-0000" />
                </div>
                <div class="field">
                  <label>E-mail</label>
                  <input class="input" name="email" type="email" placeholder="email@dominio.com" />
                </div>
              </div>
              <div class="row-wrap" style="margin-top:0.6rem;">
                <label style="display:flex;align-items:center;gap:0.4rem;">
                  <input type="checkbox" name="is_financial_responsible" value="1" />
                  Responsável financeiro
                </label>
                <label style="display:flex;align-items:center;gap:0.4rem;">
                  <input type="checkbox" name="is_primary_contact" value="1" />
                  Contato principal
                </label>
                <button type="button" class="btn btn-primary btn-sm" id="btn-save-responsible" style="margin-left:auto;">
                  Adicionar responsável
                </button>
              </div>
            </form>
          </div>` : ''}
          <div id="resp-list"><div class="skeleton" style="height:80px;border-radius:var(--r-sm);"></div></div>
        </div>
      </div>

      <div id="ptab-timeline" style="display:none;">
        <div class="section">
          <div class="empty-state"><div class="empty-state__title">Linha do tempo sera implementada em entrega futura.</div></div>
        </div>
      </div>

      <div id="ptab-record" style="display:none;">
        <div class="section">
          <div class="empty-state"><div class="empty-state__title">Prontuario sera implementado em entrega futura.</div></div>
        </div>
      </div>

      <div id="ptab-files" style="display:none;">
        <div class="section">
          <div class="empty-state"><div class="empty-state__title">Arquivos serao implementados em entrega futura.</div></div>
        </div>
      </div>`;

    document.getElementById('btn-back-pat')?.addEventListener('click', () => navigate('/patients'));
    document.getElementById('btn-edit-pat')?.addEventListener('click', () => navigate(`/patients/${uuid}/edit`));
    document.getElementById('btn-save-responsible')?.addEventListener('click', () => this._saveResponsible(uuid));

    const allTabs = ['ptab-info', 'ptab-resp', 'ptab-timeline', 'ptab-record', 'ptab-files'];
    document.querySelectorAll('#patient-detail-tabs .tab').forEach((tab) => {
      tab.addEventListener('click', () => {
        document.querySelectorAll('#patient-detail-tabs .tab').forEach((t) => t.classList.remove('active'));
        tab.classList.add('active');
        const id = tab.dataset.target;
        allTabs.forEach((t) => {
          const el = document.getElementById(t);
          if (el) el.style.display = t === id ? '' : 'none';
        });
        if (id === 'ptab-resp') this._loadResponsibles(uuid);
      });
    });

    await this._loadResponsibles(uuid);
  },

  async _loadResponsibles(uuid) {
    const container = document.getElementById('resp-list');
    if (!container) return;

    const res = await http.get(`/api/v1/patients/${uuid}/responsibles`, { context: 'page' });
    if (!res.success) {
      toast.error(res.message || 'Nao foi possivel carregar os responsaveis.');
      container.innerHTML = `<div class="alert alert-error">${escapeHtml(res.message || 'Erro ao carregar responsaveis.')}</div>`;
      return;
    }

    const items = res.data || [];
    if (!items.length) {
      container.innerHTML = '<div class="empty-state"><div class="empty-state__title">Nenhum responsavel cadastrado para este paciente.</div></div>';
      return;
    }

    container.innerHTML = items.map((r) => `
      <div class="card" style="margin-bottom:0.5rem;">
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:0.85rem;">
          ${infoField('Nome', fmt(r.name))}
          ${infoField('Parentesco', fmt(r.kinship))}
          ${infoField('CPF', fmt(r.cpf))}
          ${infoField('Telefone', fmt(r.phone))}
          ${infoField('E-mail', fmt(r.email))}
          ${infoField('Responsavel financeiro', boolLabel(Boolean(r.is_financial_responsible)))}
          ${infoField('Contato principal', boolLabel(Boolean(r.is_primary_contact)))}
        </div>
      </div>`).join('');
  },

  async _saveResponsible(patientUuid) {
    const form = document.getElementById('responsible-form');
    if (!form) return;

    const data = Object.fromEntries(new FormData(form));
    if (!data.name || String(data.name).trim() === '') {
      toast.warning('Informe o nome do responsável.');
      return;
    }

    data.is_financial_responsible = Boolean(data.is_financial_responsible);
    data.is_primary_contact = Boolean(data.is_primary_contact);
    Object.keys(data).forEach((key) => {
      if (data[key] === '') delete data[key];
    });

    const res = await http.post(`/api/v1/patients/${patientUuid}/responsibles`, data);
    if (!res.success) {
      toast.error(res.message || 'Não foi possível cadastrar o responsável.');
      return;
    }

    toast.success('Responsável cadastrado com sucesso.');
    form.reset();
    await this._loadResponsibles(patientUuid);
  },

  unmount() {},
};
