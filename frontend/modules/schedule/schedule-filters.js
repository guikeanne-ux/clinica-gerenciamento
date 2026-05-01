const STATUS_OPTIONS = [
  { value: '', label: 'Todos' },
  { value: 'agendado', label: 'Agendado' },
  { value: 'confirmado', label: 'Confirmado' },
  { value: 'realizado', label: 'Realizado' },
  { value: 'cancelado', label: 'Cancelado' },
  { value: 'falta', label: 'Falta' },
  { value: 'remarcado', label: 'Remarcado' },
  { value: 'bloqueado', label: 'Bloqueado' },
];

const STATUS_LEGEND = [
  { key: 'agendado', label: 'Agendado' },
  { key: 'confirmado', label: 'Confirmado' },
  { key: 'realizado', label: 'Realizado' },
  { key: 'cancelado', label: 'Cancelado' },
  { key: 'falta', label: 'Falta' },
  { key: 'remarcado', label: 'Remarcado' },
  { key: 'bloqueado', label: 'Bloqueado' },
];

const CATEGORY_LABELS = {
  atendimento: 'Atendimento',
  reuniao: 'Reunião',
  reunião: 'Reunião',
  bloqueio: 'Bloqueio',
  ferias: 'Férias',
  férias: 'Férias',
  feriado: 'Feriado',
  evento_interno: 'Evento interno',
  lembrete: 'Lembrete',
  outro: 'Outro',
};

function escapeHtml(value) {
  return String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

function renderSelectOptions(items, selectedValue, labelResolver, valueResolver) {
  return items.map((item) => {
    const value = String(valueResolver(item) || '');
    const label = String(labelResolver(item) || '');
    const isSelected = String(selectedValue || '') === value;

    return `<option value="${escapeHtml(value)}" ${isSelected ? 'selected' : ''}>${escapeHtml(label)}</option>`;
  }).join('');
}

function normalizeHex(value, fallback) {
  const color = String(value || '').trim();
  if (/^#[0-9A-Fa-f]{6}$/.test(color)) return color;
  return fallback;
}

function categoryLabel(value) {
  const key = String(value || '').trim();
  if (!key) return '';
  return CATEGORY_LABELS[key] || key;
}

function nonEmpty(value) {
  return String(value || '').trim() !== '';
}

function activeFilterChips({ filters, professionals, patients, eventTypes }) {
  const typeByUuid = eventTypes.reduce((acc, item) => {
    if (item?.uuid) acc[item.uuid] = item;
    return acc;
  }, {});

  const professionalByUuid = professionals.reduce((acc, item) => {
    if (item?.uuid) acc[item.uuid] = item;
    return acc;
  }, {});

  const patientByUuid = patients.reduce((acc, item) => {
    if (item?.uuid) acc[item.uuid] = item;
    return acc;
  }, {});

  const statusByValue = STATUS_OPTIONS.reduce((acc, item) => {
    if (item.value) acc[item.value] = item.label;
    return acc;
  }, {});

  const chips = [];

  if (nonEmpty(filters.professional_uuid)) {
    chips.push(`Profissional: ${professionalByUuid[filters.professional_uuid]?.full_name || 'Selecionado'}`);
  }

  if (nonEmpty(filters.patient_uuid)) {
    chips.push(`Paciente: ${patientByUuid[filters.patient_uuid]?.full_name || 'Selecionado'}`);
  }

  if (nonEmpty(filters.event_type_uuid)) {
    chips.push(`Tipo: ${typeByUuid[filters.event_type_uuid]?.name || 'Selecionado'}`);
  }

  if (nonEmpty(filters.status)) {
    chips.push(`Status: ${statusByValue[filters.status] || filters.status}`);
  }

  if (nonEmpty(filters.category)) {
    chips.push(`Categoria: ${categoryLabel(filters.category)}`);
  }

  if (nonEmpty(filters.start_date)) {
    chips.push(`De: ${filters.start_date}`);
  }

  if (nonEmpty(filters.end_date)) {
    chips.push(`Até: ${filters.end_date}`);
  }

  return chips;
}

export function hasActiveScheduleFilters(filters) {
  return Object.values(filters || {}).some((value) => nonEmpty(value));
}

export function renderScheduleFilters({ filters, professionals, patients, eventTypes, collapsed }) {
  const onlyActiveTypes = eventTypes.filter((type) => String(type.status || 'ativo') === 'ativo');

  const categoryOptions = Array.from(new Set(
    onlyActiveTypes
      .map((item) => String(item.category || '').trim())
      .filter(Boolean)
  )).sort((a, b) => a.localeCompare(b, 'pt-BR'));

  const typeLegend = onlyActiveTypes.slice(0, 12).map((type) => {
    const color = normalizeHex(type.color, '#157470');

    return `
      <span class="schedule-legend__item" title="${escapeHtml(type.name)}">
        <span class="schedule-legend__dot" style="background:${escapeHtml(color)};"></span>
        <span>${escapeHtml(type.name)}</span>
      </span>`;
  }).join('');

  const professionalsLegend = professionals
    .filter((professional) => /^#[0-9A-Fa-f]{6}$/.test(String(professional.schedule_color || '').trim()))
    .slice(0, 8)
    .map((professional) => `
      <span class="schedule-legend__item" title="${escapeHtml(professional.full_name || professional.name || 'Profissional')}">
        <span class="schedule-legend__dot" style="background:${escapeHtml(professional.schedule_color)};"></span>
        <span>${escapeHtml((professional.full_name || professional.name || '').split(' ').slice(0, 2).join(' '))}</span>
      </span>`)
    .join('');

  const chips = activeFilterChips({ filters, professionals, patients, eventTypes });

  return `
    <section class="schedule-filters section">
      <div class="schedule-filters__header">
        <h3>Filtros da agenda ${chips.length ? `<span class="schedule-filter-count">${chips.length} ativo(s)</span>` : ''}</h3>
        <button
          type="button"
          id="schedule-toggle-filters"
          class="btn btn-ghost btn-sm"
          aria-expanded="${collapsed ? 'false' : 'true'}"
          aria-controls="schedule-filters-body"
        >
          ${collapsed ? 'Mostrar filtros' : 'Ocultar filtros'}
        </button>
      </div>

      <div id="schedule-filters-body" class="schedule-filters__body${collapsed ? ' is-collapsed' : ''}">
        <div class="filters-bar schedule-filters__grid">
          <div class="field">
            <label for="schedule-filter-professional">Profissional</label>
            <select id="schedule-filter-professional" class="input">
              <option value="">Todos</option>
              ${renderSelectOptions(
                professionals,
                filters.professional_uuid,
                (item) => item.full_name || item.name,
                (item) => item.uuid
              )}
            </select>
          </div>

          <div class="field">
            <label for="schedule-filter-patient">Paciente</label>
            <select id="schedule-filter-patient" class="input">
              <option value="">Todos</option>
              ${renderSelectOptions(
                patients,
                filters.patient_uuid,
                (item) => item.full_name,
                (item) => item.uuid
              )}
            </select>
          </div>

          <div class="field">
            <label for="schedule-filter-event-type">Tipo de evento</label>
            <select id="schedule-filter-event-type" class="input">
              <option value="">Todos</option>
              ${renderSelectOptions(
                onlyActiveTypes,
                filters.event_type_uuid,
                (item) => item.name,
                (item) => item.uuid
              )}
            </select>
          </div>

          <div class="field">
            <label for="schedule-filter-status">Status</label>
            <select id="schedule-filter-status" class="input">
              ${renderSelectOptions(
                STATUS_OPTIONS,
                filters.status,
                (item) => item.label,
                (item) => item.value
              )}
            </select>
          </div>

          <div class="field">
            <label for="schedule-filter-category">Categoria</label>
            <select id="schedule-filter-category" class="input">
              <option value="">Todas</option>
              ${categoryOptions.map((category) => `
                <option value="${escapeHtml(category)}" ${filters.category === category ? 'selected' : ''}>${escapeHtml(categoryLabel(category))}</option>
              `).join('')}
            </select>
          </div>

          <div class="field">
            <label for="schedule-filter-start-date">Data inicial</label>
            <input id="schedule-filter-start-date" type="date" class="input" value="${escapeHtml(filters.start_date || '')}" />
          </div>

          <div class="field">
            <label for="schedule-filter-end-date">Data final</label>
            <input id="schedule-filter-end-date" type="date" class="input" value="${escapeHtml(filters.end_date || '')}" />
          </div>
        </div>

        <div class="schedule-filters__actions">
          <button type="button" id="schedule-apply-filters" class="btn btn-secondary btn-md">Aplicar filtros</button>
          <button type="button" id="schedule-clear-filters" class="btn btn-ghost btn-md">Limpar filtros</button>
        </div>

        ${chips.length
          ? `<div class="schedule-filter-chips" aria-live="polite">${chips.map((chip) => `<span class="schedule-filter-chip">${escapeHtml(chip)}</span>`).join('')}</div>`
          : ''}

        <div class="schedule-legend schedule-legend--stacked">
          <div class="schedule-legend__section">
            <strong>Regra de cor:</strong>
            <p class="schedule-legend__help">Override do evento > cor do profissional > cor do tipo > cor padrão.</p>
          </div>

          ${professionalsLegend
            ? `<div class="schedule-legend__section">
                <strong>Profissionais:</strong>
                <div class="schedule-legend__list">${professionalsLegend}</div>
              </div>`
            : ''}

          <div class="schedule-legend__section">
            <strong>Cores por tipo:</strong>
            <div class="schedule-legend__list">
              ${typeLegend || '<span class="schedule-legend__empty">Nenhuma cor configurada.</span>'}
            </div>
          </div>

          <div class="schedule-legend__section">
            <strong>Status:</strong>
            <div class="schedule-legend__list schedule-status-legend">
              ${STATUS_LEGEND.map((status) => `
                <span class="schedule-legend__item schedule-legend__item--status schedule-status-key schedule-status-key--${status.key}">
                  <span class="schedule-legend__dot"></span>
                  <span>${escapeHtml(status.label)}</span>
                </span>`).join('')}
            </div>
          </div>
        </div>
      </div>
    </section>`;
}

export function readScheduleFiltersFromDom() {
  return {
    professional_uuid: document.getElementById('schedule-filter-professional')?.value || '',
    patient_uuid: document.getElementById('schedule-filter-patient')?.value || '',
    event_type_uuid: document.getElementById('schedule-filter-event-type')?.value || '',
    status: document.getElementById('schedule-filter-status')?.value || '',
    category: document.getElementById('schedule-filter-category')?.value || '',
    start_date: document.getElementById('schedule-filter-start-date')?.value || '',
    end_date: document.getElementById('schedule-filter-end-date')?.value || '',
  };
}
