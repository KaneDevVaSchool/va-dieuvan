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
          DEFAULT: '#78001e',
          50: '#fdf2f4',
          100: '#fce8ec',
          700: '#78001e',
          800: '#5c0017',
          900: '#450011',
          brand: '#78001e',
        },
      },
    },
  },
  plugins: [],
}

