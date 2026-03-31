import { createRouter, createWebHistory } from 'vue-router';

import MesasView from './pages/MesasView.vue';
import MesaDetalleView from './pages/MesaDetalleView.vue';

const routes = [
    {
        path: '/',
        redirect: { name: 'mesas' },
    },
    {
        path: '/mesero',
        redirect: { name: 'mesas' },
    },
    {
        path: '/mesero/mesa/:id',
        redirect: (to) => ({ name: 'mesa-detalle', params: { id: to.params.id } }),
    },
    {
        path: '/mesas',
        name: 'mesas',
        component: MesasView,
    },
    {
        path: '/mesas/:id',
        name: 'mesa-detalle',
        component: MesaDetalleView,
        props: true,
    },
    {
        path: '/admin/mesas',
        name: 'admin-mesas',
        component: () => import('../pages/admin/MesasAdmin.vue'),
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

export default router;
