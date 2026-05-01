import { renderScheduleDayView } from './schedule-day-view.js';
import { renderScheduleWeekView } from './schedule-week-view.js';
import { renderScheduleMonthView } from './schedule-month-view.js';

export const SCHEDULE_VIEWS = {
  day: 'day',
  week: 'week',
  month: 'month',
};

function cloneDate(value) {
  const copy = new Date(value);
  copy.setHours(0, 0, 0, 0);
  return copy;
}

function formatDate(date) {
  return `${String(date.getFullYear())}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
}

function getWeekRange(anchorDate) {
  const start = cloneDate(anchorDate);
  const weekday = start.getDay();
  const mondayShift = weekday === 0 ? -6 : 1 - weekday;
  start.setDate(start.getDate() + mondayShift);

  const end = cloneDate(start);
  end.setDate(start.getDate() + 6);

  return { start, end };
}

function getMonthRange(anchorDate) {
  const start = new Date(anchorDate.getFullYear(), anchorDate.getMonth(), 1);
  const end = new Date(anchorDate.getFullYear(), anchorDate.getMonth() + 1, 0);
  start.setHours(0, 0, 0, 0);
  end.setHours(0, 0, 0, 0);

  return { start, end };
}

export function getPeriodRange(view, anchorDate) {
  if (view === SCHEDULE_VIEWS.day) {
    const day = cloneDate(anchorDate);
    return { startDate: formatDate(day), endDate: formatDate(day) };
  }

  if (view === SCHEDULE_VIEWS.week) {
    const week = getWeekRange(anchorDate);
    return { startDate: formatDate(week.start), endDate: formatDate(week.end) };
  }

  const month = getMonthRange(anchorDate);
  return { startDate: formatDate(month.start), endDate: formatDate(month.end) };
}

export function getPeriodLabel(view, anchorDate) {
  if (view === SCHEDULE_VIEWS.day) {
    return anchorDate.toLocaleDateString('pt-BR', {
      weekday: 'long',
      day: '2-digit',
      month: 'long',
      year: 'numeric',
    });
  }

  if (view === SCHEDULE_VIEWS.week) {
    const week = getWeekRange(anchorDate);
    const start = week.start.toLocaleDateString('pt-BR', { day: '2-digit', month: 'short' });
    const end = week.end.toLocaleDateString('pt-BR', { day: '2-digit', month: 'short', year: 'numeric' });

    return `${start} - ${end}`;
  }

  return anchorDate.toLocaleDateString('pt-BR', { month: 'long', year: 'numeric' });
}

export function shiftPeriod(view, anchorDate, step) {
  const next = new Date(anchorDate);

  if (view === SCHEDULE_VIEWS.day) {
    next.setDate(next.getDate() + step);
    return next;
  }

  if (view === SCHEDULE_VIEWS.week) {
    next.setDate(next.getDate() + (7 * step));
    return next;
  }

  next.setMonth(next.getMonth() + step);
  return next;
}

export function renderScheduleCalendarView({
  view,
  events,
  anchorDate,
  lookups,
  uiState = {},
}) {
  if (view === SCHEDULE_VIEWS.day) {
    return renderScheduleDayView({ events, anchorDate, lookups, uiState });
  }

  if (view === SCHEDULE_VIEWS.week) {
    return renderScheduleWeekView({ events, anchorDate, lookups, uiState });
  }

  return renderScheduleMonthView({ events, anchorDate, lookups, uiState });
}
