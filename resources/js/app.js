import './bootstrap'
import '../css/app.css'
import { createApp, h } from 'vue'
import { createInertiaApp, router } from '@inertiajs/vue3'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import { ZiggyVue } from '../../vendor/tightenco/ziggy'
import { createPinia } from 'pinia'
import AppLayout from './Layouts/AppLayout.vue'

createInertiaApp({
    title: (title) => `${title} — PingOS`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue'))
        .then((module) => {
            const page = module.default || module;
            
            // Set AppLayout by default, except for Auth pages
            if (name.startsWith('Auth/') || name === 'Error') {
                page.layout = undefined;
            } else {
                page.layout = page.layout || AppLayout;
            }

            return page;
        }),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(createPinia())
            .mount(el)
    },
    progress: {
        color: '#4F46E5',
    },
})

router.on('before', () => {
    try {
        if (window.Echo) {
            Object.keys(window.Echo.connector?.channels || {}).forEach(channel => {
                window.Echo.leave(channel)
            })
        }
    } catch(e) {}
})
