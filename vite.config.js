import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import path from 'path';
import { quasar } from '@quasar/vite-plugin';
import { fileURLToPath } from 'node:url';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/backoffice/js/backoffice.js',
                'resources/website/js/website.js',
            ],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        quasar({
            sassVariables: fileURLToPath(
                new URL('./quasar-variables.sass', import.meta.url)
            ),
        }),
    ],
    resolve: {
        alias: {
            'bo@': path.resolve(__dirname, 'resources/backoffice/js'),
            's@': path.resolve(__dirname, 'resources/website/js'),
            'w@': path.resolve(__dirname, 'resources/website/js'),
        },
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
