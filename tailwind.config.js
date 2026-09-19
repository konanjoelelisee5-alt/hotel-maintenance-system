import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './app/**/*.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                // Archivo devient la police principale (refonte) ; Figtree reste en repli
                // pour les pages pas encore portées, le temps de la transition.
                sans: ['Archivo', 'Figtree', ...defaultTheme.fontFamily.sans],
                mono: ['"IBM Plex Mono"', ...defaultTheme.fontFamily.mono],
            },
            colors: {
                // Échelle historique (navy-50…900) conservée pour les ~90 vues pas encore
                // portées, + les clés DEFAULT/dark/light de la nouvelle maquette : Tailwind
                // fusionne les deux (bg-navy, bg-navy-800 coexistent).
                navy: {
                    DEFAULT: '#0E2136',
                    dark: '#0B1B2C',
                    light: '#16344F',
                    50: '#eef3f8',
                    100: '#d6e2ee',
                    200: '#adc5dd',
                    300: '#7fa3c8',
                    400: '#4d7ba8',
                    500: '#2d5a88',
                    600: '#1e4568',
                    700: '#17354f',
                    800: '#0f2942',
                    900: '#0a1c2e',
                },
                gold: {
                    DEFAULT: '#B58435',
                    50: '#faf7ef',
                    100: '#f2ead2',
                    200: '#e4d3a3',
                    300: '#d6bb74',
                    400: '#c9a961',
                    500: '#b8934a',
                    600: '#96753a',
                    700: '#75592d',
                },
                // Nouveaux tokens de la refonte (palette "editorial" navy/gold/canvas).
                blue: '#26496B',
                red: '#B3261E',
                amber: '#B4740F',
                green: '#1E7A55',
                'ink-grey': '#8A8578',
                canvas: '#EDEAE3',
                paper: '#FAF8F4',
                line: '#E2DCD0',
                'line-soft': '#F3EFE6',
                success: '#123A2C',
            },
        },
    },

    plugins: [forms],
};