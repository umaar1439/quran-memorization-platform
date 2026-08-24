import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import rtl from 'tailwindcss-rtl';

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
        },
    },

    // دعم RTL عبر tailwindcss-rtl (لأن الإصدار المستخدم من Tailwind هو v3 عبر Breeze)
    plugins: [forms, rtl],
};
