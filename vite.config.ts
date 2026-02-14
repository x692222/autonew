import {defineConfig} from 'vite'
import vue from '@vitejs/plugin-vue'
import laravel from 'laravel-vite-plugin';
import path from 'path'
import { quasar } from '@quasar/vite-plugin'
import { fileURLToPath } from 'node:url'

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/backoffice/js/backoffice.js',
                // 'resources/backoffice/css/backoffice.css',

                // 'resources/website/js/website.js',
                // 'resources/website/css/aos.css',
                //
                // 'resources/websiteassets/js/bootstrap.bundle.min.js',
                // 'resources/websiteassets/js/aos.js',
                // 'resources/websiteassets/js/main.js',
            ],
            refresh: true, // enable full-page reload on Blade changes
        }),
        vue({
            template: {
                // required so Vue templates resolve asset URLs the same way Mix did
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        quasar({
            sassVariables: fileURLToPath(
                new URL('./quasar-variables.sass', import.meta.url)
            )
        })
    ],
    resolve: {
        alias: {
            'bo@': path.resolve(__dirname, 'resources/backoffice/js'), // Alias for the `js` backoffice folder
            's@': path.resolve(__dirname, 'resources/website/js'), // Alias for the `js` website folder
        },
    },
    css: {
        devSourcemap: true, // Enable source maps for CSS in development
        preprocessorOptions: {
            scss: {
                // Optional: You can add some shared SCSS variables or mixins here if needed
            },
        },
    },
})
