import { getEventColor } from './schedule-event-card.js';

function escapeHtml(value) {
  return String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

function formatDate(date) {
  return `${String(date.getFullYear())}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
}

function startOfGrid(anchorDate) {
  const firstDay = new Date(anchorDate.getFullYear(), anchorDate.getMonth(), 1);
  const weekday = firstDay.getDay();
  const mondayShift = weekday === 0 ? -6 : 1 - weekday;
  firstDay.setDate(firstDay.getDate() + mondayShift);
  firstDay.setHours(0, 0, 0, 0);
  return firstDay;
}

function parseEventDate(value) {
  const date = new Date(String(value).replace(' ', 'T'));
  if (Number.isNaN(date.getTime())) return '';
  return formatDate(date);
}

function weekdayHeaders() {
  return ['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom'];
}

function dayNumber(date) {
  return date.toLocaleDateString('pt-BR', { day: '2-digit' });
}

function fullNameFromLookup(map, uuid) {
  return map?.[uuid]?.full_name || map?.[uuid]?.name || '—';
}

function fullNameFromEventOrLookup(map, uuid, fallbackFromEvent) {
  const fromEvent = String(fallbackFromEvent || '').trim();
  if (fromEvent) return fromEvent;
  return fullNameFromLookup(map, uuid);
}

function statusLabel(status) {
  const labels = {
    agendado: 'Agendado',
    confirmado: 'Confirmado',
    realizado: 'Realizado',
    cancelado: 'Cancelado',
    falta: 'Falta',
    remarcado: 'Remarcado',
    bloqueado: 'Bloqueado',
  };
  return labels[String(status || '').trim()] || String(status || '—');
}

export function renderScheduleMonthView({ events, anchorDate, lookups, uiState = {} }) {
  const gridStart = startOfGrid(anchorDate);
  const hasActiveFilters = Boolean(uiState?.hasActiveFilters);
  const hasEvents = Array.isArray(events) && events.length > 0;

  const days = Array.from({ length: 42 }, (_, index) => {
    const date = new Date(gridStart);
    date.setDate(gridStart.getDate() + index);
    const key = formatDate(date);

    const items = events
      .filter((event) => parseEventDate(event.starts_at) === key)
      .sort((a, b) => String(a.starts_at).localeCompare(String(b.starts_at)));

    return {
      key,
      date,
      items,
      isCurrentMonth: date.getMonth() === anchorDate.getMonth(),
      isToday: key === formatDate(new Date()),
    };
  });

  return `
    <section class="schedule-month-view" aria-label="Visão mensal da agenda">
      ${hasEvents
        ? ''
        : `
          <div class="schedule-month-empty empty-state schedule-empty-state">
            <div class="empty-state__icon"><i data-lucide="calendar" style="width:24px;height:24px;"></i></div>
            <div class="empty-state__title">${hasActiveFilters ? 'Nenhum compromisso encontrado para este período.' : 'Nenhum compromisso neste mês.'}</div>
            <div class="empty-state__desc">${hasActiveFilters ? 'Tente limpar ou ajustar os filtros para ampliar os resultados.' : 'Use os controles de navegação para verificar outros períodos.'}</div>
          </div>
        `}

      <div class="schedule-month-grid schedule-month-grid--header">
        ${weekdayHeaders().map((label) => `<div class="schedule-month-weekday">${label}</div>`).join('')}
      </div>

      <div class="schedule-month-grid schedule-month-grid--body">
        ${days.map((entry) => {
          const previewItems = entry.items.slice(0, 3);
          const remaining = Math.max(0, entry.items.length - previewItems.length);

          return `
            <article class="schedule-month-cell${entry.isCurrentMonth ? '' : ' is-outside'}${entry.isToday ? ' is-today' : ''}">
              <header class="schedule-month-cell__header">
                <span>${dayNumber(entry.date)}</span>
                ${entry.items.length ? `<span class="schedule-month-cell__count">${entry.items.length}</span>` : ''}
              </header>

              <div class="schedule-month-cell__events">
                ${previewItems.map((event) => `
                  <button
                    type="button"
                    class="schedule-month-item"
                    data-schedule-event="${escapeHtml(event.uuid)}"
                    style="--event-color:${escapeHtml(getEventColor(event, lookups))};"
                    title="${escapeHtml(event.title || 'Evento')}"
                  >
                    <span class="schedule-month-item__time">${escapeHtml(String(event.starts_at || '').slice(11, 16))}</span>
                    <span class="schedule-month-item__title">${escapeHtml(event.title || 'Evento')}</span>
                  </button>`).join('')}

                ${remaining > 0 ? `
                  <button
                    type="button"
                    class="schedule-month-more"
                    data-schedule-day-more="${escapeHtml(entry.key)}"
                    aria-label="Mostrar todos os ${entry.items.length} compromissos do dia ${escapeHtml(dayNumber(entry.date))}"
                  >+${remaining} compromissos</button>` : ''}
              </div>
            </article>`;
        }).join('')}
      </div>

      <div class="modal" id="schedule-day-events-modal" aria-hidden="true">
        <div class="modal__panel modal__panel--lg schedule-modal-panel" role="dialog" aria-modal="true" aria-labelledby="schedule-day-events-title">
          <div class="modal__header">
            <h2 id="schedule-day-events-title">Compromissos do dia</h2>
            <button type="button" class="modal__close" data-schedule-modal-close aria-label="Fechar">✕</button>
          </div>
          <div class="modal__body" id="schedule-day-events-body"></div>
        </div>
      </div>
    </section>`;
}

export function renderScheduleMonthDayEventsList({ date, events, lookups }) {
  const dayLabel = new Date(`${date}T00:00:00`).toLocaleDateString('pt-BR', {
    day: '2-digit',
    month: 'long',
    year: 'numeric',
  });

  const rows = (events || []).map((event) => {
    const eventTypeName = event.event_type_name || lookups?.eventTypesByUuid?.[event.event_type_uuid]?.name || '—';
    const professionalName = fullNameFromEventOrLookup(
      lookups?.professionalsByUuid,
      event.professional_uuid,
      event.professional_name
    );
    const patientName = fullNameFromEventOrLookup(
      lookups?.patientsByUuid,
      event.patient_uuid,
      event.patient_name
    );
    const color = getEventColor(event, lookups);

    return `
      <button type="button" class="schedule-day-events-item" data-schedule-day-event="${escapeHtml(event.uuid)}">
        <span class="schedule-day-events-item__color" style="background:${escapeHtml(color)};"></span>
        <span class="schedule-day-events-item__time">${escapeHtml(String(event.starts_at || '').slice(11, 16))} - ${escapeHtml(String(event.ends_at || '').slice(11, 16))}</span>
        <span class="schedule-day-events-item__title">${escapeHtml(event.title || 'Evento')}</span>
        <span class="schedule-day-events-item__meta">Profissional: ${escapeHtml(professionalName)}</span>
        <span class="schedule-day-events-item__meta">Paciente: ${escapeHtml(patientName)}</span>
        <span class="schedule-day-events-item__meta">Tipo: ${escapeHtml(eventTypeName)}</span>
        <span class="schedule-day-events-item__meta">Status: ${escapeHtml(statusLabel(event.status))}</span>
      </button>`;
  }).join('');

  return `
    <div class="schedule-day-events-list" role="region" aria-label="Compromissos de ${escapeHtml(dayLabel)}">
      <p class="schedule-day-events-list__subtitle">Todos os compromissos de ${escapeHtml(dayLabel)}</p>
      <div class="schedule-day-events-list__items">
        ${rows || '<p class="schedule-day-events-list__empty">Nenhum compromisso neste dia.</p>'}
      </div>
    </div>`;
}
