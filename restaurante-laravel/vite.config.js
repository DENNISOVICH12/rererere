import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
  plugins: [
    laravel({
      input: [
        'resources/css/app.css',
        'resources/js/app.js',
        'resources/js/mesero/main.js',
      ],
      refresh: true,
    }),
    vue(),
  ],

  server: {
  host: '0.0.0.0',
  port: 5180,
  strictPort: true,
  cors: true,
  hmr: {
    host: '192.168.80.31',
    port: Number(process.env.VITE_PORT_HOST || 5180),
  },
},  
});