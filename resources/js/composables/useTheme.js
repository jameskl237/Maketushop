import { computed, ref } from 'vue';

const STORAGE_KEY = 'maketushop-theme';
const theme = ref('light');

function applyTheme(nextTheme) {
    theme.value = nextTheme === 'dark' ? 'dark' : 'light';

    if (typeof document !== 'undefined') {
        document.documentElement.classList.toggle('dark', theme.value === 'dark');
    }

    if (typeof localStorage !== 'undefined') {
        localStorage.setItem(STORAGE_KEY, theme.value);
    }
}

function getPreferredTheme() {
    if (typeof localStorage !== 'undefined') {
        const savedTheme = localStorage.getItem(STORAGE_KEY);
        if (savedTheme === 'dark' || savedTheme === 'light') {
            return savedTheme;
        }
    }

    if (typeof window !== 'undefined' && window.matchMedia) {
        return window.matchMedia('(prefers-color-scheme: dark)').matches
            ? 'dark'
            : 'light';
    }

    return 'light';
}

export function initTheme() {
    applyTheme(getPreferredTheme());
}

export function useTheme() {
    const isDark = computed(() => theme.value === 'dark');

    const toggleTheme = () => {
        applyTheme(isDark.value ? 'light' : 'dark');
    };

    return {
        theme,
        isDark,
        toggleTheme,
        setTheme: applyTheme,
    };
}

