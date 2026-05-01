import { http } from '../../core/services/http.js';

function buildQuery(params = {}) {
  const search = new URLSearchParams();

  Object.entries(params).forEach(([key, value]) => {
    if (value === null || value === undefined) return;

    const normalized = String(value).trim();
    if (normalized === '') return;

    search.set(key, normalized);
  });

  const query = search.toString();
  return query ? `?${query}` : '';
}

function extractItemsFromPaginatedResponse(payload) {
  if (Array.isArray(payload?.items)) {
    return payload.items;
  }

  if (Array.isArray(payload)) {
    return payload;
  }

  return [];
}

export const scheduleService = {
  async listEvents(filters = {}) {
    const perPage = 100;
    let page = 1;
    let total = 0;
    const allItems = [];

    while (true) {
      const query = buildQuery({ ...filters, page, per_page: perPage });
      const res = await http.get(`/api/v1/schedule/events${query}`, { context: 'page' });

      if (!res.success) {
        return {
          ...res,
          data: {
            items: [],
            pagination: { page: 1, per_page: perPage, total: 0 },
          },
        };
      }

      const items = extractItemsFromPaginatedResponse(res?.data);
      const pagination = res?.data?.pagination || {};

      total = Number(pagination.total || items.length || total || 0);
      allItems.push(...items);

      const fetched = page * perPage;
      if (items.length === 0 || fetched >= total || page >= 20) {
        return {
          ...res,
          data: {
            items: allItems,
            pagination: {
              page: 1,
              per_page: perPage,
              total,
            },
          },
        };
      }

      page += 1;
    }
  },

  async getEvent(uuid) {
    return http.get(`/api/v1/schedule/events/${uuid}`, { context: 'page' });
  },

  async createEvent(payload) {
    return http.post('/api/v1/schedule/events', payload, { context: 'page' });
  },

  async updateEvent(uuid, payload) {
    return http.put(`/api/v1/schedule/events/${uuid}`, payload, { context: 'page' });
  },

  async deleteEvent(uuid) {
    return http.delete(`/api/v1/schedule/events/${uuid}`, { context: 'page' });
  },

  async cancelEvent(uuid, cancelReason) {
    return http.post(`/api/v1/schedule/events/${uuid}/cancel`, { cancel_reason: cancelReason }, { context: 'page' });
  },

  async markAbsence(uuid) {
    return http.post(`/api/v1/schedule/events/${uuid}/mark-absence`, {}, { context: 'page' });
  },

  async confirmEvent(uuid) {
    return http.post(`/api/v1/schedule/events/${uuid}/confirm`, {}, { context: 'page' });
  },

  async markDone(uuid) {
    return http.post(`/api/v1/schedule/events/${uuid}/mark-done`, {}, { context: 'page' });
  },

  async rescheduleEvent(uuid, payload) {
    return http.post(`/api/v1/schedule/events/${uuid}/reschedule`, payload, { context: 'page' });
  },

  async listEventTypes() {
    const res = await http.get('/api/v1/schedule/event-types', { context: 'page' });

    return {
      ...res,
      data: extractItemsFromPaginatedResponse(res?.data),
    };
  },

  async listProfessionals() {
    const res = await http.get('/api/v1/professionals?page=1&per_page=100&sort=full_name&direction=asc', { context: 'page' });

    return {
      ...res,
      data: extractItemsFromPaginatedResponse(res?.data),
    };
  },

  async listPatients() {
    const res = await http.get('/api/v1/patients?page=1&per_page=100&sort=full_name&direction=asc', { context: 'page' });

    return {
      ...res,
      data: extractItemsFromPaginatedResponse(res?.data),
    };
  },
};
