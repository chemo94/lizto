const BASE = '/api';

async function request(url, options = {}) {
  const res = await fetch(`${BASE}${url}`, {
    headers: { 'Content-Type': 'application/json', ...options.headers },
    ...options,
  });
  const data = await res.json();
  if (!res.ok || data.status === 'error') {
    throw new Error(data.message?.[0] || data.message || 'Error del servidor');
  }
  return data;
}

export const api = {
  lookupRuc: (ruc) =>
    request('/kiosk/lookup-ruc', {
      method: 'POST',
      body: JSON.stringify({ ruc }),
    }),

  login: (storeId, pin) =>
    request('/kiosk/login', {
      method: 'POST',
      body: JSON.stringify({ store_id: storeId, pin }),
    }),

  validate: (token) =>
    request('/kiosk/validate', { headers: { Authorization: `Bearer ${token}` } }),

  menu: (storeId, token) =>
    request(`/kiosk/menu/${storeId}`, { headers: { Authorization: `Bearer ${token}` } }),

  tables: (storeId, token) =>
    request(`/kiosk/tables/${storeId}`, { headers: { Authorization: `Bearer ${token}` } }),

  areas: (storeId, token) =>
    request(`/kiosk/tables/${storeId}`, { headers: { Authorization: `Bearer ${token}` } }),

  paymentMethods: (storeId, token) =>
    request(`/kiosk/payment-methods/${storeId}`, { headers: { Authorization: `Bearer ${token}` } }),

  createOrder: (orderData, token) =>
    request('/kiosk/order/create', {
      method: 'POST',
      headers: { Authorization: `Bearer ${token}` },
      body: JSON.stringify(orderData),
    }),

  activeOrders: (storeId, token) =>
    request(`/kiosk/orders/active/${storeId}`, { headers: { Authorization: `Bearer ${token}` } }),

  logout: (token) =>
    request('/kiosk/logout', {
      method: 'POST',
      headers: { Authorization: `Bearer ${token}` },
    }),
};
