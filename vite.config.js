import { fileURLToPath, URL } from 'node:url';
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import react from '@vitejs/plugin-react';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            refresh: true,
            // Font files are downloaded at build time and served from this app (PRIV-006).
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
                // Arabic body typeface.
                bunny('IBM Plex Sans Arabic', {
                    weights: [400, 500, 600],
                    subsets: ['arabic', 'latin'],
                }),
                // Display typeface for headings in both scripts (Arabic + Latin glyphs in one family).
                bunny('Alexandria', {
                    weights: [500, 600],
                    subsets: ['arabic', 'latin'],
                }),
            ],
        }),
        react(),
        tailwindcss(),
    ],
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
        },
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
