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

