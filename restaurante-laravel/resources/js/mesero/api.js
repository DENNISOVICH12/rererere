import axios from 'axios';

const meseroHttp = axios.create({
    baseURL: '/api/mesero',
    withCredentials: true,
    headers: { Accept: 'application/json' },
});

const floorHttp = axios.create({
    baseURL: '/api',
    withCredentials: true,
    headers: { Accept: 'application/json' },
});

const inFlightGet = new Map();

const dedupeGet = async (key, requester) => {
    if (inFlightGet.has(key)) {
        return inFlightGet.get(key);
    }

    const request = requester().finally(() => inFlightGet.delete(key));
    inFlightGet.set(key, request);
    return request;
};

export const listActiveOrders = (status = '') =>
    meseroHttp.get('/orders', { params: status ? { status } : {} }).then((r) => r.data.data);

export const getOrder = (orderId) =>
    meseroHttp.get(`/orders/${orderId}`).then((r) => r.data.data);

export const updateOrder = (orderId, payload) =>
    meseroHttp.put(`/orders/${orderId}`, payload).then((r) => r.data.data);

export const updateOrderStatus = (orderId, payload) =>
    axios.put(`/api/mesero/pedidos/${orderId}`, payload, {
        withCredentials: true,
        headers: { Accept: 'application/json' },
    }).then((r) => r.data);

export const deliverOrderGroup = (orderId, group) => 
    floorHttp.put(`/pedidos/${orderId}/entregar/${group}`)
         .then((r) => r.data);
export const requestOrderChange = (orderId, payload = {}) =>
    meseroHttp.post(`/orders/${orderId}/request-change`, payload).then((r) => r.data.data);

export const sendOrderToKitchen = (orderId) =>
    meseroHttp.post(`/orders/${orderId}/send-to-kitchen`).then((r) => r.data.data);

export const deleteOrder = (orderId, payload = {}) =>
    meseroHttp.delete(`/orders/${orderId}`, { data: payload });

export const searchMenuItems = (query = '') =>
    dedupeGet(`menu:${query}`, () => meseroHttp.get('/menu-items', { params: { q: query } }).then((r) => r.data.data));

export const listMesas = () => dedupeGet('mesas:list', () => floorHttp.get('/mesas').then((r) => r.data.data));
export const createMesa = (payload) => floorHttp.post('/mesas', payload).then((r) => r.data);
export const deleteMesa = (id) => floorHttp.delete(`/mesas/${id}`).then((r) => r.data);

export const getMesa = (mesaId) => dedupeGet(`mesa:${mesaId}`, () => floorHttp.get(`/mesas/${mesaId}`).then((r) => r.data.data));

export const getMesaClientes = (mesaId) => floorHttp.get(`/mesas/${mesaId}/clientes`).then((r) => r.data.data.clientes ?? []);

export const createMesaCliente = (mesaId, payload) =>
    floorHttp.post(`/mesas/${mesaId}/clientes`, payload).then((r) => r.data.data);

export const getClientePedidos = (clienteId) =>
    floorHttp.get(`/clientes/${clienteId}/pedidos`).then((r) => r.data);

export const facturarCliente = (clienteId) =>
    floorHttp.post(`/clientes/${clienteId}/facturar`).then((r) => r.data);


export const getMesaPedidos = (mesaId) =>
    dedupeGet(`mesa:${mesaId}:pedidos`, () => floorHttp.get(`/mesas/${mesaId}/pedidos`).then((r) => r.data.data));

export const getNotifications = () =>
    meseroHttp.get('/notifications').then((r) => r.data);

export const markNotificationRead = (id) =>
    meseroHttp.post(`/notifications/${id}/read`).then((r) => r.data);

export const markNotificationsReadAll = () =>
    meseroHttp.post('/notifications/read-all').then((r) => r.data);
