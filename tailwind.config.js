/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './resources/views/**/*.blade.php',
        './resources/js/**/*.{js,vue}',
    ],
    theme: {
        extend: {
            colors: {
                trigger: {
                    50: '#f0fdf4',
                    100: '#dcfce7',
                    500: '#22c55e',
                    600: '#16a34a',
                    700: '#15803d',
                },
                condition: {
                    50: '#fefce8',
                    100: '#fef9c3',
                    500: '#eab308',
                    600: '#ca8a04',
                    700: '#a16207',
                },
                action: {
                    50: '#eff6ff',
                    100: '#dbeafe',
                    500: '#3b82f6',
                    600: '#2563eb',
                    700: '#1d4ed8',
                },
            },
        },
    },
    plugins: [],
}
