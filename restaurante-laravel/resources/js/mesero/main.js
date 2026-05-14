import '../../css/app.css';
import { createApp } from 'vue';
import App from './App.vue';
import router from './router';
import '../echo.js'

createApp(App)
    .use(router)
    .mount('#mesero-app');
