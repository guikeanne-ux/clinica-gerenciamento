const token = localStorage.getItem('token');
if (!token) location.href = '../auth/login.html';

const feedback = document.getElementById('feedback');

async function api(url, method = 'GET', body = null) {
  const res = await fetch(url, {
    method,
    headers: {
      'Content-Type': 'application/json',
      Authorization: 'Bearer ' + token,
    },
    body: body ? JSON.stringify(body) : null,
  });

  const json = await res.json();
  return { res, json };
}

function notify(ok, message, data = null) {
  feedback.innerHTML = `<div class="alert ${ok ? 'alert-success' : 'alert-error'}">${message}</div>`;
  if (data !== null) {
    const pre = document.createElement('pre');
    pre.textContent = JSON.stringify(data, null, 2);
    feedback.appendChild(pre);
  }
}

async function ensurePermission() {
  const { res, json } = await api('/api/v1/auth/me');
  if (!res.ok) {
    localStorage.removeItem('token');
    location.href = '../auth/login.html';
    return false;
  }

  const perms = json.data.permissions || [];
  if (!perms.includes('professional_payment.view')) {
    document.body.innerHTML = '<main class="main-content"><section class="section"><div class="alert alert-error">Acesso negado a dados sigilosos de repasse.</div></section></main>';
    return false;
  }

  return true;
}

async function loadTables() {
  const search = document.getElementById('search').value;
  const status = document.getElementById('status').value;
  const { json } = await api('/api/v1/payment-tables?search=' + encodeURIComponent(search) + '&status=' + encodeURIComponent(status));

  const container = document.getElementById('tables-list');
  container.innerHTML = '';

  (json.data.items || []).forEach((item) => {
    const card = document.createElement('article');
    card.className = 'card';
    card.innerHTML = `
      <h3>${item.name}</h3>
      <p>Tipo: ${item.calculation_type}</p>
      <p>Status: ${item.status}</p>
      <p>Vigência: ${item.effective_start_date} até ${item.effective_end_date || 'aberta'}</p>
      <div class="row-wrap">
        <button class="btn btn-sm" data-edit="${item.uuid}">Editar</button>
      </div>
    `;

    container.appendChild(card);
  });

  document.querySelectorAll('[data-edit]').forEach((btn) => {
    btn.addEventListener('click', async () => {
      const { json: row } = await api('/api/v1/payment-tables/' + btn.dataset.edit);
      const form = document.getElementById('table-form');
      Object.entries(row.data).forEach(([key, value]) => {
        const field = form.querySelector(`[name="${key}"]`);
        if (field) field.value = value || '';
      });
    });
  });
}

async function saveTable() {
  const form = document.getElementById('table-form');
  const data = Object.fromEntries(new FormData(form));
  const isEdit = !!data.uuid;
  const url = isEdit ? '/api/v1/payment-tables/' + data.uuid : '/api/v1/payment-tables';
  const method = isEdit ? 'PUT' : 'POST';
  const { res, json } = await api(url, method, data);
  notify(res.ok, json.message, res.ok ? json.data : json.errors || null);
  if (res.ok) loadTables();
}

async function simulate() {
  const form = document.getElementById('simulate-form');
  const data = Object.fromEntries(new FormData(form));
  const professionalUuid = data.professional_uuid;
  delete data.professional_uuid;

  const { res, json } = await api('/api/v1/professionals/' + professionalUuid + '/simulate-payout', 'POST', data);
  notify(res.ok, json.message, json.data || json.errors || null);
}

if (await ensurePermission()) {
  document.getElementById('btn-search').addEventListener('click', loadTables);
  document.getElementById('save-table').addEventListener('click', saveTable);
  document.getElementById('simulate').addEventListener('click', simulate);
  loadTables();
}
