import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                navy: {
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
                    50: '#faf7ef',
                    100: '#f2ead2',
                    200: '#e4d3a3',
                    300: '#d6bb74',
                    400: '#c9a961',
                    500: '#b8934a',
                    600: '#96753a',
                    700: '#75592d',
                },
            },
        },
    },

    plugins: [forms],
};