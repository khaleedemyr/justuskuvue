import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

const appName = import.meta.env.VITE_APP_NAME || 'Justus Group';
const LOADER_ID = 'global-page-loader';

function ensureGlobalLoader() {
    if (typeof document === 'undefined') {
        return null;
    }

    const existing = document.getElementById(LOADER_ID);
    if (existing) {
        return existing;
    }

    const loader = document.createElement('div');
    loader.id = LOADER_ID;
    loader.className = 'global-page-loader';
    loader.setAttribute('aria-hidden', 'true');
    loader.innerHTML = `
        <div class="global-page-loader__inner">
            <img src="/logobulathitam.png" alt="Justus Group loading" class="global-page-loader__logo" />
            <p class="global-page-loader__text">Loading...</p>
        </div>
    `;

    document.body.appendChild(loader);
    return loader;
}

function showGlobalLoader() {
    const loader = ensureGlobalLoader();
    loader?.classList.add('is-visible');
}

function hideGlobalLoader() {
    const loader = ensureGlobalLoader();
    loader?.classList.remove('is-visible');
}

if (typeof document !== 'undefined') {
    document.addEventListener('inertia:start', showGlobalLoader);
    document.addEventListener('inertia:finish', hideGlobalLoader);
    document.addEventListener('inertia:error', hideGlobalLoader);
    document.addEventListener('inertia:invalid', hideGlobalLoader);
}

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
