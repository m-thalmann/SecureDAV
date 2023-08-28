import daisyui from 'daisyui';
import defaultTheme from 'tailwindcss/defaultTheme';

/** @type {import('tailwindcss').Config} */
export default {
  content: [
    './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
    './storage/framework/views/*.php',
    './resources/views/**/*.blade.php',
  ],

  theme: {
    extend: {
      fontFamily: {
        sans: ['Nunito', ...defaultTheme.fontFamily.sans],
      },
    },
  },

  plugins: [daisyui],

  daisyui: {
    themes: [
      {
        dark: {
          primary: '#22c55e',
          secondary: '#10b981',
          accent: '#2dd4bf',
          neutral: '#134e4a',
          'base-100': '#374151',
          'base-200': '#1f2937',
          'base-300': '#111827',
          info: '#a5f3fc',
          success: '#a3e635',
          warning: '#fde047',
          error: '#ef4444',
        },
      },
    ],
  },
};
