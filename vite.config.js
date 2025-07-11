import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/calls.js', // Ajouté pour les appels
                'resources/css/chat.css', // Ajouté pour le CSS du chat
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
