/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {
      colors: {
        'system-wine': '#635962',
        'system-wine-dark': '#7a0d10',
        'system-gray': '#F6F6F6',
        'system-gray-dark': '#E8E8E8',
      },
    },
  },
  plugins: [],
} 