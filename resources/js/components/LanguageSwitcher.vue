<script setup>
import { getI18nLocale, setI18nLocale } from '@/i18n';
import { Globe } from 'lucide-vue-next';
import { ref, onMounted, onUnmounted } from 'vue';
import { useI18n } from 'vue-i18n';

defineProps({
    floating: { type: Boolean, default: false },
});

const { t } = useI18n();
const locale = ref(getI18nLocale());
const open = ref(false);
const dropdownRef = ref(null);

const LOCALES = [
    { code: 'fr', label: 'Français', flag: '🇫🇷' },
    { code: 'en', label: 'English', flag: '🇬🇧' },
];

const selectLocale = (code) => {
    setI18nLocale(code);
    locale.value = code;
    open.value = false;
};

const currentLocale = () => LOCALES.find(l => l.code === locale.value) || LOCALES[0];

const onClickOutside = (e) => {
    if (dropdownRef.value && !dropdownRef.value.contains(e.target)) {
        open.value = false;
    }
};

onMounted(() => document.addEventListener('mousedown', onClickOutside));
onUnmounted(() => document.removeEventListener('mousedown', onClickOutside));
</script>

<template>
    <div ref="dropdownRef" class="relative">
        <button
            type="button"
            class="flex shrink-0 items-center gap-1 rounded-2xl border border-border/60 bg-card/80 px-2 py-1.5 text-xs font-semibold text-foreground backdrop-blur-xl transition-all hover:bg-card active:scale-95 shadow-sm sm:gap-1.5 sm:px-2.5"
            :aria-label="t('language.select')"
            @click="open = !open"
        >
            <Globe class="h-3.5 w-3.5 shrink-0 text-muted-foreground" />
            <span class="hidden uppercase sm:inline">{{ locale }}</span>
            <svg
                class="hidden h-3 w-3 shrink-0 text-muted-foreground transition-transform duration-200 sm:block"
                :class="open && 'rotate-180'"
                fill="none" stroke="currentColor" viewBox="0 0 24 24"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="scale-95 opacity-0 -translate-y-1"
            enter-to-class="scale-100 opacity-100 translate-y-0"
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="scale-100 opacity-100"
            leave-to-class="scale-95 opacity-0"
        >
            <div
                v-if="open"
                class="absolute right-0 top-full mt-1.5 z-[200] min-w-[130px] overflow-hidden rounded-2xl border border-border/60 bg-card/90 shadow-xl backdrop-blur-xl"
            >
                <button
                    v-for="lang in LOCALES"
                    :key="lang.code"
                    type="button"
                    class="flex w-full items-center gap-2.5 px-3 py-2.5 text-left text-xs font-medium transition-colors hover:bg-primary/8"
                    :class="lang.code === locale ? 'text-primary font-bold' : 'text-foreground'"
                    @click="selectLocale(lang.code)"
                >
                    <span class="text-base leading-none">{{ lang.flag }}</span>
                    <span>{{ lang.label }}</span>
                    <svg v-if="lang.code === locale" class="ml-auto h-3.5 w-3.5 text-primary" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
        </Transition>
    </div>
</template>
