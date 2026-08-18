import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';
import terser from '@rollup/plugin-terser';
import obfuscatorPlugin from 'vite-plugin-javascript-obfuscator';

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
    ],

    build: {
        // Terser zamiast esbuild jako minifier (na produkcji obsługiwany przez plugin powyżej).
        minify: isProd ? 'terser' : 'esbuild',
    },

    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
