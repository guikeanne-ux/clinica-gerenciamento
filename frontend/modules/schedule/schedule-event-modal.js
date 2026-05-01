function escapeHtml(value) {
  return String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

function fullNameFromMap(map, uuid) {
  return map?.[uuid]?.full_name || map?.[uuid]?.name || '—';
}

function parseDate(value) {
  const date = new Date(String(value || '').replace(' ', 'T'));
  return Number.isNaN(date.getTime()) ? null : date;
}

function pad2(value) {
  return String(value).padStart(2, '0');
}

function fieldError(errors, field) {
  const message = String(errors?.[field] || '').trim();
  if (!message) return '';
  return `<div class="field-error schedule-field-error">${escapeHtml(message)}</div>`;
}

function formatDateTime(value) {
  const date = parseDate(value);
  if (!date) return '—';

  return date.toLocaleString('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
}

export function formatDateTimeLocalInput(value) {
  const date = parseDate(value);
  if (!date) return '';

  return [
    date.getFullYear(),
    pad2(date.getMonth() + 1),
    pad2(date.getDate()),
  ].join('-') + `T${pad2(date.getHours())}:${pad2(date.getMinutes())}`;
}

export const SCHEDULE_STATUS_OPTIONS = [
  { value: 'agendado', label: 'Agendado' },
  { value: 'confirmado', label: 'Confirmado' },
  { value: 'realizado', label: 'Realizado' },
  { value: 'cancelado', label: 'Cancelado' },
  { value: 'falta', label: 'Falta' },
  { value: 'remarcado', label: 'Remarcado' },
  { value: 'bloqueado', label: 'Bloqueado' },
];

function statusLabel(status) {
  const found = SCHEDULE_STATUS_OPTIONS.find((item) => item.value === status);
  return found ? found.label : status || '—';
}

function statusClassName(status) {
  const value = String(status || '').trim();
  return value ? `schedule-status-pill--${value}` : '';
}

function formatBasicDate(value) {
  const date = parseDate(value);
  if (!date) return '—';
  return date.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric' });
}

let _lastFocusedElement = null;

function shouldShowAttendanceAction(event, lookups, permissions = {}) {
  if (!permissions.canCreateAttendance || !permissions.attendanceModuleAvailable) {
    return false;
  }

  if (!event?.is_attendance) {
    return false;
  }

  if (!event?.patient_uuid || !event?.professional_uuid) {
    return false;
  }

  const allowedStatus = ['agendado', 'confirmado'];
  if (!allowedStatus.includes(String(event?.status || '').trim())) {
    return false;
  }

  const eventType = lookups?.eventTypesByUuid?.[event?.event_type_uuid];
  return Boolean(eventType?.can_generate_attendance);
}

export function renderScheduleModals() {
  return `
    <div class="modal" id="schedule-event-form-modal" aria-hidden="true">
      <div class="modal__panel modal__panel--lg schedule-modal-panel" role="dialog" aria-modal="true" aria-labelledby="schedule-event-form-title">
        <div class="modal__header">
          <h2 id="schedule-event-form-title">Novo evento</h2>
          <button type="button" class="modal__close" data-schedule-modal-close aria-label="Fechar">✕</button>
        </div>

        <div class="modal__body" id="schedule-event-form-body"></div>
      </div>
    </div>

    <div class="modal" id="schedule-event-details-modal" aria-hidden="true">
      <div class="modal__panel schedule-modal-panel" role="dialog" aria-modal="true" aria-labelledby="schedule-event-details-title">
        <div class="modal__header">
          <h2 id="schedule-event-details-title">Detalhes do compromisso</h2>
          <button type="button" class="modal__close" data-schedule-modal-close aria-label="Fechar">✕</button>
        </div>

        <div class="modal__body" id="schedule-event-details-body"></div>
      </div>
    </div>

    <div class="modal" id="schedule-event-cancel-modal" aria-hidden="true">
      <div class="modal__panel modal__panel--sm schedule-modal-panel" role="dialog" aria-modal="true" aria-labelledby="schedule-event-cancel-title">
        <div class="modal__header">
          <h2 id="schedule-event-cancel-title">Cancelar compromisso</h2>
          <button type="button" class="modal__close" data-schedule-modal-close aria-label="Fechar">✕</button>
        </div>

        <div class="modal__body" id="schedule-event-cancel-body"></div>
      </div>
    </div>

    <div class="modal" id="schedule-event-reschedule-modal" aria-hidden="true">
      <div class="modal__panel modal__panel--sm schedule-modal-panel" role="dialog" aria-modal="true" aria-labelledby="schedule-event-reschedule-title">
        <div class="modal__header">
          <h2 id="schedule-event-reschedule-title">Remarcar compromisso</h2>
          <button type="button" class="modal__close" data-schedule-modal-close aria-label="Fechar">✕</button>
        </div>

        <div class="modal__body" id="schedule-event-reschedule-body"></div>
      </div>
    </div>`;
}

export function openScheduleModal(modalId) {
  const modal = document.getElementById(modalId);
  if (!modal) return;

  _lastFocusedElement = document.activeElement;
  modal.classList.add('open');
  modal.setAttribute('aria-hidden', 'false');
  const focusTarget = modal.querySelector('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
  focusTarget?.focus();
}

export function closeScheduleModal(modalElement) {
  if (!modalElement) return;

  modalElement.classList.remove('open');
  modalElement.setAttribute('aria-hidden', 'true');
  if (_lastFocusedElement instanceof HTMLElement) {
    _lastFocusedElement.focus();
  }
}

export function renderScheduleEventDetails(event, lookups, permissions = {}) {
  const eventTypeName = event.event_type_name || lookups?.eventTypesByUuid?.[event.event_type_uuid]?.name || '—';
  const professionalName = fullNameFromMap(lookups?.professionalsByUuid, event.professional_uuid);
  const patientName = fullNameFromMap(lookups?.patientsByUuid, event.patient_uuid);
  const canMutate = event.status !== 'cancelado';
  const statusText = statusLabel(event.status || '');
  const detailsStatusClass = statusClassName(event.status || '');
  const hasDescription = String(event.description || '').trim() !== '';
  const canShowAttendanceButton = shouldShowAttendanceAction(event, lookups, permissions);

  const actionButtons = [
    permissions.canUpdate ? `<button type="button" class="btn btn-secondary btn-sm" data-schedule-detail-action="edit">Editar</button>` : '',
    permissions.canConfirm && canMutate ? `<button type="button" class="btn btn-ghost btn-sm" data-schedule-detail-action="confirm">Confirmar</button>` : '',
    permissions.canUpdate && canMutate ? `<button type="button" class="btn btn-ghost btn-sm" data-schedule-detail-action="absence">Marcar falta</button>` : '',
    permissions.canUpdate && canMutate ? `<button type="button" class="btn btn-ghost btn-sm" data-schedule-detail-action="done">Marcar realizado</button>` : '',
    permissions.canUpdate && canMutate ? `<button type="button" class="btn btn-ghost btn-sm" data-schedule-detail-action="reschedule">Remarcar</button>` : '',
    permissions.canCancel && canMutate ? `<button type="button" class="btn btn-danger btn-sm" data-schedule-detail-action="cancel">Cancelar</button>` : '',
    permissions.canDelete ? `<button type="button" class="btn btn-ghost btn-sm" data-schedule-detail-action="delete">Excluir</button>` : '',
    canShowAttendanceButton ? `<button type="button" class="btn btn-outline btn-sm" data-schedule-detail-action="attendance">Iniciar atendimento</button>` : '',
  ].filter(Boolean).join('');

  return `
    <div class="schedule-event-details">
      <header class="schedule-event-details__header">
        <div class="schedule-event-details__title-block">
          <h3>${escapeHtml(event.title || 'Evento sem título')}</h3>
          <p class="schedule-event-details__time">${escapeHtml(formatDateTime(event.starts_at))} - ${escapeHtml(formatDateTime(event.ends_at))}</p>
        </div>
        <span class="schedule-status-pill ${detailsStatusClass}">${escapeHtml(statusText)}</span>
      </header>

      <dl class="schedule-event-details__grid">
        <div><dt>Classificação</dt><dd>${event.is_attendance ? 'Atendimento agendado' : 'Evento comum'}</dd></div>
        <div><dt>Tipo</dt><dd>${escapeHtml(eventTypeName)}</dd></div>
        <div><dt>Origem</dt><dd>${escapeHtml(event.origin || '—')}</dd></div>
        <div><dt>Profissional</dt><dd>${escapeHtml(professionalName)}</dd></div>
        <div><dt>Paciente</dt><dd>${escapeHtml(patientName)}</dd></div>
        <div><dt>Local</dt><dd>${escapeHtml(event.room_or_location || '—')}</dd></div>
        <div><dt>Dia inteiro</dt><dd>${event.all_day ? 'Sim' : 'Não'}</dd></div>
      </dl>

      <section class="schedule-event-details__block">
        <h4>Descrição</h4>
        <p class="schedule-event-details__description">${escapeHtml(hasDescription ? event.description : 'Sem descrição.')}</p>
      </section>

      <section class="schedule-event-details__block schedule-event-details__history">
        <h4>Histórico</h4>
        <ul class="schedule-event-history">
          <li>Criado em ${escapeHtml(formatBasicDate(event.created_at))}</li>
          <li>Última atualização em ${escapeHtml(formatBasicDate(event.updated_at))}</li>
          ${event.canceled_at ? `<li>Cancelado em ${escapeHtml(formatBasicDate(event.canceled_at))}</li>` : '<li>Sem cancelamentos registrados</li>'}
        </ul>
      </section>

      ${actionButtons ? `<div class="schedule-event-details__actions">${actionButtons}</div>` : ''}
    </div>`;
}

function optionsHtml(items, selectedValue, labelResolver, valueResolver, extraAttrsResolver = null) {
  return items.map((item) => {
    const value = String(valueResolver(item) || '');
    const label = String(labelResolver(item) || '');
    const selected = value === String(selectedValue || '') ? 'selected' : '';
    const extra = typeof extraAttrsResolver === 'function' ? String(extraAttrsResolver(item) || '') : '';

    return `<option value="${escapeHtml(value)}" ${selected} ${extra}>${escapeHtml(label)}</option>`;
  }).join('');
}

function weekDaysCheckboxes(selectedDays = []) {
  const labels = [
    ['MO', 'Seg'],
    ['TU', 'Ter'],
    ['WE', 'Qua'],
    ['TH', 'Qui'],
    ['FR', 'Sex'],
    ['SA', 'Sáb'],
    ['SU', 'Dom'],
  ];

  return labels.map(([value, label]) => {
    const checked = selectedDays.includes(value) ? 'checked' : '';

    return `
      <label class="schedule-weekday-check">
        <input type="checkbox" name="schedule-recurrence-weekday" value="${value}" ${checked} />
        <span>${label}</span>
      </label>`;
  }).join('');
}

export function renderScheduleEventForm({
  mode,
  draft,
  errors,
  eventTypes,
  professionals,
  patients,
}) {
  const title = mode === 'edit' ? 'Editar compromisso' : 'Novo compromisso';
  const recurrenceEnabled = Boolean(draft?.recurrence?.enabled);
  const recurrenceCollapsed = recurrenceEnabled ? '' : ' is-collapsed';

  const statusOptions = optionsHtml(
    SCHEDULE_STATUS_OPTIONS,
    draft.status,
    (item) => item.label,
    (item) => item.value
  );

  const typeOptions = optionsHtml(
    eventTypes,
    draft.event_type_uuid,
    (item) => item.name,
    (item) => item.uuid,
    (item) => `data-requires-patient="${item.requires_patient ? '1' : '0'}" data-requires-professional="${item.requires_professional ? '1' : '0'}"`
  );

  const professionalOptions = optionsHtml(
    professionals,
    draft.professional_uuid,
    (item) => item.full_name || item.name,
    (item) => item.uuid
  );

  const patientOptions = optionsHtml(
    patients,
    draft.patient_uuid,
    (item) => item.full_name,
    (item) => item.uuid
  );

  return `
    <form id="schedule-event-form" class="schedule-form" novalidate>
      <h3 class="schedule-form__title">${title}</h3>

      <div class="schedule-form__grid">
        <div class="field ${errors.title ? 'has-error' : ''}">
          <label for="schedule-form-title">Título *</label>
          <input id="schedule-form-title" name="title" class="input" maxlength="160" value="${escapeHtml(draft.title || '')}" />
          ${fieldError(errors, 'title')}
        </div>

        <div class="field ${errors.event_type_uuid ? 'has-error' : ''}">
          <label for="schedule-form-event-type">Tipo de evento (opcional)</label>
          <select id="schedule-form-event-type" name="event_type_uuid" class="input">
            <option value="">Usar tipo padrão automático</option>
            ${typeOptions}
          </select>
          ${fieldError(errors, 'event_type_uuid')}
        </div>

        <div class="field schedule-form__field-full ${errors.description ? 'has-error' : ''}">
          <label for="schedule-form-description">Descrição</label>
          <textarea id="schedule-form-description" name="description" class="input" rows="3" placeholder="Detalhes principais do compromisso">${escapeHtml(draft.description || '')}</textarea>
          ${fieldError(errors, 'description')}
        </div>

        <div class="field schedule-form__field-full ${errors.observations ? 'has-error' : ''}">
          <label for="schedule-form-observations">Observações</label>
          <textarea id="schedule-form-observations" name="observations" class="input" rows="2" placeholder="Observações internas opcionais">${escapeHtml(draft.observations || '')}</textarea>
          ${fieldError(errors, 'observations')}
        </div>

        <div class="field ${errors.starts_at ? 'has-error' : ''}">
          <label for="schedule-form-starts-at">Data/hora início *</label>
          <input id="schedule-form-starts-at" name="starts_at" type="datetime-local" class="input" value="${escapeHtml(draft.starts_at || '')}" />
          ${fieldError(errors, 'starts_at')}
        </div>

        <div class="field ${errors.ends_at ? 'has-error' : ''}">
          <label for="schedule-form-ends-at">Data/hora fim *</label>
          <input id="schedule-form-ends-at" name="ends_at" type="datetime-local" class="input" value="${escapeHtml(draft.ends_at || '')}" />
          ${fieldError(errors, 'ends_at')}
        </div>

        <div class="field ${errors.professional_uuid ? 'has-error' : ''}">
          <label for="schedule-form-professional">Profissional</label>
          <select id="schedule-form-professional" name="professional_uuid" class="input">
            <option value="">Sem profissional</option>
            ${professionalOptions}
          </select>
          ${fieldError(errors, 'professional_uuid')}
        </div>

        <div class="field ${errors.patient_uuid ? 'has-error' : ''}">
          <label for="schedule-form-patient">Paciente</label>
          <select id="schedule-form-patient" name="patient_uuid" class="input">
            <option value="">Sem paciente</option>
            ${patientOptions}
          </select>
          ${fieldError(errors, 'patient_uuid')}
        </div>

        <div class="field">
          <label for="schedule-form-room">Sala/local</label>
          <input id="schedule-form-room" name="room_or_location" class="input" maxlength="120" value="${escapeHtml(draft.room_or_location || '')}" />
        </div>

        <div class="field ${errors.status ? 'has-error' : ''}">
          <label for="schedule-form-status">Status inicial</label>
          <select id="schedule-form-status" name="status" class="input">
            ${statusOptions}
          </select>
          ${fieldError(errors, 'status')}
        </div>
      </div>

      <div class="schedule-form__toggles">
        <label class="schedule-check">
          <input type="checkbox" id="schedule-form-is-attendance" name="is_attendance" ${draft.is_attendance ? 'checked' : ''} />
          <span>Atendimento agendado</span>
        </label>
        <label class="schedule-check">
          <input type="checkbox" id="schedule-form-all-day" name="all_day" ${draft.all_day ? 'checked' : ''} />
          <span>Evento de dia inteiro</span>
        </label>
      </div>

      <section class="schedule-recurrence ${recurrenceCollapsed}">
        <button type="button" class="btn btn-ghost btn-sm" id="schedule-toggle-recurrence">
          ${recurrenceEnabled ? 'Ocultar repetição' : 'Repetição'}
        </button>

        <div class="schedule-recurrence__panel">
          <label class="schedule-check">
            <input type="checkbox" id="schedule-form-recurrence-enabled" ${recurrenceEnabled ? 'checked' : ''} />
            <span>Repetir semanalmente</span>
          </label>

          <div class="schedule-recurrence__grid">
            <div class="field ${errors.recurrence_until ? 'has-error' : ''}">
              <label for="schedule-form-recurrence-until">Repetir até *</label>
              <input id="schedule-form-recurrence-until" type="date" class="input" value="${escapeHtml(draft.recurrence?.until || '')}" />
              ${fieldError(errors, 'recurrence_until')}
            </div>

            <div class="field ${errors.recurrence_interval ? 'has-error' : ''}">
              <label for="schedule-form-recurrence-interval">Intervalo (semanas)</label>
              <input id="schedule-form-recurrence-interval" type="number" min="1" max="12" class="input" value="${escapeHtml(String(draft.recurrence?.interval || 1))}" />
              ${fieldError(errors, 'recurrence_interval')}
            </div>
          </div>

          <div class="field ${errors.recurrence_week_days ? 'has-error' : ''}">
            <label>Dias da semana *</label>
            <div class="schedule-weekday-list">${weekDaysCheckboxes(draft.recurrence?.week_days || [])}</div>
            ${fieldError(errors, 'recurrence_week_days')}
          </div>
        </div>
      </section>

      <div class="schedule-form__footer">
        <button type="button" class="btn btn-secondary btn-md" data-schedule-modal-close>Cancelar</button>
        <button type="submit" class="btn btn-primary btn-md">${mode === 'edit' ? 'Salvar alterações' : 'Criar compromisso'}</button>
      </div>
    </form>`;
}

export function renderScheduleCancelForm({ reason = '', errors = {} }) {
  return `
    <form id="schedule-event-cancel-form" class="schedule-form" novalidate>
      <p class="schedule-modal__text">Informe o motivo do cancelamento. Esta ação altera o status do compromisso para cancelado.</p>

      <div class="field ${errors.cancel_reason ? 'has-error' : ''}">
        <label for="schedule-cancel-reason">Motivo do cancelamento *</label>
        <textarea id="schedule-cancel-reason" class="input" rows="3" maxlength="500">${escapeHtml(reason)}</textarea>
        ${fieldError(errors, 'cancel_reason')}
      </div>

      <div class="schedule-form__footer">
        <button type="button" class="btn btn-secondary btn-md" data-schedule-modal-close>Voltar</button>
        <button type="submit" class="btn btn-danger btn-md">Confirmar cancelamento</button>
      </div>
    </form>`;
}

export function renderScheduleRescheduleForm({ event, values = {}, errors = {} }) {
  const startsAt = values.starts_at || formatDateTimeLocalInput(event?.starts_at || '');
  const endsAt = values.ends_at || formatDateTimeLocalInput(event?.ends_at || '');
  const reason = values.reason || '';

  return `
    <form id="schedule-event-reschedule-form" class="schedule-form" novalidate>
      <p class="schedule-modal__text">Defina o novo horário do compromisso. O histórico da remarcação será auditado no backend.</p>

      <div class="schedule-form__grid">
        <div class="field ${errors.starts_at ? 'has-error' : ''}">
          <label for="schedule-reschedule-starts-at">Novo início *</label>
          <input id="schedule-reschedule-starts-at" type="datetime-local" class="input" value="${escapeHtml(startsAt)}" />
          ${fieldError(errors, 'starts_at')}
        </div>

        <div class="field ${errors.ends_at ? 'has-error' : ''}">
          <label for="schedule-reschedule-ends-at">Novo fim *</label>
          <input id="schedule-reschedule-ends-at" type="datetime-local" class="input" value="${escapeHtml(endsAt)}" />
          ${fieldError(errors, 'ends_at')}
        </div>
      </div>

      <div class="field ${errors.reason ? 'has-error' : ''}">
        <label for="schedule-reschedule-reason">Motivo (opcional)</label>
        <textarea id="schedule-reschedule-reason" class="input" rows="2" maxlength="300">${escapeHtml(reason)}</textarea>
        ${fieldError(errors, 'reason')}
      </div>

      <div class="schedule-form__footer">
        <button type="button" class="btn btn-secondary btn-md" data-schedule-modal-close>Voltar</button>
        <button type="submit" class="btn btn-primary btn-md">Confirmar remarcação</button>
      </div>
    </form>`;
}
