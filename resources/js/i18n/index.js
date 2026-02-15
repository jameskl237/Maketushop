import { createI18n } from 'vue-i18n';
import en from '@/i18n/locales/en.json';
import fr from '@/i18n/locales/fr.json';

const LOCALE_STORAGE_KEY = 'maketushop-locale';
const SUPPORTED_LOCALES = ['fr', 'en'];
const DEFAULT_LOCALE = 'fr';

const normalizeLocale = (value) => (value || '').toLowerCase().split('-')[0];

const isSupportedLocale = (value) => SUPPORTED_LOCALES.includes(value);

const resolveInitialLocale = () => {
    if (typeof localStorage !== 'undefined') {
        const savedLocale = normalizeLocale(localStorage.getItem(LOCALE_STORAGE_KEY));
        if (isSupportedLocale(savedLocale)) {
            return savedLocale;
        }
    }

    if (typeof document !== 'undefined') {
        const htmlLocale = normalizeLocale(document.documentElement.lang);
        if (isSupportedLocale(htmlLocale)) {
            return htmlLocale;
        }
    }

    if (typeof navigator !== 'undefined') {
        const browserLocale = normalizeLocale(navigator.language);
        if (isSupportedLocale(browserLocale)) {
            return browserLocale;
        }
    }

    return DEFAULT_LOCALE;
};

export const i18n = createI18n({
    legacy: false,
    globalInjection: true,
    locale: resolveInitialLocale(),
    fallbackLocale: DEFAULT_LOCALE,
    messages: {
        fr,
        en,
    },
});

export const setI18nLocale = (nextLocale) => {
    const locale = normalizeLocale(nextLocale);
    const resolvedLocale = isSupportedLocale(locale) ? locale : DEFAULT_LOCALE;

    i18n.global.locale.value = resolvedLocale;

    if (typeof localStorage !== 'undefined') {
        localStorage.setItem(LOCALE_STORAGE_KEY, resolvedLocale);
    }

    if (typeof document !== 'undefined') {
        document.documentElement.lang = resolvedLocale;
    }
};

export const getI18nLocale = () => i18n.global.locale.value;

