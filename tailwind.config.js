import defaultTheme from 'tailwindcss/defaultTheme';
import colors from 'tailwindcss/colors';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Silo's forest-green mark, standing in for Breeze's stock
                // indigo so every existing indigo-* utility (buttons, links,
                // focus rings, actives) picks up the brand color for free.
                // See BRAND.md for the source palette.
                indigo: {
                    50: '#EFF4EF',
                    100: '#DEE9DE',
                    200: '#BDD3BE',
                    300: '#97B69A',
                    400: '#729875',
                    500: '#547B57',
                    600: '#2F4B33',
                    700: '#263D2A',
                    800: '#1E3021',
                    900: '#182619',
                    950: '#0E170F',
                },
                // Silo's grain-gold, standing in for Breeze's stock amber
                // (used for the starred-entry indicator).
                amber: {
                    ...colors.amber,
                    400: '#E8B84B',
                    500: '#C48F35',
                    600: '#B9792B',
                },
            },
        },
    },

    plugins: [forms, typography],
};
