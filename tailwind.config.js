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
          DEFAULT: '#8B1538',
          50: '#fdf2f4',
          100: '#fce7ec',
          700: '#8B1538',
          800: '#6f112d',
          900: '#5a0e25',
        },
      },
    },
  },
  plugins: [],
}

