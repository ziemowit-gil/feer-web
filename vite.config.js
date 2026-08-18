import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';
import terser from '@rollup/plugin-terser';
import obfuscatorPlugin from 'vite-plugin-javascript-obfuscator';
import compression from 'vite-plugin-compression';
import { visualizer } from 'rollup-plugin-visualizer';

const isProd = process.env.NODE_ENV === 'production';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            fonts: [
                bunny('Roboto', {
                    weights: [400, 500, 700],
                }),
            ],
        }),
        tailwindcss(),

        // Terser — głębsza minifikacja JS niż domyślny esbuild (produkcja).
        ...(isProd ? [terser({
            compress: {
                drop_console: true,
                drop_debugger: true,
                passes: 2,
            },
            format: {
                comments: false,
            },
        })] : []),

        // JavaScript Obfuscator — zaciemnianie kodu (produkcja).
        // Zwiększa rozmiar ~20 %, mocno utrudnia debugowanie.
        // Wyłącz ustawiając VITE_OBFUSCATE=false w .env.
        ...(isProd && process.env.VITE_OBFUSCATE !== 'false' ? [obfuscatorPlugin({
            options: {
                compact: true,
                controlFlowFlattening: false,
                deadCodeInjection: false,
                identifierNamesGenerator: 'mangled',
                renameGlobals: false,
                stringArray: true,
                stringArrayEncoding: ['base64'],
                stringArrayThreshold: 0.5,
                transformObjectKeys: false,
                selfDefending: false,
            },
            // Nie zaciemniaj Alpine.js — reaguje na nazwy funkcji w HTML.
            exclude: [/alpine/i, /node_modules/],
        })] : []),

        // Pre-kompresja gzip i brotli — Nginx serwuje gotowe pliki bez obciążenia CPU.
        // Wymaga: gzip_static on; / brotli_static on; w konfiguracji Nginx.
        ...(isProd ? [
            compression({ algorithm: 'gzip', ext: '.gz' }),
            compression({ algorithm: 'brotliCompress', ext: '.br' }),
        ] : []),

        // Mapa treemap paczki — generuje public/build/stats.html po każdym buildzie.
        visualizer({
            filename: 'public/build/stats.html',
            open: false,
            gzipSize: true,
            brotliSize: true,
            template: 'treemap',
        }),
    ],

    build: {
        // Vite 8 używa rolldown — domyślna minifikacja wbudowana.
        // Terser jako dodatkowy pass (plugin powyżej) działa niezależnie.
        minify: isProd ? 'rolldown-minify' : false,
    },

    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
