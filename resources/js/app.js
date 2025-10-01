import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import '@fortawesome/fontawesome-free/css/all.css';
import PostForm from './components/PostForm.vue';
import axios from 'axios';

axios.defaults.withCredentials = true;
axios.defaults.baseURL = 'http://127.0.0.1:8000';

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
