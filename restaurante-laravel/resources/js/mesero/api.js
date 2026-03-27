import axios from 'axios';

const http = axios.create({
    baseURL: '/api/mesero',
    withCredentials: true,
    headers: {
        Accept: 'application/json',
    },
});

export const listActiveOrders = (status = '') =>
    http.get('/orders', { params: status ? { status } : {} }).then((r) => r.data.data);

export const getOrder = (orderId) =>
    http.get(`/orders/${orderId}`).then((r) => r.data.data);

export const updateOrder = (orderId, payload) =>
    http.put(`/orders/${orderId}`, payload).then((r) => r.data.data);

export const updateOrderStatus = (orderId, payload) =>
    axios.put(`/api/mesero/pedidos/${orderId}`, payload, {
        withCredentials: true,
        headers: {
            Accept: 'application/json',
        },
    }).then((r) => r.data);

export const requestOrderChange = (orderId, payload = {}) =>
    http.post(`/orders/${orderId}/request-change`, payload).then((r) => r.data.data);

export const sendOrderToKitchen = (orderId) =>
    http.post(`/orders/${orderId}/send-to-kitchen`).then((r) => r.data.data);

export const deleteOrder = (orderId, payload = {}) =>
    http.delete(`/orders/${orderId}`, { data: payload });

export const searchMenuItems = (query = '') =>
    http.get('/menu-items', { params: { q: query } }).then((r) => r.data.data);
