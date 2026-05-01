import { renderScheduleEventCard } from './schedule-event-card.js';

function pad2(value) {
  return String(value).padStart(2, '0');
}

function formatDayHeading(date) {
  return date.toLocaleDateString('pt-BR', {
    weekday: 'long',
    day: '2-digit',
    month: 'long',
    year: 'numeric',
  });
}

function hourRange() {
  const hours = [];
  for (let hour = 6; hour <= 22; hour += 1) {
    hours.push(`${pad2(hour)}:00`);
  }
  return hours;
}

function parseDate(value) {
  const parsed = new Date(String(value).replace(' ', 'T'));
  return Number.isNaN(parsed.getTime()) ? null : parsed;
}

export function renderScheduleDayView({ events, anchorDate, lookups, uiState = {} }) {
  const sorted = [...events].sort((a, b) => String(a.starts_at).localeCompare(String(b.starts_at)));

  if (!sorted.length) {
    const hasActiveFilters = Boolean(uiState?.hasActiveFilters);
    const title = hasActiveFilters ? 'Nenhum compromisso encontrado para este período.' : 'Nenhum compromisso neste dia.';
    const description = hasActiveFilters
      ? 'Tente limpar ou ajustar os filtros para ampliar os resultados.'
      : 'Tente navegar para outro período ou ajustar os filtros.';

    return `
      <div class="empty-state schedule-empty-state">
        <div class="empty-state__icon"><i data-lucide="calendar-days" style="width:24px;height:24px;"></i></div>
        <div class="empty-state__title">${title}</div>
        <div class="empty-state__desc">${description}</div>
      </div>`;
  }

  const slots = hourRange().map((slot) => {
    const hour = Number(slot.slice(0, 2));
    const bucket = sorted.filter((item) => {
      const parsed = parseDate(item.starts_at);
      if (!parsed) return false;
      return parsed.getHours() === hour;
    });

    return `
      <div class="schedule-day-slot">
        <div class="schedule-day-slot__hour">${slot}</div>
        <div class="schedule-day-slot__events">
          ${bucket.length
            ? bucket.map((event) => renderScheduleEventCard(event, { lookups, variant: 'day' })).join('')
            : '<div class="schedule-day-slot__empty"></div>'}
        </div>
      </div>`;
  }).join('');

  const outsideHourEvents = sorted.filter((item) => {
    const parsed = parseDate(item.starts_at);
    if (!parsed) return false;

    const hour = parsed.getHours();
    return hour < 6 || hour > 22;
  });

  return `
    <section class="schedule-day-view" aria-label="Visão diária da agenda">
      <header class="schedule-view-header">
        <h3>${formatDayHeading(anchorDate)}</h3>
      </header>

      <div class="schedule-day-grid">
        ${slots}
      </div>

      ${outsideHourEvents.length
        ? `
          <div class="schedule-outside-hours">
            <h4>Fora do horário padrão</h4>
            <div class="schedule-outside-hours__list">
              ${outsideHourEvents.map((event) => renderScheduleEventCard(event, { lookups, variant: 'day' })).join('')}
            </div>
          </div>`
        : ''}
    </section>`;
}
