/** @type {import('tailwindcss').Config} */
export default {
  content: ['./index.html', './src/**/*.{js,jsx,ts,tsx}'],
  theme: {
    extend: {
      colors: {
        primary: {
          50: '#fdf0f0',
          100: '#fccccc',
          200: '#f99a9a',
          300: '#f67e7e',
          400: '#f05252',
          500: '#ff6b6b',
          600: '#e54848',
          700: '#d94545',
          800: '#b93a3a',
          900: '#9b2c2c',
        },
      },
    },
  },
  plugins: [
    function ({ addBase }) {
      addBase({
        ':focus-visible': {
          outline: '2px solid #2563eb',
          outlineOffset: '2px',
        },
      });
    },
  ],
}
