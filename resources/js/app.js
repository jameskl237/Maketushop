import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createPinia } from 'pinia';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import PhoneVerificationModal from '@/components/PhoneVerificationModal.vue';
import { initTheme } from '@/composables/useTheme';
import { initViewTransitions } from '@/composables/useViewTransition';
import { i18n, getI18nLocale, setI18nLocale } from '@/i18n';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';
initTheme();
initViewTransitions();

// Image en échec : on tente d'abord de réparer l'URL avant d'afficher le visuel
// MaketuShop. Cas couverts : URL absolue construite avec un autre APP_URL
// (http://localhost/storage/…, http au lieu de https, ancien domaine — ex. panier
// mémorisé dans le navigateur) et chemin brut du disque (« products/x.jpg »).
const repairImageUrl = (img) => {
    const raw = img.getAttribute('src') || '';
    if (!raw || raw.startsWith('data:')) return null;
    if (!/^(https?:)?\/\//i.test(raw) && !raw.startsWith('/')) {
        return `/storage/${raw.replace(/^(public|storage)\//, '')}`;
    }
    try {
        const url = new URL(raw, window.location.origin);
        const index = url.pathname.indexOf('/storage/');
        if (index === -1) return null;
        const fixed = `${window.location.origin}${url.pathname.slice(index)}`;
        return fixed !== url.href ? fixed : null;
    } catch {
        return null;
    }
};

document.addEventListener('error', (event) => {
    const img = event.target;
    if (!(img instanceof HTMLImageElement) || img.dataset.fallback) return;

    if (!img.dataset.repaired) {
        img.dataset.repaired = '1';
        const fixed = repairImageUrl(img);
        if (fixed) {
            img.src = fixed;
            return;
        }
    }

    img.dataset.fallback = '1';
    img.src = '/images/Maketu1.png';
    img.classList.add('object-contain', 'p-4', 'opacity-80');
}, true);

// Register PWA service worker
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {});
    });
}

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        const pinia = createPinia();
        const app = createApp({ render: () => [h(App, props), h(PhoneVerificationModal)] })
            .use(plugin)
            .use(pinia)
            .use(i18n)
            .use(ZiggyVue)
            ;

        setI18nLocale(getI18nLocale());
        app.config.globalProperties.$setLocale = setI18nLocale;
        // Montant lisible : « 2 250 FCFA » au lieu de « 2250.41 FCFA ».
        app.config.globalProperties.$fcfa = (value) =>
            `${new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 0 }).format(Number(value) || 0)} FCFA`;

        return app.mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
