import defaultTheme from "tailwindcss/defaultTheme";
import forms from "@tailwindcss/forms";

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: "class",

    content: [
        "./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php",
        "./storage/framework/views/*.php",
        "./resources/views/**/*.blade.php",
        "./resources/js/**/*.vue",
        "./resources/js/**/*.js",
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ["Inter", "Outfit", ...defaultTheme.fontFamily.sans],
            },

            colors: {
                // ── Admin / CRM palette (RGB format for opacity support) ───────
                "admin-primary": "rgb(var(--color-primary) / <alpha-value>)",
                "admin-highlight":
                    "rgb(var(--color-highlight) / <alpha-value>)",
                "admin-lilac": "rgb(var(--color-lilac) / <alpha-value>)",
                "admin-alert": "rgb(var(--color-alert) / <alpha-value>)",
                "admin-bg": "rgb(var(--color-bg) / <alpha-value>)",
                "admin-surface": "rgb(var(--color-surface) / <alpha-value>)",
                "admin-border": "rgb(var(--color-border) / <alpha-value>)",

                // ── Storefront palette (RGB format for opacity support) ────────
                "store-primary":
                    "rgb(var(--color-store-primary) / <alpha-value>)",
                "store-secondary":
                    "rgb(var(--color-store-secondary) / <alpha-value>)",
                "store-bg": "rgb(var(--color-store-bg) / <alpha-value>)",

                // Platform brands
                whatsapp: "rgb(var(--color-whatsapp) / <alpha-value>)",
                telegram: "rgb(var(--color-telegram) / <alpha-value>)",
            },

            borderRadius: {
                xl: "12px",
                "2xl": "16px",
                "3xl": "24px",
            },

            boxShadow: {
                card: "var(--shadow-card)",
                "card-hover": "var(--shadow-card-hover)",
                sidebar: "var(--shadow-sidebar)",
            },

            animation: {
                "fade-in": "fadeIn .2s ease-out",
                "slide-in-left": "slideInLeft .25s ease-out",
                "slide-up": "slideUp .2s ease-out",
                pulse: "pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite",
            },

            keyframes: {
                fadeIn: { from: { opacity: 0 }, to: { opacity: 1 } },
                slideInLeft: {
                    from: { transform: "translateX(-100%)" },
                    to: { transform: "translateX(0)" },
                },
                slideUp: {
                    from: { opacity: 0, transform: "translateY(8px)" },
                    to: { opacity: 1, transform: "translateY(0)" },
                },
            },
        },
    },

    plugins: [forms],
};
