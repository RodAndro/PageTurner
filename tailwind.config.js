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
                primary: {
                    50: '#f3faf1',
                    100: '#e8f5e3',
                    200: '#cce9c5',
                    300: '#a8dea6',
                    400: '#8cd285',
                    500: '#78b14b',
                    600: '#6a9a42',
                    700: '#588339',
                    800: '#476c30',
                    900: '#355527',
                    950: '#1f3118',
                },
            },
        },
    },

    plugins: [forms],
};
