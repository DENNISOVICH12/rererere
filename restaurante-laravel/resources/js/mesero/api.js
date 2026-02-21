import axios from 'axios';

const http = axios.create({
    baseURL: '/api/mesero',
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

export const deleteOrder = (orderId, payload = {}) =>
    http.delete(`/orders/${orderId}`, { data: payload });

export const searchMenuItems = (query = '') =>
    http.get('/menu-items', { params: { q: query } }).then((r) => r.data.data);
