import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import '@fortawesome/fontawesome-free/css/all.css';
import PostForm from './Components/PostForm.vue';
import axios from 'axios';

axios.defaults.withCredentials = true;
axios.defaults.baseURL = 'http://127.0.0.1:8000';

// Dark Mode Manager
class DarkModeManager {
    constructor() {
        this.init();
    }

    init() {
        // Check for saved preference or default to system preference
        const savedTheme = localStorage.getItem('darkMode');
        const systemDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

        let isDark;
        if (savedTheme !== null) {
            // Use saved preference
            isDark = savedTheme === 'true';
        } else {
            // Use system preference
            isDark = systemDark;
        }

        this.setTheme(isDark);

        // Listen for system theme changes
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
            // Only auto-switch if no manual preference is saved
            if (localStorage.getItem('darkMode') === null) {
                this.setTheme(e.matches);
            }
        });
    }

    setTheme(isDark) {
        if (isDark) {
            document.body.classList.add('dark');
        } else {
            document.body.classList.remove('dark');
        }
    }

    toggle() {
        const isDark = document.body.classList.contains('dark');
        const newMode = !isDark;

        this.setTheme(newMode);
        localStorage.setItem('darkMode', newMode.toString());

        return newMode;
    }

    getCurrentMode() {
        return document.body.classList.contains('dark');
    }
}

// Initialize dark mode
const darkModeManager = new DarkModeManager();

// Make it globally available
window.darkModeManager = darkModeManager;

createInertiaApp({
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) })
            .use(plugin)

        app.component('PostForm', PostForm);

        return app.mount(el);
    },
    progress: {
        color: '#4B5563',
    },

});
