/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.jsx',
    ],
    theme: {
        extend: {
            colors: {
                brand: {
                    900: '#3B0764',
                    700: '#5B1F63',
                    600: '#8A2E93',
                    500: '#9C3FA8',
                    400: '#B457C0',
                    100: '#F3E8FD',
                    50: '#FAF5FE',
                },
                thead: {
                    DEFAULT: '#5B3159',
                },
                logout: {
                    DEFAULT: '#E9634C',
                },
                ink: {
                    950: '#1C1B22',
                    900: '#141417',
                    700: '#48454F',
                    500: '#78757F',
                    300: '#D2CFD6',
                    100: '#EFEDF1',
                    50: '#F8F7FA',
                },
                verify: {
                    700: '#0B6E4F',
                    100: '#DCEFE6',
                    50: '#F0FDF4',
                },
                pending: {
                    700: '#9A5B0A',
                    100: '#F5E9D3',
                    50: '#FFFBEB',
                },
                danger: {
                    700: '#B3261E',
                    100: '#FBDDDB',
                    50: '#FEF2F2',
                },
            },
            fontFamily: {
                display: ['Poppins', 'sans-serif'],
                body: ['Inter', 'sans-serif'],
                mono: ['"IBM Plex Mono"', 'monospace'],
            },
        },
    },
    plugins: [],
};