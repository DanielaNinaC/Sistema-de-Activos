import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: [
                'resources/views/**',
                'routes/**',
                'app/Http/**',
            ],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            usePolling: true,
            interval: 1000,
            ignored: ['**/storage/framework/views/**', '**/node_modules/**'],
        },
    },
});
