import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/pages/pay.js',
                'node_modules/intl-tel-input/build/css/intlTelInput.css',
                'node_modules/intl-tel-input/build/js/intlTelInput.min.js'
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
