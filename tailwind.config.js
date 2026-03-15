import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', 'Outfit', ...defaultTheme.fontFamily.sans],
            },

            colors: {
                // ── Storefront palette ──────────────────────────
                'store-primary':   '#F67E15',
                'store-secondary': '#06402B',
                'store-bg':        '#F9FBF9',
                'store-surface':   '#FFFFFF',

                // ── Admin / CRM palette ─────────────────────────
                'admin-primary':   '#4020C9',
                'admin-highlight': '#9477ED',
                'admin-lilac':     '#BBACE7',
                'admin-alert':     '#AF5134',
                'admin-bg':        '#F8FAFC',
                'admin-surface':   '#FFFFFF',
                'admin-border':    '#E2E8F0',
            },

            borderRadius: {
                xl: '12px',
                '2xl': '16px',
                '3xl': '24px',
            },

            boxShadow: {
                card:   '0 1px 3px 0 rgba(0,0,0,.06), 0 1px 2px 0 rgba(0,0,0,.04)',
                'card-hover': '0 4px 20px 0 rgba(64,32,201,.12)',
                sidebar: '2px 0 24px 0 rgba(0,0,0,.06)',
            },

            animation: {
                'fade-in':    'fadeIn .2s ease-out',
                'slide-in-left': 'slideInLeft .25s ease-out',
                'slide-up':   'slideUp .2s ease-out',
                pulse: 'pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite',
            },

            keyframes: {
                fadeIn:      { from: { opacity: 0 }, to: { opacity: 1 } },
                slideInLeft: { from: { transform: 'translateX(-100%)' }, to: { transform: 'translateX(0)' } },
                slideUp:     { from: { opacity: 0, transform: 'translateY(8px)' }, to: { opacity: 1, transform: 'translateY(0)' } },
            },
        },
    },

    plugins: [forms],
};
