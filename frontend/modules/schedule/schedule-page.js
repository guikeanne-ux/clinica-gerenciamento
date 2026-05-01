import { toast } from '../../core/js/toast.js';
import { permissionService } from '../../core/auth/permission-service.js';
import { scheduleService } from './schedule-service.js';
import {
  renderScheduleCalendarView,
  getPeriodLabel,
  getPeriodRange,
  shiftPeriod,
  SCHEDULE_VIEWS,
} from './schedule-calendar.js';
import { renderScheduleMonthDayEventsList } from './schedule-month-view.js';
import {
  renderScheduleFilters,
  readScheduleFiltersFromDom,
  hasActiveScheduleFilters,
} from './schedule-filters.js';
import {
  renderScheduleModals,
  renderScheduleEventDetails,
  renderScheduleEventForm,
  renderScheduleCancelForm,
  renderScheduleRescheduleForm,
  openScheduleModal,
  closeScheduleModal,
  formatDateTimeLocalInput,
} from './schedule-event-modal.js';

let _container = null;
let _state = null;
let _loadToken = 0;

function buildPermissions() {
  return {
    canCreate: permissionService.has('schedule.create'),
    canUpdate: permissionService.has('schedule.update'),
    canCancel: permissionService.has('schedule.cancel'),
    canDelete: permissionService.has('schedule.delete'),
    canOverrideConflict: permissionService.has('schedule.override_conflict'),
    canCreateAttendance: permissionService.has('schedule.create_attendance'),
    attendanceModuleAvailable: false,
    canConfirm: permissionService.has('schedule.update'),
  };
}

function buildInitialState() {
  return {
    view: SCHEDULE_VIEWS.week,
    anchorDate: new Date(),
    events: [],
    eventTypes: [],
    professionals: [],
    patients: [],
    filters: {
      professional_uuid: '',
      patient_uuid: '',
      event_type_uuid: '',
      status: '',
      category: '',
      start_date: '',
      end_date: '',
    },
    loading: true,
    errorMessage: '',
    filtersCollapsed: window.innerWidth <= 900,
    permissions: buildPermissions(),
    selectedEventUuid: '',
    eventForm: null,
    cancelForm: null,
    rescheduleForm: null,
    submitting: false,
  };
}

function mapByUuid(items) {
  return items.reduce((acc, item) => {
    if (item?.uuid) acc[item.uuid] = item;
    return acc;
  }, {});
}

function getLookups() {
  return {
    eventTypesByUuid: mapByUuid(_state.eventTypes),
    professionalsByUuid: mapByUuid(_state.professionals),
    patientsByUuid: mapByUuid(_state.patients),
  };
}

function getEventByUuid(uuid) {
  return _state.events.find((item) => item.uuid === uuid) || null;
}

function parseDate(value) {
  const date = new Date(String(value || '').replace(' ', 'T'));
  return Number.isNaN(date.getTime()) ? null : date;
}

function pad2(value) {
  return String(value).padStart(2, '0');
}

function toLocalDateTime(date) {
  return [
    date.getFullYear(),
    pad2(date.getMonth() + 1),
    pad2(date.getDate()),
  ].join('-') + `T${pad2(date.getHours())}:${pad2(date.getMinutes())}`;
}

function localDateTimeFromAnchor(offsetHours = 0) {
  const date = new Date(_state.anchorDate);
  date.setHours(9 + offsetHours, 0, 0, 0);
  return toLocalDateTime(date);
}

function toApiDateTime(localDateTime, fallbackSeconds = '00') {
  const date = parseDate(localDateTime);
  if (!date) return '';

  return [
    `${date.getFullYear()}-${pad2(date.getMonth() + 1)}-${pad2(date.getDate())}`,
    `${pad2(date.getHours())}:${pad2(date.getMinutes())}:${fallbackSeconds}`,
  ].join(' ');
}

function parseIsoDate(value) {
  const raw = String(value || '').trim();
  if (!raw) return null;

  const parsed = new Date(`${raw}T00:00:00`);
  return Number.isNaN(parsed.getTime()) ? null : parsed;
}

function normalizeBackendErrors(errors = []) {
  const map = {};
  if (!Array.isArray(errors)) return map;

  errors.forEach((item) => {
    const originalField = String(item?.field || '').trim();
    const message = String(item?.message || '').trim();
    if (!originalField || !message) return;

    const field = ({
      'recurrence.until': 'recurrence_until',
      'recurrence.interval': 'recurrence_interval',
      'recurrence.week_days': 'recurrence_week_days',
    })[originalField] || originalField;

    if (field && message) {
      map[field] = message;
    }
  });

  return map;
}

function parseRecurrenceRule(rule) {
  if (!rule || typeof rule !== 'string') return null;

  try {
    const parsed = JSON.parse(rule);
    if (parsed && parsed.frequency === 'weekly') {
      return {
        enabled: true,
        frequency: 'weekly',
        until: String(parsed.until || ''),
        interval: Number(parsed.interval || 1),
        week_days: Array.isArray(parsed.week_days) ? parsed.week_days : [],
      };
    }
  } catch {
    // ignore malformed recurrence
  }

  return null;
}

function mergeDescription(description, observations) {
  const left = String(description || '').trim();
  const right = String(observations || '').trim();

  if (!left && !right) return '';
  if (left && !right) return left;
  if (!left && right) return `Observações: ${right}`;

  return `${left}\n\nObservações: ${right}`;
}

function splitDescriptionAndObservations(description) {
  const text = String(description || '').trim();
  if (!text) return { description: '', observations: '' };

  const marker = '\n\nObservações:';
  const markerIndex = text.indexOf(marker);

  if (markerIndex === -1) {
    return { description: text, observations: '' };
  }

  return {
    description: text.slice(0, markerIndex).trim(),
    observations: text.slice(markerIndex + marker.length).trim(),
  };
}

function draftFromEvent(event) {
  const recurrence = parseRecurrenceRule(event?.recurrence_rule || '');
  const descriptionParts = splitDescriptionAndObservations(event?.description || '');

  return {
    title: event?.title || '',
    description: descriptionParts.description,
    observations: descriptionParts.observations,
    event_type_uuid: event?.event_type_uuid || '',
    patient_uuid: event?.patient_uuid || '',
    professional_uuid: event?.professional_uuid || '',
    starts_at: formatDateTimeLocalInput(event?.starts_at || ''),
    ends_at: formatDateTimeLocalInput(event?.ends_at || ''),
    all_day: Boolean(event?.all_day),
    status: event?.status || 'agendado',
    room_or_location: event?.room_or_location || '',
    is_attendance: Boolean(event?.is_attendance),
    recurrence: recurrence || {
      enabled: false,
      frequency: 'weekly',
      until: '',
      interval: 1,
      week_days: [],
    },
  };
}

function emptyDraft() {
  const startsAt = localDateTimeFromAnchor(0);
  const endsAt = localDateTimeFromAnchor(1);

  return {
    title: '',
    description: '',
    observations: '',
    event_type_uuid: '',
    patient_uuid: '',
    professional_uuid: '',
    starts_at: startsAt,
    ends_at: endsAt,
    all_day: false,
    status: 'agendado',
    room_or_location: '',
    is_attendance: false,
    recurrence: {
      enabled: false,
      frequency: 'weekly',
      until: '',
      interval: 1,
      week_days: [],
    },
  };
}

function selectedEventType(draft) {
  return _state.eventTypes.find((item) => item.uuid === draft.event_type_uuid) || null;
}

function recurrenceWeekdayFromStart(startsAtLocal) {
  const parsed = parseDate(startsAtLocal);
  if (!parsed) return 'MO';

  const day = parsed.getDay();
  return ['SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA'][day] || 'MO';
}

function validateFormDraft(draft) {
  const errors = {};

  if (!String(draft.title || '').trim()) {
    errors.title = 'Título é obrigatório.';
  }

  if (!String(draft.starts_at || '').trim()) {
    errors.starts_at = 'Data/hora de início é obrigatória.';
  }

  if (!String(draft.ends_at || '').trim()) {
    errors.ends_at = 'Data/hora de fim é obrigatória.';
  }

  const starts = parseDate(draft.starts_at);
  const ends = parseDate(draft.ends_at);

  if (starts && ends && ends.getTime() <= starts.getTime()) {
    errors.ends_at = 'A data/hora final deve ser posterior ao início.';
  }

  if (draft.is_attendance && !String(draft.patient_uuid || '').trim()) {
    errors.patient_uuid = 'Atendimento agendado exige paciente.';
  }

  if (draft.is_attendance && !String(draft.professional_uuid || '').trim()) {
    errors.professional_uuid = 'Atendimento agendado exige profissional.';
  }

  const eventType = selectedEventType(draft);
  if (eventType?.requires_patient && !String(draft.patient_uuid || '').trim()) {
    errors.patient_uuid = 'Este tipo de evento exige paciente.';
  }

  if (eventType?.requires_professional && !String(draft.professional_uuid || '').trim()) {
    errors.professional_uuid = 'Este tipo de evento exige profissional.';
  }

  if (draft.recurrence?.enabled) {
    if (!String(draft.recurrence.until || '').trim()) {
      errors.recurrence_until = 'Data final da repetição é obrigatória.';
    }

    const interval = Number(draft.recurrence.interval || 1);
    if (!Number.isFinite(interval) || interval < 1 || interval > 12) {
      errors.recurrence_interval = 'Intervalo deve ser entre 1 e 12 semanas.';
    }

    if (!Array.isArray(draft.recurrence.week_days) || draft.recurrence.week_days.length === 0) {
      errors.recurrence_week_days = 'Selecione ao menos um dia da semana.';
    }
  }

  return errors;
}

function buildEventPayloadFromDraft(draft) {
  const allDay = Boolean(draft.all_day);

  let startsAt = toApiDateTime(draft.starts_at);
  let endsAt = toApiDateTime(draft.ends_at);

  if (allDay) {
    const startDatePart = String(draft.starts_at || '').slice(0, 10);
    const endDatePart = String(draft.ends_at || '').slice(0, 10) || startDatePart;
    startsAt = `${startDatePart} 00:00:00`;
    endsAt = `${endDatePart} 23:59:59`;
  }

  const payload = {
    title: String(draft.title || '').trim(),
    description: mergeDescription(draft.description, draft.observations),
    event_type_uuid: String(draft.event_type_uuid || '').trim() || null,
    patient_uuid: String(draft.patient_uuid || '').trim() || null,
    professional_uuid: String(draft.professional_uuid || '').trim() || null,
    is_attendance: Boolean(draft.is_attendance),
    starts_at: startsAt,
    ends_at: endsAt,
    all_day: allDay,
    status: String(draft.status || 'agendado').trim() || 'agendado',
    origin: 'manual',
    room_or_location: String(draft.room_or_location || '').trim() || null,
  };

  if (draft.recurrence?.enabled) {
    payload.recurrence = {
      frequency: 'weekly',
      week_days: Array.from(new Set(draft.recurrence.week_days || [])),
      until: String(draft.recurrence.until || '').trim(),
      interval: Math.max(1, Number(draft.recurrence.interval || 1)),
    };
  }

  return payload;
}

function renderPageShell() {
  if (!_container) return;

  const activeView = _state.view;

  _container.innerHTML = `
    <div class="schedule-page">
      <div class="page-header">
        <div class="page-header__info">
          <h1>Agenda</h1>
          <p class="subtitle">Acompanhe compromissos por dia, semana e mês com filtros rápidos.</p>
        </div>

        <div class="page-header__actions schedule-page__actions">
          ${_state.permissions.canCreate ? `
            <button type="button" class="btn btn-primary btn-md" id="schedule-btn-new-event">
              <i data-lucide="plus" style="width:15px;height:15px;"></i>
              Novo evento
            </button>` : ''}
        </div>
      </div>

      <section class="section schedule-controls">
        <div class="schedule-controls__left" role="tablist" aria-label="Selecionar visão da agenda">
          <button type="button" class="btn btn-secondary btn-sm${activeView === 'day' ? ' is-active' : ''}" data-schedule-view="day">Dia</button>
          <button type="button" class="btn btn-secondary btn-sm${activeView === 'week' ? ' is-active' : ''}" data-schedule-view="week">Semana</button>
          <button type="button" class="btn btn-secondary btn-sm${activeView === 'month' ? ' is-active' : ''}" data-schedule-view="month">Mês</button>
        </div>

        <div class="schedule-controls__right">
          <button type="button" class="btn btn-ghost btn-sm" id="schedule-nav-prev">Anterior</button>
          <button type="button" class="btn btn-outline btn-sm" id="schedule-nav-today">Hoje</button>
          <button type="button" class="btn btn-ghost btn-sm" id="schedule-nav-next">Próximo</button>
          <strong id="schedule-period-label" class="schedule-controls__period"></strong>
        </div>
      </section>

      <div id="schedule-filters-slot"></div>

      <section class="section schedule-calendar-section">
        <div id="schedule-calendar-root"></div>
      </section>

      ${renderScheduleModals()}
    </div>`;

  bindStaticEvents();
  renderFilters();
  renderCalendarArea();
  window.lucide?.createIcons();
}

function renderFilters() {
  const slot = document.getElementById('schedule-filters-slot');
  if (!slot) return;

  slot.innerHTML = renderScheduleFilters({
    filters: _state.filters,
    professionals: _state.professionals,
    patients: _state.patients,
    eventTypes: _state.eventTypes,
    collapsed: _state.filtersCollapsed,
  });

  bindFilterEvents();
}

function renderErrorState() {
  return `
    <div class="empty-state schedule-empty-state schedule-empty-state--error">
      <div class="empty-state__icon"><i data-lucide="alert-triangle" style="width:24px;height:24px;"></i></div>
      <div class="empty-state__title">Não foi possível carregar a agenda</div>
      <div class="empty-state__desc">${_state.errorMessage || 'Tente novamente em alguns instantes.'}</div>
      <div class="empty-state__action">
        <button type="button" class="btn btn-secondary btn-md" id="schedule-btn-retry">Tentar novamente</button>
      </div>
    </div>`;
}

function renderLoadingState() {
  return `
    <div class="schedule-loading-grid" aria-hidden="true">
      <div class="skeleton schedule-loading-grid__item"></div>
      <div class="skeleton schedule-loading-grid__item"></div>
      <div class="skeleton schedule-loading-grid__item"></div>
      <div class="skeleton schedule-loading-grid__item"></div>
      <div class="skeleton schedule-loading-grid__item"></div>
      <div class="skeleton schedule-loading-grid__item"></div>
    </div>`;
}

function renderCalendarArea() {
  const root = document.getElementById('schedule-calendar-root');
  const periodLabel = document.getElementById('schedule-period-label');
  if (!root || !periodLabel) return;

  periodLabel.textContent = getPeriodLabel(_state.view, _state.anchorDate);

  if (_state.loading) {
    root.innerHTML = renderLoadingState();
    return;
  }

  if (_state.errorMessage) {
    root.innerHTML = renderErrorState();
    document.getElementById('schedule-btn-retry')?.addEventListener('click', () => {
      loadEvents();
    });
    window.lucide?.createIcons();
    return;
  }

  root.innerHTML = renderScheduleCalendarView({
    view: _state.view,
    events: _state.events,
    anchorDate: _state.anchorDate,
    lookups: getLookups(),
    uiState: {
      hasActiveFilters: hasActiveScheduleFilters(_state.filters),
    },
  });

  window.lucide?.createIcons();
}

function bindStaticEvents() {
  document.getElementById('schedule-btn-new-event')?.addEventListener('click', () => {
    openEventForm('create');
  });

  document.getElementById('schedule-nav-prev')?.addEventListener('click', () => {
    _state.anchorDate = shiftPeriod(_state.view, _state.anchorDate, -1);
    loadEvents();
  });

  document.getElementById('schedule-nav-next')?.addEventListener('click', () => {
    _state.anchorDate = shiftPeriod(_state.view, _state.anchorDate, 1);
    loadEvents();
  });

  document.getElementById('schedule-nav-today')?.addEventListener('click', () => {
    _state.anchorDate = new Date();
    loadEvents();
  });

  _container.querySelectorAll('[data-schedule-view]').forEach((button) => {
    button.addEventListener('click', () => {
      const nextView = button.getAttribute('data-schedule-view');
      if (!nextView || nextView === _state.view) return;

      _state.view = nextView;
      renderPageShell();
      loadEvents();
    });
  });

  _container.addEventListener('click', onContainerClick);
}

function bindFilterEvents() {
  document.getElementById('schedule-toggle-filters')?.addEventListener('click', () => {
    _state.filtersCollapsed = !_state.filtersCollapsed;
    renderFilters();
  });

  document.getElementById('schedule-apply-filters')?.addEventListener('click', () => {
    const nextFilters = readScheduleFiltersFromDom();
    const startDate = parseIsoDate(nextFilters.start_date);
    const endDate = parseIsoDate(nextFilters.end_date);

    if (startDate && endDate && startDate.getTime() > endDate.getTime()) {
      toast.warning('A data inicial deve ser menor ou igual à data final.');
      return;
    }

    _state.filters = nextFilters;
    loadEvents();
  });

  document.getElementById('schedule-clear-filters')?.addEventListener('click', () => {
    _state.filters = {
      professional_uuid: '',
      patient_uuid: '',
      event_type_uuid: '',
      status: '',
      category: '',
      start_date: '',
      end_date: '',
    };
    renderFilters();
    loadEvents();
  });
}

async function onContainerClick(event) {
  const closeButton = event.target.closest('[data-schedule-modal-close]');
  if (closeButton) {
    closeScheduleModal(closeButton.closest('.modal'));
    return;
  }

  const clickedModalBackdrop = event.target.classList?.contains('modal');
  if (clickedModalBackdrop) {
    closeScheduleModal(event.target);
    return;
  }

  const detailAction = event.target.closest('[data-schedule-detail-action]');
  if (detailAction) {
    const action = detailAction.getAttribute('data-schedule-detail-action');
    if (action) {
      await handleDetailsAction(action);
    }
    return;
  }

  const dayMoreButton = event.target.closest('[data-schedule-day-more]');
  if (dayMoreButton) {
    const date = dayMoreButton.getAttribute('data-schedule-day-more');
    if (date) {
      openMonthDayEventsModal(date);
    }
    return;
  }

  const dayEventButton = event.target.closest('[data-schedule-day-event]');
  if (dayEventButton) {
    const eventUuidFromDayList = dayEventButton.getAttribute('data-schedule-day-event');
    if (eventUuidFromDayList) {
      closeScheduleModal(document.getElementById('schedule-day-events-modal'));
      openEventDetails(eventUuidFromDayList);
    }
    return;
  }

  const eventCard = event.target.closest('[data-schedule-event]');
  if (!eventCard) return;

  const eventUuid = eventCard.getAttribute('data-schedule-event');
  if (!eventUuid) return;

  openEventDetails(eventUuid);
}

function openMonthDayEventsModal(date) {
  const body = document.getElementById('schedule-day-events-body');
  const title = document.getElementById('schedule-day-events-title');
  if (!body || !title) return;

  const dayEvents = _state.events
    .filter((event) => String(event.starts_at || '').slice(0, 10) === date)
    .sort((left, right) => String(left.starts_at).localeCompare(String(right.starts_at)));

  const parsedDate = new Date(`${date}T00:00:00`);
  const dayLabel = Number.isNaN(parsedDate.getTime())
    ? date
    : parsedDate.toLocaleDateString('pt-BR', { day: '2-digit', month: 'long', year: 'numeric' });

  title.textContent = `Compromissos de ${dayLabel}`;
  body.innerHTML = renderScheduleMonthDayEventsList({
    date,
    events: dayEvents,
    lookups: getLookups(),
  });

  openScheduleModal('schedule-day-events-modal');
}

function readSelectedWeekDays() {
  const checked = Array.from(document.querySelectorAll('input[name="schedule-recurrence-weekday"]:checked'));
  return checked.map((input) => String(input.value || ''));
}

function writeFormDraftFromDom() {
  if (!_state.eventForm) return;

  const draft = _state.eventForm.draft;
  draft.title = document.getElementById('schedule-form-title')?.value || '';
  draft.description = document.getElementById('schedule-form-description')?.value || '';
  draft.observations = document.getElementById('schedule-form-observations')?.value || '';
  draft.event_type_uuid = document.getElementById('schedule-form-event-type')?.value || '';
  draft.patient_uuid = document.getElementById('schedule-form-patient')?.value || '';
  draft.professional_uuid = document.getElementById('schedule-form-professional')?.value || '';
  draft.starts_at = document.getElementById('schedule-form-starts-at')?.value || '';
  draft.ends_at = document.getElementById('schedule-form-ends-at')?.value || '';
  draft.all_day = Boolean(document.getElementById('schedule-form-all-day')?.checked);
  draft.status = document.getElementById('schedule-form-status')?.value || 'agendado';
  draft.room_or_location = document.getElementById('schedule-form-room')?.value || '';
  draft.is_attendance = Boolean(document.getElementById('schedule-form-is-attendance')?.checked);

  draft.recurrence.enabled = Boolean(document.getElementById('schedule-form-recurrence-enabled')?.checked);
  draft.recurrence.until = document.getElementById('schedule-form-recurrence-until')?.value || '';
  draft.recurrence.interval = Number(document.getElementById('schedule-form-recurrence-interval')?.value || 1);
  draft.recurrence.week_days = readSelectedWeekDays();
}

function renderEventFormModal() {
  const body = document.getElementById('schedule-event-form-body');
  const titleEl = document.getElementById('schedule-event-form-title');
  if (!body || !titleEl || !_state.eventForm) return;

  titleEl.textContent = _state.eventForm.mode === 'edit' ? 'Editar compromisso' : 'Novo compromisso';

  body.innerHTML = renderScheduleEventForm({
    mode: _state.eventForm.mode,
    draft: _state.eventForm.draft,
    errors: _state.eventForm.errors,
    eventTypes: _state.eventTypes,
    professionals: _state.professionals,
    patients: _state.patients,
  });

  bindEventFormEvents();
  window.lucide?.createIcons();
}

function toggleRecurrencePanel(forceValue = null) {
  const section = document.querySelector('.schedule-recurrence');
  const toggleButton = document.getElementById('schedule-toggle-recurrence');
  if (!section || !toggleButton || !_state.eventForm) return;

  const currentEnabled = Boolean(_state.eventForm.draft.recurrence.enabled);
  const enabled = forceValue === null ? !currentEnabled : Boolean(forceValue);

  _state.eventForm.draft.recurrence.enabled = enabled;

  section.classList.toggle('is-collapsed', !enabled);
  toggleButton.textContent = enabled ? 'Ocultar repetição' : 'Repetição';

  const enabledInput = document.getElementById('schedule-form-recurrence-enabled');
  if (enabledInput) enabledInput.checked = enabled;

  if (enabled && (!_state.eventForm.draft.recurrence.week_days || !_state.eventForm.draft.recurrence.week_days.length)) {
    _state.eventForm.draft.recurrence.week_days = [recurrenceWeekdayFromStart(_state.eventForm.draft.starts_at)];
    renderEventFormModal();
  }
}

function syncRequiredHints() {
  const typeSelect = document.getElementById('schedule-form-event-type');
  const patientLabel = document.querySelector('label[for="schedule-form-patient"]');
  const professionalLabel = document.querySelector('label[for="schedule-form-professional"]');
  const attendanceToggle = document.getElementById('schedule-form-is-attendance');

  if (!typeSelect || !patientLabel || !professionalLabel) return;

  const selectedOption = typeSelect.options[typeSelect.selectedIndex];
  const isAttendance = Boolean(attendanceToggle?.checked);
  const requiresPatient = selectedOption?.dataset?.requiresPatient === '1';
  const requiresProfessional = selectedOption?.dataset?.requiresProfessional === '1';

  patientLabel.textContent = requiresPatient || isAttendance ? 'Paciente *' : 'Paciente';
  professionalLabel.textContent = requiresProfessional || isAttendance ? 'Profissional *' : 'Profissional';
}

function bindEventFormEvents() {
  const form = document.getElementById('schedule-event-form');
  if (!form) return;

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    await submitEventForm();
  });

  document.getElementById('schedule-form-event-type')?.addEventListener('change', () => {
    writeFormDraftFromDom();
    syncRequiredHints();
  });

  document.getElementById('schedule-form-is-attendance')?.addEventListener('change', () => {
    writeFormDraftFromDom();
    syncRequiredHints();
  });

  document.getElementById('schedule-form-starts-at')?.addEventListener('change', () => {
    writeFormDraftFromDom();
  });

  document.getElementById('schedule-form-ends-at')?.addEventListener('change', () => {
    writeFormDraftFromDom();
  });

  document.getElementById('schedule-toggle-recurrence')?.addEventListener('click', () => {
    writeFormDraftFromDom();
    toggleRecurrencePanel();
  });

  document.getElementById('schedule-form-recurrence-enabled')?.addEventListener('change', (e) => {
    writeFormDraftFromDom();
    toggleRecurrencePanel(Boolean(e.target.checked));
  });

  syncRequiredHints();
}

async function submitEventForm() {
  if (_state.submitting || !_state.eventForm) return;

  writeFormDraftFromDom();
  const draft = _state.eventForm.draft;

  if (draft.recurrence.enabled && (!draft.recurrence.week_days || draft.recurrence.week_days.length === 0)) {
    draft.recurrence.week_days = [recurrenceWeekdayFromStart(draft.starts_at)];
  }

  const localErrors = validateFormDraft(draft);
  if (Object.keys(localErrors).length) {
    _state.eventForm.errors = localErrors;
    renderEventFormModal();
    toast.warning('Revise os campos obrigatórios do formulário.');
    return;
  }

  const payload = buildEventPayloadFromDraft(draft);

  _state.submitting = true;
  const res = _state.eventForm.mode === 'edit'
    ? await scheduleService.updateEvent(_state.eventForm.eventUuid, payload)
    : await scheduleService.createEvent(payload);
  _state.submitting = false;

  if (!res.success) {
    _state.eventForm.errors = normalizeBackendErrors(res.errors);
    renderEventFormModal();

    if (Object.keys(_state.eventForm.errors).length) {
      toast.warning('Existem campos com erro. Revise e tente novamente.');
    } else {
      toast.error(res.message || 'Não foi possível salvar o compromisso.');
    }

    return;
  }

  const mode = _state.eventForm.mode;
  closeScheduleModal(document.getElementById('schedule-event-form-modal'));
  _state.eventForm = null;

  toast.success(mode === 'edit' ? 'Compromisso atualizado com sucesso.' : 'Compromisso criado com sucesso.');
  toast.info('Agenda atualizada.');
  await loadEvents();
}

async function openEventForm(mode, eventUuid = '') {
  if (mode === 'create' && !_state.permissions.canCreate) {
    toast.error('Você não tem permissão para criar eventos.');
    return;
  }

  if (mode === 'edit' && !_state.permissions.canUpdate) {
    toast.error('Você não tem permissão para editar eventos.');
    return;
  }

  if (mode === 'edit') {
    const getRes = await scheduleService.getEvent(eventUuid);
    if (!getRes.success || !getRes.data) {
      toast.error(getRes.message || 'Não foi possível carregar o evento para edição.');
      return;
    }

    _state.eventForm = {
      mode,
      eventUuid,
      draft: draftFromEvent(getRes.data),
      errors: {},
    };
  } else {
    _state.eventForm = {
      mode,
      eventUuid: '',
      draft: emptyDraft(),
      errors: {},
    };
  }

  renderEventFormModal();
  openScheduleModal('schedule-event-form-modal');
}

function openEventDetails(eventUuid) {
  const selected = getEventByUuid(eventUuid);
  if (!selected) return;

  _state.selectedEventUuid = eventUuid;

  const detailsBody = document.getElementById('schedule-event-details-body');
  if (!detailsBody) return;

  detailsBody.innerHTML = renderScheduleEventDetails(selected, getLookups(), _state.permissions);
  openScheduleModal('schedule-event-details-modal');
}

async function updateDetailsModalIfOpen() {
  const detailsModal = document.getElementById('schedule-event-details-modal');
  if (!detailsModal || !detailsModal.classList.contains('open') || !_state.selectedEventUuid) return;

  const event = getEventByUuid(_state.selectedEventUuid);
  if (!event) {
    closeScheduleModal(detailsModal);
    return;
  }

  const detailsBody = document.getElementById('schedule-event-details-body');
  if (!detailsBody) return;

  detailsBody.innerHTML = renderScheduleEventDetails(event, getLookups(), _state.permissions);
}

function renderCancelModal() {
  const body = document.getElementById('schedule-event-cancel-body');
  if (!body || !_state.cancelForm) return;

  body.innerHTML = renderScheduleCancelForm({
    reason: _state.cancelForm.reason,
    errors: _state.cancelForm.errors,
  });

  const form = document.getElementById('schedule-event-cancel-form');
  form?.addEventListener('submit', async (e) => {
    e.preventDefault();
    await submitCancelForm();
  });
}

function openCancelModal() {
  _state.cancelForm = {
    reason: '',
    errors: {},
  };

  renderCancelModal();
  openScheduleModal('schedule-event-cancel-modal');
}

async function submitCancelForm() {
  if (!_state.selectedEventUuid || !_state.cancelForm) return;

  const reasonInput = document.getElementById('schedule-cancel-reason');
  _state.cancelForm.reason = reasonInput?.value || '';

  const reason = String(_state.cancelForm.reason || '').trim();
  if (!reason) {
    _state.cancelForm.errors = { cancel_reason: 'Motivo do cancelamento é obrigatório.' };
    renderCancelModal();
    toast.warning('Informe o motivo do cancelamento.');
    return;
  }

  if (!confirm('Confirma o cancelamento deste compromisso?')) {
    return;
  }

  const res = await scheduleService.cancelEvent(_state.selectedEventUuid, reason);
  if (!res.success) {
    _state.cancelForm.errors = normalizeBackendErrors(res.errors);
    renderCancelModal();
    toast.error(res.message || 'Não foi possível cancelar o compromisso.');
    return;
  }

  closeScheduleModal(document.getElementById('schedule-event-cancel-modal'));
  toast.success('Compromisso cancelado com sucesso.');
  toast.info('Agenda atualizada.');
  await loadEvents();
  await updateDetailsModalIfOpen();
}

function renderRescheduleModal() {
  const body = document.getElementById('schedule-event-reschedule-body');
  if (!body || !_state.rescheduleForm) return;

  const event = getEventByUuid(_state.selectedEventUuid);
  if (!event) return;

  body.innerHTML = renderScheduleRescheduleForm({
    event,
    values: _state.rescheduleForm.values,
    errors: _state.rescheduleForm.errors,
  });

  const form = document.getElementById('schedule-event-reschedule-form');
  form?.addEventListener('submit', async (e) => {
    e.preventDefault();
    await submitRescheduleForm();
  });
}

function openRescheduleModal() {
  const event = getEventByUuid(_state.selectedEventUuid);
  if (!event) return;

  _state.rescheduleForm = {
    values: {
      starts_at: formatDateTimeLocalInput(event.starts_at),
      ends_at: formatDateTimeLocalInput(event.ends_at),
      reason: '',
    },
    errors: {},
  };

  renderRescheduleModal();
  openScheduleModal('schedule-event-reschedule-modal');
}

async function submitRescheduleForm() {
  if (!_state.selectedEventUuid || !_state.rescheduleForm) return;

  _state.rescheduleForm.values = {
    starts_at: document.getElementById('schedule-reschedule-starts-at')?.value || '',
    ends_at: document.getElementById('schedule-reschedule-ends-at')?.value || '',
    reason: document.getElementById('schedule-reschedule-reason')?.value || '',
  };

  const errors = {};
  if (!_state.rescheduleForm.values.starts_at) {
    errors.starts_at = 'Novo início é obrigatório.';
  }

  if (!_state.rescheduleForm.values.ends_at) {
    errors.ends_at = 'Novo fim é obrigatório.';
  }

  const start = parseDate(_state.rescheduleForm.values.starts_at);
  const end = parseDate(_state.rescheduleForm.values.ends_at);
  if (start && end && end.getTime() <= start.getTime()) {
    errors.ends_at = 'A data/hora final deve ser posterior ao início.';
  }

  if (Object.keys(errors).length) {
    _state.rescheduleForm.errors = errors;
    renderRescheduleModal();
    toast.warning('Revise os dados da remarcação.');
    return;
  }

  const payload = {
    starts_at: toApiDateTime(_state.rescheduleForm.values.starts_at),
    ends_at: toApiDateTime(_state.rescheduleForm.values.ends_at),
    reason: String(_state.rescheduleForm.values.reason || '').trim() || null,
  };

  const res = await scheduleService.rescheduleEvent(_state.selectedEventUuid, payload);
  if (!res.success) {
    _state.rescheduleForm.errors = normalizeBackendErrors(res.errors);
    renderRescheduleModal();

    toast.error(res.message || 'Não foi possível remarcar o compromisso.');

    return;
  }

  closeScheduleModal(document.getElementById('schedule-event-reschedule-modal'));
  toast.success('Compromisso remarcado com sucesso.');
  toast.info('Agenda atualizada.');
  await loadEvents();
  await updateDetailsModalIfOpen();
}

async function handleSimpleAction({
  confirmMessage,
  request,
  successMessage,
  fallbackError,
}) {
  if (!_state.selectedEventUuid) return;

  if (confirmMessage && !confirm(confirmMessage)) {
    return;
  }

  const res = await request(_state.selectedEventUuid);
  if (!res.success) {
    toast.error(res.message || fallbackError);
    return;
  }

  toast.success(successMessage);
  toast.info('Agenda atualizada.');
  await loadEvents();
  await updateDetailsModalIfOpen();
}

async function handleDetailsAction(action) {
  const eventUuid = _state.selectedEventUuid;
  if (!eventUuid) return;

  if (action === 'edit') {
    await openEventForm('edit', eventUuid);
    return;
  }

  if (action === 'cancel') {
    openCancelModal();
    return;
  }

  if (action === 'reschedule') {
    openRescheduleModal();
    return;
  }

  if (action === 'absence') {
    await handleSimpleAction({
      confirmMessage: 'Confirmar marcação de falta para este compromisso?',
      request: (uuid) => scheduleService.markAbsence(uuid),
      successMessage: 'Paciente faltou ao compromisso.',
      fallbackError: 'Não foi possível marcar falta.',
    });
    return;
  }

  if (action === 'confirm') {
    await handleSimpleAction({
      confirmMessage: 'Confirmar este compromisso?',
      request: (uuid) => scheduleService.confirmEvent(uuid),
      successMessage: 'Compromisso confirmado com sucesso.',
      fallbackError: 'Não foi possível confirmar o compromisso.',
    });
    return;
  }

  if (action === 'done') {
    await handleSimpleAction({
      confirmMessage: 'Marcar este compromisso como realizado?',
      request: (uuid) => scheduleService.markDone(uuid),
      successMessage: 'Compromisso marcado como realizado.',
      fallbackError: 'Não foi possível marcar como realizado.',
    });
    return;
  }

  if (action === 'delete') {
    if (!confirm('Confirma a exclusão lógica deste compromisso?')) return;

    const res = await scheduleService.deleteEvent(eventUuid);
    if (!res.success) {
      toast.error(res.message || 'Não foi possível excluir o compromisso.');
      return;
    }

    closeScheduleModal(document.getElementById('schedule-event-details-modal'));
    toast.success('Compromisso excluído com sucesso.');
    toast.info('Agenda atualizada.');
    await loadEvents();
    return;
  }

  if (action === 'attendance') {
    toast.info('Atendimento será liberado na próxima etapa.');
  }
}

async function loadReferenceData() {
  const calls = [scheduleService.listEventTypes()];

  if (permissionService.has('professionals.view')) {
    calls.push(scheduleService.listProfessionals());
  } else {
    calls.push(Promise.resolve({ success: true, data: [] }));
  }

  if (permissionService.has('patients.view')) {
    calls.push(scheduleService.listPatients());
  } else {
    calls.push(Promise.resolve({ success: true, data: [] }));
  }

  const [typesRes, professionalsRes, patientsRes] = await Promise.all(calls);

  _state.eventTypes = typesRes.success ? (typesRes.data || []) : [];
  _state.professionals = professionalsRes.success ? (professionalsRes.data || []) : [];
  _state.patients = patientsRes.success ? (patientsRes.data || []) : [];

  if (!typesRes.success) {
    toast.error(typesRes.message || 'Não foi possível carregar os tipos de evento.');
  }

  if (!professionalsRes.success) {
    toast.error(professionalsRes.message || 'Não foi possível carregar os profissionais para filtro.');
  }

  if (!patientsRes.success) {
    toast.error(patientsRes.message || 'Não foi possível carregar os pacientes para filtro.');
  }
}

async function loadEvents() {
  const token = ++_loadToken;

  _state.loading = true;
  _state.errorMessage = '';
  renderCalendarArea();

  const range = getPeriodRange(_state.view, _state.anchorDate);

  const params = {
    start_date: range.startDate,
    end_date: range.endDate,
    sort: 'starts_at',
    direction: 'asc',
    ..._state.filters,
  };

  const res = await scheduleService.listEvents(params);

  if (token !== _loadToken) return;

  if (!res.success) {
    _state.loading = false;
    _state.events = [];
    _state.errorMessage = res.message || 'Não foi possível carregar os eventos para este período.';
    toast.error(_state.errorMessage);
    renderCalendarArea();
    return;
  }

  _state.loading = false;
  _state.errorMessage = '';
  _state.events = Array.isArray(res?.data?.items) ? res.data.items : [];

  renderCalendarArea();
  await updateDetailsModalIfOpen();
}

function onResize() {
  const shouldCollapse = window.innerWidth <= 900;
  if (shouldCollapse === _state.filtersCollapsed) return;

  _state.filtersCollapsed = shouldCollapse;
  renderFilters();
}

export default {
  async mount(container) {
    _container = container;
    _state = buildInitialState();

    renderPageShell();
    await loadReferenceData();
    renderFilters();
    await loadEvents();

    window.addEventListener('resize', onResize);
  },

  unmount() {
    window.removeEventListener('resize', onResize);
    _container?.removeEventListener('click', onContainerClick);

    _container = null;
    _state = null;
    _loadToken = 0;
  },
};
