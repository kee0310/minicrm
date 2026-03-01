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
        },
    },

    plugins: [forms],

    safelist: [
        'bg-gray-100',
        'text-gray-800',
        'bg-blue-100',
        'text-blue-800',
        'bg-yellow-100',
        'text-yellow-800',
        'bg-purple-100',
        'text-purple-800',
        'bg-orange-100',
        'text-orange-800',
        'bg-green-100',
        'text-green-800',
        'bg-indigo-100',
        'text-indigo-800',
        'bg-emerald-100',
        'text-emerald-800',
        'bg-teal-100',
        'bg-olive-500',
        'text-teal-800',
        'px-2',
        'py-1',
        'text-xs',
        'font-semibold',
        'rounded-full',
        'bg-red-100',
        'text-red-800',
    ],
};
