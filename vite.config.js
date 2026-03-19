import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import vue from "@vitejs/plugin-vue";

export default defineConfig({
    plugins: [
        laravel({
            input: "resources/js/app.js",
            ssr: "resources/js/ssr.js",
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    // 1. Moved optimizeDeps OUTSIDE of the server block
    optimizeDeps: {
        include: [
            "vue",
            "@inertiajs/vue3",
            "chart.js",
            "vue-chartjs",
            "lodash",
            "axios",
        ],
    },
    server: {
        host: "0.0.0.0",
        port: 5173, // It's best to explicitly declare the port
        hmr: {
            host: "localhost",
        },
        // 2. Removed the polling configuration to fix the infinite loading
    },
});
