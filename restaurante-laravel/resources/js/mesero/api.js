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
    meseroHttp.put(`/pedidos/${orderId}/entregar-grupo/${group}`).then((r) => r.data.data);

export const requestOrderChange = (orderId, payload = {}) =>
    meseroHttp.post(`/orders/${orderId}/request-change`, payload).then((r) => r.data.data);

export const sendOrderToKitchen = (orderId) =>
    meseroHttp.post(`/orders/${orderId}/send-to-kitchen`).then((r) => r.data.data);

export const deleteOrder = (orderId, payload = {}) =>
    meseroHttp.delete(`/orders/${orderId}`, { data: payload });

export const searchMenuItems = (query = '') =>
    meseroHttp.get('/menu-items', { params: { q: query } }).then((r) => r.data.data);

export const listMesas = () => floorHttp.get('/mesas').then((r) => r.data.data);

export const getMesa = (mesaId) => floorHttp.get(`/mesas/${mesaId}`).then((r) => r.data.data);

export const getMesaClientes = (mesaId) => floorHttp.get(`/mesas/${mesaId}/clientes`).then((r) => r.data.data.clientes ?? []);

export const createMesaCliente = (mesaId, payload) =>
    floorHttp.post(`/mesas/${mesaId}/clientes`, payload).then((r) => r.data.data);

export const getClientePedidos = (clienteId) =>
    floorHttp.get(`/clientes/${clienteId}/pedidos`).then((r) => r.data);

export const facturarCliente = (clienteId) =>
    floorHttp.post(`/clientes/${clienteId}/facturar`).then((r) => r.data);

export const getMesaPedidos = (mesaId) =>
    floorHttp.get(`/mesas/${mesaId}/pedidos`).then((r) => r.data.data);
