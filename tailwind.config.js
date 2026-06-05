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
      screens: {
        xs: '380px',
      },
      colors: {
        va: {
          DEFAULT: '#9a0036',
          50: '#fdf2f5',
          100: '#fce4ec',
          200: '#f9c9d8',
          300: '#f29db7',
          400: '#e76690',
          500: '#d6336c',
          600: '#bd0a52',
          700: '#ad0043',
          800: '#9a0036',
          900: '#7d0029',
          brand: '#9a0036',
        },
        /** Shell / fleet UI — trùng token với LayoutDriver & DriverDashboard */
        driver: {
          bg: '#020B0B',
          surface: '#0f1a17',
          card: '#111f1b',
          elevated: '#152722',
          accent: '#7fdcc8',
          'accent-bright': '#5eead4',
          ink: '#eaf8f5',
          muted: '#9fbdb4',
        },
      },
    },
  },
  plugins: [],
}

