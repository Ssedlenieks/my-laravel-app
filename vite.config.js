import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/js/app.js',
                'resources/css/app.css',
            ],
            refresh: true,
        }),
        vue(),
    ],
    server: {
        proxy: {
            '/lingvanex': 'http://127.0.0.1:8000',
            '/posts': 'http://127.0.0.1:8000',
            '/categories': 'http://127.0.0.1:8000',
            '/login': 'http://127.0.0.1:8000',
            '/register': 'http://127.0.0.1:8000',
            '/user': 'http://127.0.0.1:8000',
        }
    },
});
