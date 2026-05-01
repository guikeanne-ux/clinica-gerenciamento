import { renderScheduleEventCard } from './schedule-event-card.js';

function formatDate(date) {
  return `${String(date.getFullYear())}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
}

function startOfWeek(date) {
  const value = new Date(date);
  value.setHours(0, 0, 0, 0);
  const day = value.getDay();
  const mondayShift = day === 0 ? -6 : 1 - day;
  value.setDate(value.getDate() + mondayShift);
  return value;
}

function buildWeekDays(anchorDate) {
  const monday = startOfWeek(anchorDate);
  return Array.from({ length: 7 }, (_, index) => {
    const day = new Date(monday);
    day.setDate(monday.getDate() + index);
    return day;
  });
}

function parseEventDay(value) {
  const date = new Date(String(value).replace(' ', 'T'));
  if (Number.isNaN(date.getTime())) return '';
  return formatDate(date);
}

function dayHeading(date) {
  return date.toLocaleDateString('pt-BR', { weekday: 'short' }).replace('.', '');
}

function dayLabel(date) {
  return date.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' });
}

export function renderScheduleWeekView({ events, anchorDate, lookups, uiState = {} }) {
  const days = buildWeekDays(anchorDate);

  const groupedByDay = days.map((day) => {
    const key = formatDate(day);

    const items = events
      .filter((event) => parseEventDay(event.starts_at) === key)
      .sort((a, b) => String(a.starts_at).localeCompare(String(b.starts_at)));

    return { day, key, items };
  });

  const hasAnyEvent = groupedByDay.some((entry) => entry.items.length > 0);

  if (!hasAnyEvent) {
    const hasActiveFilters = Boolean(uiState?.hasActiveFilters);
    const title = hasActiveFilters ? 'Nenhum compromisso encontrado para este período.' : 'Nenhum compromisso nesta semana.';
    const description = hasActiveFilters
      ? 'Tente limpar ou ajustar os filtros para ampliar os resultados.'
      : 'Você pode mudar o período ou criar um novo evento.';

    return `
      <div class="empty-state schedule-empty-state">
        <div class="empty-state__icon"><i data-lucide="calendar-range" style="width:24px;height:24px;"></i></div>
        <div class="empty-state__title">${title}</div>
        <div class="empty-state__desc">${description}</div>
      </div>`;
  }

  return `
    <section class="schedule-week-view" aria-label="Visão semanal da agenda">
      <div class="schedule-week-grid">
        ${groupedByDay.map((entry) => `
          <article class="schedule-week-day">
            <header class="schedule-week-day__header">
              <span class="schedule-week-day__weekday">${dayHeading(entry.day)}</span>
              <span class="schedule-week-day__date">${dayLabel(entry.day)}</span>
            </header>

            <div class="schedule-week-day__events">
              ${entry.items.length
                ? entry.items.map((event) => renderScheduleEventCard(event, { lookups, variant: 'week' })).join('')
                : '<div class="schedule-week-day__empty">Sem eventos</div>'}
            </div>
          </article>`).join('')}
      </div>
    </section>`;
}
