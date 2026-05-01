function escapeHtml(value) {
  return String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

function isHexColor(value) {
  return /^#[0-9A-Fa-f]{6}$/.test(String(value || '').trim());
}

function shortName(value, fallback = '—') {
  const name = String(value || '').trim();
  if (!name) return fallback;

  const parts = name.split(/\s+/).filter(Boolean);
  if (parts.length <= 2) return name;

  return `${parts[0]} ${parts[parts.length - 1]}`;
}

const STATUS_LABELS = {
  agendado: 'Agendado',
  confirmado: 'Confirmado',
  realizado: 'Realizado',
  cancelado: 'Cancelado',
  falta: 'Falta',
  remarcado: 'Remarcado',
  bloqueado: 'Bloqueado',
};

const STATUS_BADGE_CLASS = {
  agendado: 'badge-info',
  confirmado: 'badge-success',
  realizado: 'badge-primary',
  cancelado: 'badge-neutral',
  falta: 'badge-warning',
  remarcado: 'badge-info',
  bloqueado: 'badge-error',
};

function formatTime(value) {
  if (!value) return '--:--';

  const parsed = new Date(String(value).replace(' ', 'T'));
  if (Number.isNaN(parsed.getTime())) return '--:--';

  return parsed.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
}

function buildAriaLabel({
  title,
  timeLabel,
  statusLabel,
  professionalName,
  patientName,
}) {
  const chunks = [
    title || 'Evento',
    `Horário ${timeLabel}`,
    `Status ${statusLabel}`,
  ];

  if (professionalName) {
    chunks.push(`Profissional ${professionalName}`);
  }

  if (patientName) {
    chunks.push(`Paciente ${patientName}`);
  }

  return chunks.join('. ');
}

export function getEventColor(event, lookups = {}) {
  const resolvedColor = String(event?.resolved_color || '').trim();
  if (isHexColor(resolvedColor)) return resolvedColor;

  const override = String(event?.color_override || '').trim();
  if (isHexColor(override)) return override;

  const professionalColor = String(lookups?.professionalsByUuid?.[event?.professional_uuid]?.schedule_color || '').trim();
  if (isHexColor(professionalColor)) return professionalColor;

  const typeColor = String(event?.event_type_color || '').trim();
  if (isHexColor(typeColor)) return typeColor;

  const byTypeUuid = String(lookups?.eventTypesByUuid?.[event?.event_type_uuid]?.color || '').trim();
  if (isHexColor(byTypeUuid)) return byTypeUuid;

  return '#157470';
}

export function getReadableTextColor(backgroundColor) {
  const normalized = String(backgroundColor || '').trim();
  if (!isHexColor(normalized)) return '#ffffff';

  const r = parseInt(normalized.slice(1, 3), 16);
  const g = parseInt(normalized.slice(3, 5), 16);
  const b = parseInt(normalized.slice(5, 7), 16);
  const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;

  return luminance > 0.62 ? '#1a2530' : '#ffffff';
}

export function renderScheduleEventCard(event, options = {}) {
  const lookups = options.lookups || {};
  const variant = options.variant || 'day';

  const status = String(event?.status || 'agendado').trim();
  const title = escapeHtml(event?.title || 'Evento sem título');
  const eventUuid = escapeHtml(event?.uuid || '');
  const eventTypeName = escapeHtml(event?.event_type_name || lookups?.eventTypesByUuid?.[event?.event_type_uuid]?.name || 'Evento');

  const professionalName = shortName(
    event?.professional_name ||
      lookups?.professionalsByUuid?.[event?.professional_uuid]?.full_name ||
      lookups?.professionalsByUuid?.[event?.professional_uuid]?.name ||
      '',
    ''
  );

  const patientName = shortName(
    event?.patient_name || lookups?.patientsByUuid?.[event?.patient_uuid]?.full_name || '',
    ''
  );

  const timeLabel = `${formatTime(event?.starts_at)} - ${formatTime(event?.ends_at)}`;
  const color = getEventColor(event, lookups);
  const badgeClass = STATUS_BADGE_CLASS[status] || 'badge-neutral';
  const statusLabel = STATUS_LABELS[status] || 'Status';

  const showPeople = variant === 'day';
  const ariaLabel = buildAriaLabel({
    title: event?.title || 'Evento sem título',
    timeLabel,
    statusLabel,
    professionalName,
    patientName,
  });

  return `
    <button
      type="button"
      class="schedule-event-card schedule-event-card--${variant} schedule-event-card--status-${escapeHtml(status)}"
      data-schedule-event="${eventUuid}"
      style="--event-color:${escapeHtml(color)};"
      aria-label="${escapeHtml(ariaLabel)}"
    >
      <div class="schedule-event-card__left"></div>
      <div class="schedule-event-card__content">
        <div class="schedule-event-card__top">
          <span class="schedule-event-card__time">${escapeHtml(timeLabel)}</span>
          <span class="badge ${badgeClass}">${escapeHtml(statusLabel)}</span>
        </div>

        <h4 class="schedule-event-card__title">${title}</h4>

        <div class="schedule-event-card__meta">
          <span class="schedule-event-card__chip">${eventTypeName}</span>
          ${showPeople && professionalName ? `<span class="schedule-event-card__person">Profissional: ${escapeHtml(professionalName)}</span>` : ''}
          ${showPeople && patientName ? `<span class="schedule-event-card__person">Paciente: ${escapeHtml(patientName)}</span>` : ''}
        </div>
      </div>
    </button>`;
}
