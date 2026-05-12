/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {
      colors: {
        burgundy: {
          500: '#9A2832',
          600: '#7A1E26',
          700: '#5C1519',
          800: '#3D0E10',
          900: '#2A0B0D',
        },
        'cfo-bar': '#8B1D23',
        cream: {
          50:  '#FBF8F4',
          100: '#F5EDE0',
          200: '#EADDCB',
        },
        gold: {
          400: '#D4A574',
          500: '#B88A56',
        },
        ink: {
          500: 'rgba(20,10,12,0.50)',
          700: 'rgba(20,10,12,0.70)',
          900: '#0A0506',
        },
        'gray-cfo': '#6D6E71',
        /* Aliases de compatibilidade */
        'system-wine': '#635962',
        'system-wine-dark': '#7A1E26',
        'system-gray': '#FBF8F4',
        'system-gray-dark': '#F5EDE0',
      },
      fontFamily: {
        display: ["'Instrument Serif'", 'Georgia', 'serif'],
        body:    ["'Geist'", "'Inter'", 'sans-serif'],
        mono:    ["'JetBrains Mono'", 'monospace'],
      },
    },
  },
  plugins: [],
}
