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
    host: true,
    port: 5180,
    strictPort: true,
    cors: true,
  },
});  
