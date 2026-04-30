export default {
  mount(container) {
    container.innerHTML = `
      <div class="page-header">
        <div class="page-header__info">
          <h1>Design System</h1>
          <p class="subtitle">Componentes, tokens e padrões visuais do ClinicaGest</p>
        </div>
      </div>

      <!-- Cores -->
      <div class="section" id="colors">
        <h2>Paleta de cores</h2>
        <div style="display:flex;gap:0.5rem;flex-wrap:wrap;margin-top:0.75rem;">
          ${['#edfaf8','#8de1d7','#28b0a4','#157470','#0d3330'].map((c,i)=>`<div style="width:56px;height:56px;background:${c};border-radius:var(--r-sm);border:1px solid var(--border);display:flex;align-items:flex-end;padding:4px;"><span style="font-size:0.6rem;color:${i<2?'#333':'#fff'}">${c}</span></div>`).join('')}
          <div style="width:20px;"></div>
          ${['#fef3dc','#f7c564','#d08a18','#a86d0f'].map((c,i)=>`<div style="width:56px;height:56px;background:${c};border-radius:var(--r-sm);border:1px solid var(--border);display:flex;align-items:flex-end;padding:4px;"><span style="font-size:0.6rem;color:${i<2?'#333':'#fff'}">${c}</span></div>`).join('')}
        </div>
      </div>

      <!-- Tipografia -->
      <div class="section" id="typography">
        <h2>Tipografia</h2>
        <h1>Título h1 — 1.625rem / 700</h1>
        <h2>Subtítulo h2 — 1.2rem / 600</h2>
        <h3>Heading h3 — 1rem / 600</h3>
        <p>Parágrafo padrão com cor muted para textos de suporte e descrições.</p>
        <p class="text-sm text-muted">Texto pequeno (text-sm) para labels e dicas.</p>
      </div>

      <!-- Botões -->
      <div class="section" id="buttons">
        <h2>Botões</h2>
        <div class="row-wrap" style="margin-bottom:0.75rem;">
          <button class="btn btn-primary btn-md">Primário</button>
          <button class="btn btn-secondary btn-md">Secundário</button>
          <button class="btn btn-outline btn-md">Outline</button>
          <button class="btn btn-ghost btn-md">Ghost</button>
          <button class="btn btn-danger btn-md">Destrutivo</button>
          <button class="btn btn-accent btn-md">Accent</button>
        </div>
        <div class="row-wrap">
          <button class="btn btn-primary btn-xs">XS</button>
          <button class="btn btn-primary btn-sm">SM</button>
          <button class="btn btn-primary btn-md">MD</button>
          <button class="btn btn-primary btn-lg">LG</button>
          <button class="btn btn-primary btn-md" disabled>Desabilitado</button>
        </div>
      </div>

      <!-- Badges -->
      <div class="section" id="badges">
        <h2>Badges</h2>
        <div class="row-wrap">
          <span class="badge badge-success">Ativo</span>
          <span class="badge badge-warning">Pendente</span>
          <span class="badge badge-error">Inativo</span>
          <span class="badge badge-info">Info</span>
          <span class="badge badge-neutral">Neutro</span>
          <span class="badge badge-primary">Primário</span>
        </div>
      </div>

      <!-- Alertas -->
      <div class="section" id="alerts">
        <h2>Alertas</h2>
        <div class="alert alert-success" style="margin-bottom:0.5rem;">Operação realizada com sucesso.</div>
        <div class="alert alert-error" style="margin-bottom:0.5rem;">Erro ao salvar. Verifique os campos.</div>
        <div class="alert alert-warning" style="margin-bottom:0.5rem;">Atenção: esta ação não pode ser desfeita.</div>
        <div class="alert alert-info">Sua sessão expira em 30 minutos.</div>
      </div>

      <!-- Formulários -->
      <div class="section" id="forms">
        <h2>Formulários</h2>
        <div class="form-grid">
          <div class="field">
            <label>Campo de texto</label>
            <input class="input" placeholder="Placeholder…" />
          </div>
          <div class="field">
            <label>Com dica</label>
            <input class="input" placeholder="Ex: João Silva" />
            <div class="hint">Texto de apoio ao campo.</div>
          </div>
          <div class="field has-error">
            <label>Com erro</label>
            <input class="input" value="Valor inválido" />
            <div class="field-error">Este campo é obrigatório.</div>
          </div>
          <div class="field">
            <label>Select</label>
            <select class="input">
              <option>Opção 1</option>
              <option>Opção 2</option>
            </select>
          </div>
          <div class="field">
            <label>Textarea</label>
            <textarea class="input" placeholder="Digite aqui…"></textarea>
          </div>
        </div>
      </div>

      <!-- Cards -->
      <div class="section" id="cards">
        <h2>Cards</h2>
        <div class="cards-grid">
          <div class="card">
            <h3>Card padrão</h3>
            <p>Descrição breve do conteúdo do card.</p>
            <div style="margin-top:0.75rem;"><button class="btn btn-secondary btn-sm">Ação</button></div>
          </div>
          <div class="card card--active">
            <h3>Card ativo</h3>
            <p>Destaque para estado selecionado.</p>
          </div>
          <div class="stat-card">
            <div class="stat-card__icon stat-card__icon--primary">
              <svg viewBox="0 0 20 20" fill="currentColor" width="20" height="20"><path d="M9 6a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </div>
            <div class="stat-card__label">Pacientes</div>
            <div class="stat-card__value">142</div>
            <div class="stat-card__sub">Cadastrados no sistema</div>
          </div>
        </div>
      </div>

      <!-- Tabela -->
      <div class="section" id="tables">
        <h2>Tabela</h2>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Nome</th><th>Cargo</th><th>Status</th><th></th></tr></thead>
            <tbody>
              <tr><td data-label="Nome" class="font-medium">João Silva</td><td data-label="Cargo">Psicólogo</td><td data-label="Status"><span class="badge badge-success">Ativo</span></td><td><button class="btn btn-ghost btn-sm">Editar</button></td></tr>
              <tr><td data-label="Nome" class="font-medium">Maria Souza</td><td data-label="Cargo">Fonoaudióloga</td><td data-label="Status"><span class="badge badge-neutral">Inativo</span></td><td><button class="btn btn-ghost btn-sm">Editar</button></td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Empty state -->
      <div class="section" id="empty">
        <h2>Empty state</h2>
        <div class="empty-state">
          <div class="empty-state__icon">
            <svg viewBox="0 0 20 20" fill="currentColor" width="24" height="24"><path d="M9 6a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
          </div>
          <div class="empty-state__title">Nenhum registro encontrado</div>
          <div class="empty-state__desc">Tente ajustar os filtros ou cadastre um novo item.</div>
          <div class="empty-state__action"><button class="btn btn-primary btn-md">+ Adicionar</button></div>
        </div>
      </div>`;
  },

  unmount() {},
};
