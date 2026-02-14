import {createApp, h} from 'vue';
import {createInertiaApp} from '@inertiajs/vue3';
import {InertiaProgress} from '@inertiajs/progress';
import {ZiggyVue} from 'ziggy-js'
import axios from 'axios';
import { Quasar, Dialog, Notify } from 'quasar'
import quasarIconSet from 'quasar/icon-set/fontawesome-v5'
import '@quasar/extras/fontawesome-v5/fontawesome-v5.css'
import '@quasar/extras/material-icons/material-icons.css'
import 'quasar/src/css/index.sass'

// Set up Axios on the window
window.axios                                             = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.interceptors.request.use(config => {
    config.headers.Accept = 'application/json';
    return config;
}, error => Promise.reject(error));

// Initialize the Inertia progress indicator
InertiaProgress.init();

// Dynamically import all pages from the Pages directory
const pages = import.meta.glob('./Pages/**/*.vue');

// Boot the Inertia app with Vue 3 and Vite
createInertiaApp({
    resolve: name => {
        const importPage = pages[`./Pages/${name}.vue`];
        if (!importPage) {
            throw new Error(`Unknown page: ${name}. Did you create it?`);
        }
        return importPage().then(module => module.default);
    },
    title: title => title ? `${title}` : '',
    setup({el, App, props, plugin}) {
        const app = createApp({render: () => h(App, props)})
            // .component('FontAwesomeIcon', FontAwesomeIcon)
            .use(plugin)
            .use(Quasar, {
                plugins: { Dialog, Notify }, // import Quasar plugins and add here
                iconSet: quasarIconSet,
            })
            .use(ZiggyVue);

        // app.config.globalProperties.$swal = Swal
        // app.provide('swal', Swal)

        app.mount(el);
    },
});
