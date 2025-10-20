import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './app/Modules/**/*.blade.php', // Adicionado para escanear os módulos
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },// Adicionando sua paleta de cores personalizada
            colors: {
                'spigo-dark': '#322C3A',
                'spigo-violet': '#A388BE',
                'spigo-lime': '#D2E28B',
                'spigo-blue': '#1985A1',
                'spigo-light-blue': '#A9E4EF',
            },
        },
    },

    plugins: [forms],
};
