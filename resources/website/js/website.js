import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { InertiaProgress } from '@inertiajs/progress';
import axios from 'axios';
// import 'bootstrap/dist/js/bootstrap.bundle.min.js';
// import 'bootstrap/dist/css/bootstrap.min.css';
// import jQuery from 'jquery';
import {ZiggyVue} from "ziggy-js";
import Swal from "sweetalert2";

// Set up Axios on the window
window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.interceptors.request.use(config => {
    config.headers.Accept = 'application/json';
    return config;
}, error => Promise.reject(error));

// if (typeof window !== 'undefined') {
//     window.$ = window.jQuery = jQuery;
// }

// export default jQuery;

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
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })

        const app = createApp({render: () => h(App, props)})
            // .component('FontAwesomeIcon', FontAwesomeIcon)
            .use(plugin)
            .use(ZiggyVue);

        app.config.globalProperties.$swal = Swal
        app.provide('swal', Swal)

        app.mount(el);
    },
});
