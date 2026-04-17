/** @type {import('tailwindcss').Config} */
export default {
  darkMode: 'class',
  content: [
    './resources/views/**/*.blade.php',
    './resources/js/**/*.vue',
    './resources/js/**/*.js',
  ],
  theme: {
    extend: {
      colors: {
        va: {
          DEFAULT: '#9A0036',
          50: '#fdf2f5',
          100: '#fce7ed',
          700: '#9A0036',
          800: '#7a0029',
          900: '#5c001f',
          brand: '#9A0036',
        },
      },
    },
  },
  plugins: [],
}

