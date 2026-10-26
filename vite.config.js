import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/buscador.js', 'resources/js/producto-imagenes.js', 'resources/js/producto-picker.js'],
            refresh: true,
            fonts: [
                bunny('Inter', {
                    weights: [400, 500, 600, 700],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        host: '0.0.0.0',
        // Vite escucha en 5175; Caddy ocupa el 5173 para terminar el TLS
        port: 5175,
        strictPort: true,
        allowedHosts: ['macomputers.localhost'],
        hmr: {
            host: 'macomputers.localhost',
            protocol: 'wss',
            clientPort: 5173,
        },
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
