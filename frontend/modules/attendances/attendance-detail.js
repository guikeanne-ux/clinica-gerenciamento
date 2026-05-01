import { http } from '../../core/services/http.js';
import { toast } from '../../core/js/toast.js';
import { API_BASE } from '../../core/services/api-config.js';
import { sessionStore } from '../../core/auth/session-store.js';

const AUDIO_LIMIT_MB = 20;
let quillLoaderPromise = null;

async function loadAttendance(uuid) {
  return http.get(`/api/v1/attendances/${uuid}`, { context: 'page' });
}

async function postAction(uuid, action) {
  return http.post(`/api/v1/attendances/${uuid}/${action}`, {}, { context: 'page' });
}

async function substituteProfessional(uuid, payload) {
  return http.post(`/api/v1/attendances/${uuid}/substitute-professional`, payload, { context: 'page' });
}

async function listProfessionals() {
  const res = await http.get('/api/v1/professionals?page=1&per_page=100&sort=full_name&direction=asc', { context: 'page' });
  return Array.isArray(res?.data?.items) ? res.data.items : [];
}

async function listClinicalRecords(attendanceUuid) {
  const res = await http.get(`/api/v1/attendances/${attendanceUuid}/clinical-records`, { context: 'page' });
  return Array.isArray(res?.data?.items) ? res.data.items : [];
}

async function createClinicalRecord(attendanceUuid, payload) {
  return http.post(`/api/v1/attendances/${attendanceUuid}/clinical-records`, payload, { context: 'page' });
}

async function updateClinicalRecord(uuid, payload) {
  return http.put(`/api/v1/clinical-records/${uuid}`, payload, { context: 'page' });
}

async function finalizeClinicalRecord(uuid) {
  return http.post(`/api/v1/clinical-records/${uuid}/finalize`, {}, { context: 'page' });
}

async function deleteClinicalRecord(uuid) {
  return http.delete(`/api/v1/clinical-records/${uuid}`, { context: 'page' });
}

async function listAudioRecords(attendanceUuid) {
  const res = await http.get(`/api/v1/attendances/${attendanceUuid}/audio-records`, { context: 'page' });
  return Array.isArray(res?.data?.items) ? res.data.items : [];
}

async function deleteAudioRecord(uuid) {
  return http.delete(`/api/v1/audio-records/${uuid}`, { context: 'page' });
}

async function uploadAudioFile(file, attendanceUuid) {
  const base64 = await fileToBase64(file);

  return http.post('/api/v1/files/upload', {
    original_name: file.name,
    mime_type: file.type || 'audio/wav',
    content_base64: base64,
    classification: 'documento_clinico',
    related_module: 'clinical_record',
    related_entity_type: 'attendance',
    related_entity_uuid: attendanceUuid,
  }, { context: 'page' });
}

async function attachAudio(attendanceUuid, fileUuid, title, durationSeconds = null) {
  return http.post(`/api/v1/attendances/${attendanceUuid}/audio-records`, {
    title,
    file_uuid: fileUuid,
    duration_seconds: durationSeconds,
  }, { context: 'page' });
}

function fileToBase64(file) {
  return new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.onload = () => {
      const result = String(reader.result || '');
      const commaIndex = result.indexOf(',');
      resolve(commaIndex >= 0 ? result.slice(commaIndex + 1) : result);
    };
    reader.onerror = reject;
    reader.readAsDataURL(file);
  });
}

function escapeHtml(value) {
  return String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');
}

function renderStatus(status) {
  return String(status || 'rascunho').replaceAll('_', ' ');
}

function statusBadgeClass(status) {
  const value = String(status || '').trim();
  if (value === 'finalizado') return 'badge-success';
  if (value === 'cancelado') return 'badge-neutral';
  if (value === 'falta') return 'badge-warning';
  if (value === 'em_andamento') return 'badge-primary';
  return 'badge-info';
}

function formatDateTimePtBr(value) {
  const raw = String(value || '').trim();
  if (!raw) return '—';

  const parsed = new Date(raw.replace(' ', 'T'));
  if (Number.isNaN(parsed.getTime())) return raw;

  return parsed.toLocaleString('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
}

function isTerminalStatus(status) {
  return ['finalizado', 'cancelado', 'falta'].includes(String(status || '').trim());
}

function fieldError(errors, field) {
  const message = String(errors?.[field] || '').trim();
  if (!message) return '';

  return `<div class="field-error" style="margin-top:0.35rem;">${escapeHtml(message)}</div>`;
}

function formatHuman(value, fallback = 'Não informado') {
  const text = String(value || '').trim();
  return text || fallback;
}

function mediaRecordingSupported() {
  return typeof navigator !== 'undefined'
    && !!navigator.mediaDevices
    && typeof navigator.mediaDevices.getUserMedia === 'function'
    && typeof window.MediaRecorder !== 'undefined';
}

function canEditRecord(record) {
  return String(record?.status || '') !== 'finalizado';
}

function ensureQuillLoaded() {
  if (window.Quill) return Promise.resolve(window.Quill);
  if (quillLoaderPromise) return quillLoaderPromise;

  quillLoaderPromise = new Promise((resolve, reject) => {
    const cssId = 'quill-snow-css';
    if (!document.getElementById(cssId)) {
      const link = document.createElement('link');
      link.id = cssId;
      link.rel = 'stylesheet';
      link.href = 'https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.snow.css';
      document.head.appendChild(link);
    }

    const scriptId = 'quill-js';
    if (document.getElementById(scriptId)) {
      const waitReady = () => {
        if (window.Quill) return resolve(window.Quill);
        setTimeout(waitReady, 30);
      };
      waitReady();
      return;
    }

    const script = document.createElement('script');
    script.id = scriptId;
    script.src = 'https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.min.js';
    script.async = true;
    script.onload = () => resolve(window.Quill);
    script.onerror = () => reject(new Error('Não foi possível carregar o editor de texto.'));
    document.body.appendChild(script);
  });

  return quillLoaderPromise;
}

async function fetchAuthorizedBlob(downloadUrl) {
  const token = sessionStore.getToken();
  if (!token) throw new Error('Sessão inválida.');

  const response = await fetch(`${API_BASE}${downloadUrl}`, {
    method: 'GET',
    headers: {
      Authorization: `Bearer ${token}`,
    },
  });

  if (!response.ok) {
    throw new Error('Falha ao acessar o áudio.');
  }

  return response.blob();
}

export default {
  async mount(container, params) {
    const uuid = params?.uuid;
    let substitutionErrors = {};
    let activeTab = 'evolucao';
    let selectedRecordUuid = null;
    let autosaveTimer = null;

    const renderPage = async () => {
      container.innerHTML = '<section class="card"><div class="card__content">Carregando atendimento...</div></section>';

      const attendanceRes = await loadAttendance(uuid);
      if (!attendanceRes.success) {
        container.innerHTML = `<section class="card"><div class="card__content">${attendanceRes.message || 'Erro ao carregar atendimento.'}</div></section>`;
        return;
      }

      const item = attendanceRes.data || {};
      const status = String(item.status || '').trim();
      const terminal = isTerminalStatus(status);

      const [professionals, records, audios] = await Promise.all([
        listProfessionals(),
        listClinicalRecords(uuid),
        listAudioRecords(uuid),
      ]);

      const options = professionals
        .map((p) => `<option value="${p.uuid}" ${p.uuid === item.professional_uuid ? 'selected' : ''}>${escapeHtml(p.full_name || p.name || p.uuid)}</option>`)
        .join('');

      const recordingSupported = mediaRecordingSupported();
      const selectedRecord = records.find((record) => record.uuid === selectedRecordUuid)
        || records.find((record) => record.record_type === activeTab)
        || null;

      container.innerHTML = `
        <div class="page-header">
          <div class="page-header__info">
            <h1 style="margin:0;font-size:1.25rem;">Atendimento</h1>
            <p class="text-sm text-muted" style="margin:0.25rem 0 0;">Registro clínico, substituição e evolução em texto/áudio.</p>
          </div>
          <div class="page-header__actions">
            <span class="badge ${statusBadgeClass(status)}">${escapeHtml(renderStatus(status))}</span>
          </div>
        </div>

        <section class="section">
          <h3 style="margin:0 0 0.9rem;font-size:1rem;">Resumo do atendimento</h3>
          <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:0.85rem;">
            <div class="card" style="padding:0.65rem;"><div class="text-xs text-muted">Paciente</div><div class="font-medium">${escapeHtml(formatHuman(item.patient_name))}</div></div>
            <div class="card" style="padding:0.65rem;"><div class="text-xs text-muted">Profissional atual</div><div class="font-medium">${escapeHtml(formatHuman(item.professional_name))}</div></div>
            <div class="card" style="padding:0.65rem;"><div class="text-xs text-muted">Profissional original</div><div class="font-medium">${escapeHtml(formatHuman(item.original_professional_name || item.professional_name))}</div></div>
            <div class="card" style="padding:0.65rem;"><div class="text-xs text-muted">Início</div><div class="font-medium">${escapeHtml(formatDateTimePtBr(item.starts_at))}</div></div>
            <div class="card" style="padding:0.65rem;"><div class="text-xs text-muted">Fim</div><div class="font-medium">${escapeHtml(formatDateTimePtBr(item.ends_at))}</div></div>
          </div>
          <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:1rem;padding-top:0.85rem;border-top:1px solid var(--border);">
            ${!terminal ? '<button class="btn btn-primary btn-md" data-finalize>Finalizar atendimento</button>' : ''}
            ${!terminal ? '<button class="btn btn-outline btn-md" data-cancel>Cancelar atendimento</button>' : ''}
            ${!terminal ? '<button class="btn btn-outline btn-md" data-no-show>Marcar falta</button>' : ''}
            ${terminal ? '<span class="badge badge-neutral">Atendimento encerrado (edição bloqueada)</span>' : ''}
          </div>
        </section>

        <section class="section" style="margin-top:0.85rem;">
          <h3 style="margin-top:0;">Substituição de profissional</h3>
          <div class="form-grid">
            <div class="field ${substitutionErrors.professional_uuid ? 'has-error' : ''}">
              <label for="attendance-substitute-professional">Novo profissional</label>
              <select id="attendance-substitute-professional" class="input">
                <option value="">Selecione...</option>
                ${options}
              </select>
              ${fieldError(substitutionErrors, 'professional_uuid')}
            </div>
            <div class="field ${substitutionErrors.reason ? 'has-error' : ''}">
              <label for="attendance-substitute-reason">Motivo</label>
              <textarea id="attendance-substitute-reason" class="input" rows="3" placeholder="Descreva o motivo da substituição"></textarea>
              ${fieldError(substitutionErrors, 'reason')}
            </div>
          </div>
          ${!terminal ? '<button class="btn btn-warning btn-md" data-substitute>Registrar substituição</button>' : '<p class="text-sm text-muted">Substituição indisponível para atendimento encerrado.</p>'}
        </section>

        <section class="section" style="margin-top:0.85rem;">
          <h3 style="margin-top:0;">Evolução clínica</h3>
          <div class="tabs" id="attendance-tabs" role="tablist" aria-label="Tipos de evolução" style="margin-bottom:0.8rem;">
            <button class="tab ${activeTab === 'evolucao' ? 'active' : ''}" data-tab="evolucao" role="tab" aria-selected="${activeTab === 'evolucao' ? 'true' : 'false'}">Evolução em texto</button>
            <button class="tab ${activeTab === 'anamnese' ? 'active' : ''}" data-tab="anamnese" role="tab" aria-selected="${activeTab === 'anamnese' ? 'true' : 'false'}">Anamnese</button>
            <button class="tab ${activeTab === 'prontuario_livre' ? 'active' : ''}" data-tab="prontuario_livre" role="tab" aria-selected="${activeTab === 'prontuario_livre' ? 'true' : 'false'}">Prontuário livre</button>
            <button class="tab ${activeTab === 'audio' ? 'active' : ''}" data-tab="audio" role="tab" aria-selected="${activeTab === 'audio' ? 'true' : 'false'}">Evolução em áudio</button>
          </div>

          <div id="attendance-tab-content"></div>
        </section>`;

      const tabContent = container.querySelector('#attendance-tab-content');
      if (activeTab === 'audio') {
        const audioItems = audios.map((audio) => `
          <div class="card" style="padding:0.65rem;display:flex;justify-content:space-between;gap:0.75rem;align-items:center;flex-wrap:wrap;">
            <div>
              <div class="font-medium">${escapeHtml(audio.title || 'Áudio clínico')}</div>
              <div class="text-xs text-muted">Enviado em ${escapeHtml(formatDateTimePtBr(audio.created_at))}</div>
              ${audio.file?.download_url ? `<audio controls data-download-url="${escapeHtml(audio.file.download_url)}" style="margin-top:0.4rem;max-width:320px;width:100%;"></audio>` : ''}
            </div>
            <div style="display:flex;gap:0.4rem;">
              ${audio.file?.download_url ? `<button class="btn btn-outline btn-md" data-audio-download-url="${escapeHtml(audio.file.download_url)}" data-audio-download-name="${escapeHtml(audio.file.original_name || `${audio.title || 'audio'}.bin`)}">Baixar</button>` : ''}
              ${!terminal ? `<button class="btn btn-danger btn-md" data-audio-delete="${audio.uuid}">Remover</button>` : ''}
            </div>
          </div>
        `).join('') || '<p class="text-sm text-muted">Nenhum áudio anexado neste atendimento.</p>';

        tabContent.innerHTML = `
          ${recordingSupported
            ? '<p class="text-sm text-muted">Seu navegador suporta gravação direta.</p><div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:0.8rem;"><button class="btn btn-outline btn-md" data-audio-record-start>Iniciar gravação</button><button class="btn btn-outline btn-md" data-audio-record-stop disabled>Parar e enviar</button><span class="text-sm text-muted" id="audio-recording-state">Aguardando</span></div>'
            : '<p class="text-sm text-muted">Seu navegador não suporta gravação direta. Faça upload abaixo.</p>'}
          <p class="text-xs text-muted" style="margin-top:0;">Limite por áudio: ${AUDIO_LIMIT_MB}MB. Você pode anexar mais de um áudio por atendimento.</p>
          <div class="form-grid">
            <div class="field"><label for="attendance-audio-title">Título</label><input id="attendance-audio-title" class="input" placeholder="Ex: Observações sessão 1" /></div>
            <div class="field"><label for="attendance-audio-file">Arquivo de áudio</label><input id="attendance-audio-file" class="input" type="file" accept="audio/*" /></div>
          </div>
          ${!terminal ? '<button class="btn btn-secondary btn-md" data-audio-upload>Enviar áudio</button>' : '<p class="text-sm text-muted">Upload bloqueado para atendimento encerrado.</p>'}
          <div style="display:grid;gap:0.6rem;margin-top:0.9rem;">${audioItems}</div>
        `;
      } else {
        const filtered = records.filter((record) => record.record_type === activeTab);
        const selected = selectedRecord && selectedRecord.record_type === activeTab ? selectedRecord : filtered[0] || null;
        selectedRecordUuid = selected?.uuid || null;

        const recordItems = filtered.map((record) => `
          <button class="btn btn-outline btn-md" data-record-select="${record.uuid}">
            ${escapeHtml(record.title || record.record_type)} · ${escapeHtml(renderStatus(record.status))}
          </button>
        `).join('');

        tabContent.innerHTML = `
          <div style="display:flex;justify-content:space-between;gap:0.6rem;flex-wrap:wrap;align-items:center;">
            <div style="display:flex;gap:0.45rem;flex-wrap:wrap;">${recordItems || '<span class="text-sm text-muted">Sem registros deste tipo ainda.</span>'}</div>
            ${!terminal ? '<button class="btn btn-primary btn-md" data-record-create>Novo registro deste tipo</button>' : ''}
          </div>
          ${selected
            ? `<div class="card" style="margin-top:0.8rem;padding:0.75rem;">
                <div class="form-grid">
                  <div class="field"><label>Título</label><input id="record-title" class="input" value="${escapeHtml(selected.title || '')}" ${canEditRecord(selected) && !terminal ? '' : 'disabled'} /></div>
                  <div class="field"><label>Status</label><div><span class="badge ${statusBadgeClass(selected.status)}">${escapeHtml(renderStatus(selected.status))}</span></div></div>
                </div>
                <div class="text-xs text-muted" id="record-save-state" style="margin:0.5rem 0;">Salvo</div>
                <div id="record-editor" style="min-height:240px;"></div>
                <div style="display:flex;gap:0.5rem;margin-top:0.7rem;flex-wrap:wrap;">
                  ${canEditRecord(selected) && !terminal ? '<button class="btn btn-primary btn-md" data-record-save>Salvar rascunho</button>' : ''}
                  ${canEditRecord(selected) && !terminal ? '<button class="btn btn-warning btn-md" data-record-finalize>Finalizar registro</button>' : ''}
                  ${canEditRecord(selected) && !terminal ? '<button class="btn btn-danger btn-md" data-record-delete>Excluir registro</button>' : ''}
                </div>
              </div>`
            : '<p class="text-sm text-muted" style="margin-top:0.75rem;">Crie um registro para começar.</p>'}
        `;

        tabContent.querySelectorAll('[data-record-select]').forEach((button) => {
          button.addEventListener('click', async () => {
            selectedRecordUuid = button.getAttribute('data-record-select');
            await renderPage();
          });
        });

          tabContent.querySelector('[data-record-create]')?.addEventListener('click', async () => {
            const createRes = await createClinicalRecord(uuid, { record_type: activeTab });
            if (!createRes.success) {
              const firstError = Array.isArray(createRes.errors) && createRes.errors[0]?.message
                ? String(createRes.errors[0].message)
                : '';
              return toast.error(firstError || createRes.message || 'Erro ao criar registro clínico.');
            }
            selectedRecordUuid = createRes?.data?.uuid || null;
            toast.success('Registro clínico criado.');
          await renderPage();
        });

        if (selected && canEditRecord(selected) && !terminal) {
          const titleInput = tabContent.querySelector('#record-title');
          const stateEl = tabContent.querySelector('#record-save-state');
          const editorHost = tabContent.querySelector('#record-editor');
          let quill = null;
          try {
            const Quill = await ensureQuillLoaded();
            quill = new Quill(editorHost, {
              theme: 'snow',
              placeholder: 'Escreva aqui o registro clínico...',
              modules: {
                toolbar: [
                  [{ header: [1, 2, 3, false] }],
                  ['bold', 'italic', 'underline', 'strike'],
                  [{ color: [] }, { background: [] }],
                  [{ list: 'ordered' }, { list: 'bullet' }],
                  [{ align: [] }],
                  ['blockquote', 'clean'],
                ],
              },
            });

            quill.clipboard.dangerouslyPasteHTML(String(selected.content_markdown || ''));
          } catch (error) {
            toast.error(error instanceof Error ? error.message : 'Falha ao inicializar editor.');
            return;
          }

          const saveDraft = async (manual = false) => {
            const payload = {
              title: titleInput?.value || '',
              content_markdown: quill.root.innerHTML || '',
            };
            if (stateEl) stateEl.textContent = 'Salvando...';
            const saveRes = await updateClinicalRecord(selected.uuid, payload);
            if (!saveRes.success) {
              if (stateEl) stateEl.textContent = 'Erro ao salvar';
              if (manual) toast.error(saveRes.message || 'Erro ao salvar rascunho.');
              return;
            }
            if (stateEl) stateEl.textContent = 'Salvo';
            if (manual) toast.success('Rascunho salvo.');
          };

          const scheduleAutosave = () => {
            if (autosaveTimer) clearTimeout(autosaveTimer);
            if (stateEl) stateEl.textContent = 'Alterações pendentes';
            autosaveTimer = setTimeout(() => saveDraft(false), 900);
          };

          titleInput?.addEventListener('input', scheduleAutosave);
          quill.on('text-change', scheduleAutosave);
          tabContent.querySelector('[data-record-save]')?.addEventListener('click', async () => saveDraft(true));

          tabContent.querySelector('[data-record-finalize]')?.addEventListener('click', async () => {
            const saveRes = await updateClinicalRecord(selected.uuid, {
              title: titleInput?.value || '',
              content_markdown: quill.root.innerHTML || '',
            });

            if (!saveRes.success) {
              return toast.error(saveRes.message || 'Não foi possível salvar antes de finalizar.');
            }

            const finalizeRes = await finalizeClinicalRecord(selected.uuid);
            if (!finalizeRes.success) {
              const firstError = Array.isArray(finalizeRes.errors) && finalizeRes.errors[0]?.message
                ? String(finalizeRes.errors[0].message)
                : '';
              return toast.error(firstError || finalizeRes.message || 'Erro ao finalizar registro.');
            }

            toast.success('Registro finalizado com sucesso.');
            await renderPage();
          });

          tabContent.querySelector('[data-record-delete]')?.addEventListener('click', async () => {
            const removeRes = await deleteClinicalRecord(selected.uuid);
            if (!removeRes.success) {
              const firstError = Array.isArray(removeRes.errors) && removeRes.errors[0]?.message
                ? String(removeRes.errors[0].message)
                : '';
              return toast.error(firstError || removeRes.message || 'Não foi possível excluir o registro.');
            }

            toast.success('Registro excluído com sucesso.');
            selectedRecordUuid = null;
            await renderPage();
          });
        }
      }

      container.querySelectorAll('[data-tab]').forEach((button) => {
        button.addEventListener('click', async () => {
          activeTab = button.getAttribute('data-tab') || 'evolucao';
          await renderPage();
        });
      });

      container.querySelector('[data-finalize]')?.addEventListener('click', async () => {
        const actionRes = await postAction(uuid, 'finalize');
        if (!actionRes.success) return toast.error(actionRes.message || 'Erro ao finalizar atendimento.');
        toast.success('Atendimento finalizado com sucesso.');
        await renderPage();
      });

      container.querySelector('[data-cancel]')?.addEventListener('click', async () => {
        const actionRes = await postAction(uuid, 'cancel');
        if (!actionRes.success) return toast.error(actionRes.message || 'Erro ao cancelar atendimento.');
        toast.success('Atendimento cancelado com sucesso.');
        await renderPage();
      });

      container.querySelector('[data-no-show]')?.addEventListener('click', async () => {
        const actionRes = await postAction(uuid, 'no-show');
        if (!actionRes.success) return toast.error(actionRes.message || 'Erro ao marcar falta.');
        toast.success('Falta registrada com sucesso.');
        await renderPage();
      });

      container.querySelector('[data-substitute]')?.addEventListener('click', async () => {
        substitutionErrors = {};

        const professionalUuid = container.querySelector('#attendance-substitute-professional')?.value || '';
        const reason = container.querySelector('#attendance-substitute-reason')?.value || '';

        const actionRes = await substituteProfessional(uuid, {
          professional_uuid: professionalUuid,
          reason,
          sync_schedule_event_professional: true,
        });

        if (!actionRes.success) {
          if (Array.isArray(actionRes.errors)) {
            actionRes.errors.forEach((entry) => {
              const field = String(entry?.field || '').trim();
              const message = String(entry?.message || '').trim();
              if (field && message) substitutionErrors[field] = message;
            });
          }
          await renderPage();
          const firstError = Array.isArray(actionRes.errors) && actionRes.errors[0]?.message
            ? String(actionRes.errors[0].message)
            : '';
          return toast.error(firstError || actionRes.message || 'Erro ao registrar substituição.');
        }

        toast.success('Substituição registrada com sucesso.');
        await renderPage();
      });

      const doUploadAudio = async (file, customDuration = null) => {
        if (!file) {
          toast.warning('Selecione um arquivo de áudio.');
          return;
        }

        if (file.size > AUDIO_LIMIT_MB * 1024 * 1024) {
          toast.error(`Áudio acima do limite permitido (${AUDIO_LIMIT_MB}MB).`);
          return;
        }

        const title = container.querySelector('#attendance-audio-title')?.value?.trim() || 'Áudio clínico';

        const uploadRes = await uploadAudioFile(file, uuid);
        if (!uploadRes.success) {
          const firstError = Array.isArray(uploadRes.errors) && uploadRes.errors[0]?.message
            ? String(uploadRes.errors[0].message)
            : '';
          return toast.error(firstError || uploadRes.message || 'Não foi possível enviar o arquivo de áudio.');
        }

        const fileUuid = uploadRes?.data?.uuid;
        if (!fileUuid) {
          return toast.error('Falha ao obter identificador do arquivo enviado.');
        }

        const attachRes = await attachAudio(uuid, fileUuid, title, customDuration);
        if (!attachRes.success) {
          const firstError = Array.isArray(attachRes.errors) && attachRes.errors[0]?.message
            ? String(attachRes.errors[0].message)
            : '';
          return toast.error(firstError || attachRes.message || 'Não foi possível anexar áudio ao atendimento.');
        }

        toast.success('Áudio anexado com sucesso.');
        const fileInput = container.querySelector('#attendance-audio-file');
        if (fileInput) fileInput.value = '';
        await renderPage();
      };

      container.querySelector('[data-audio-upload]')?.addEventListener('click', async () => {
        const file = container.querySelector('#attendance-audio-file')?.files?.[0] || null;
        await doUploadAudio(file);
      });

      container.querySelectorAll('[data-audio-delete]').forEach((button) => {
        button.addEventListener('click', async () => {
          const audioUuid = button.getAttribute('data-audio-delete');
          if (!audioUuid) return;
          const removeRes = await deleteAudioRecord(audioUuid);
          if (!removeRes.success) {
            return toast.error(removeRes.message || 'Não foi possível remover o áudio.');
          }
          toast.success('Áudio removido com sucesso.');
          await renderPage();
        });
      });

      const audioElements = container.querySelectorAll('audio[data-download-url]');
      for (const audioElement of audioElements) {
        const downloadUrl = audioElement.getAttribute('data-download-url');
        if (!downloadUrl) continue;

        try {
          const blob = await fetchAuthorizedBlob(downloadUrl);
          const objectUrl = URL.createObjectURL(blob);
          audioElement.src = objectUrl;
        } catch {
          const hint = document.createElement('p');
          hint.className = 'text-xs text-muted';
          hint.textContent = 'Não foi possível carregar o player deste áudio.';
          audioElement.insertAdjacentElement('afterend', hint);
        }
      }

      container.querySelectorAll('[data-audio-download-url]').forEach((button) => {
        button.addEventListener('click', async () => {
          const downloadUrl = button.getAttribute('data-audio-download-url');
          const fileName = button.getAttribute('data-audio-download-name') || 'audio.bin';
          if (!downloadUrl) return;

          try {
            const blob = await fetchAuthorizedBlob(downloadUrl);
            const objectUrl = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = objectUrl;
            link.download = fileName;
            document.body.appendChild(link);
            link.click();
            link.remove();
            URL.revokeObjectURL(objectUrl);
          } catch (error) {
            toast.error(error instanceof Error ? error.message : 'Não foi possível baixar o áudio.');
          }
        });
      });

      if (recordingSupported) {
        let recorder = null;
        let stream = null;
        let chunks = [];

        const startBtn = container.querySelector('[data-audio-record-start]');
        const stopBtn = container.querySelector('[data-audio-record-stop]');
        const stateEl = container.querySelector('#audio-recording-state');

        startBtn?.addEventListener('click', async () => {
          try {
            stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            recorder = new MediaRecorder(stream);
            chunks = [];

            recorder.ondataavailable = (event) => {
              if (event.data && event.data.size > 0) chunks.push(event.data);
            };

            recorder.onstop = async () => {
              const blob = new Blob(chunks, { type: recorder.mimeType || 'audio/webm' });
              const ext = blob.type.includes('ogg') ? 'ogg' : blob.type.includes('wav') ? 'wav' : blob.type.includes('mp4') ? 'm4a' : 'webm';
              const file = new File([blob], `gravacao-${Date.now()}.${ext}`, { type: blob.type || 'audio/webm' });

              if (stateEl) stateEl.textContent = 'Enviando gravação...';
              await doUploadAudio(file);
              if (stateEl) stateEl.textContent = 'Gravação enviada';

              if (stream) stream.getTracks().forEach((track) => track.stop());
            };

            recorder.start();
            if (stateEl) stateEl.textContent = 'Gravando...';
            if (startBtn) startBtn.disabled = true;
            if (stopBtn) stopBtn.disabled = false;
          } catch {
            toast.error('Não foi possível iniciar gravação neste navegador/dispositivo.');
          }
        });

        stopBtn?.addEventListener('click', () => {
          if (recorder && recorder.state !== 'inactive') recorder.stop();
          if (stateEl) stateEl.textContent = 'Finalizando gravação...';
          if (startBtn) startBtn.disabled = false;
          if (stopBtn) stopBtn.disabled = true;
        });
      }
    };

    await renderPage();
  },
};
