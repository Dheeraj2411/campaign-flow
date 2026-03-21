import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            ssr: 'resources/js/ssr.js',
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
    resolve: {
        alias: {
            '@': '/resources/js',
        },
    },
    build: {
        chunkSizeWarningLimit: 1000,
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (id.includes('apexcharts') || id.includes('vue3-apexcharts')) {
                        return 'vendor-charts'
                    }
                    if (id.includes('@vue-flow')) {
                        return 'vendor-flow'
                    }
                    if (id.includes('node_modules/vue/') || id.includes('@inertiajs') || id.includes('@vue/')) {
                        return 'vendor-vue'
                    }
                    if (id.includes('pinia') || id.includes('axios') || id.includes('lodash')) {
                        return 'vendor-utils'
                    }
                    if (id.includes('pusher') || id.includes('laravel-echo')) {
                        return 'vendor-ws'
                    }
                },
            },
        },
    },
})
