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

// Image produit / boutique introuvable : on affiche le visuel MaketuShop plutôt
// qu'une icône cassée et le texte alternatif (une seule tentative par image).
document.addEventListener('error', (event) => {
    const img = event.target;
    if (!(img instanceof HTMLImageElement) || img.dataset.fallback) return;
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
