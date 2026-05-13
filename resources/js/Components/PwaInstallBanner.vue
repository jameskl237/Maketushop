<script setup>
import { Download, X } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const deferredPrompt = ref(null);
const isVisible = ref(false);
const isInstalled = ref(false);

onMounted(() => {
    if (typeof window === 'undefined') return;

    // Already installed as PWA
    if (window.matchMedia('(display-mode: standalone)').matches) {
        isInstalled.value = true;
        return;
    }

    if (localStorage.getItem('pwa-install-dismissed')) return;

    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt.value = e;
        setTimeout(() => { isVisible.value = true; }, 4000);
    });
});

const install = async () => {
    if (!deferredPrompt.value) return;
    deferredPrompt.value.prompt();
    const { outcome } = await deferredPrompt.value.userChoice;
    deferredPrompt.value = null;
    isVisible.value = false;
    if (outcome === 'accepted') {
        localStorage.setItem('pwa-install-dismissed', '1');
    }
};

const dismiss = () => {
    isVisible.value = false;
    localStorage.setItem('pwa-install-dismissed', '1');
};
</script>

<template>
    <Transition
        enter-active-class="transition duration-500 ease-out"
        enter-from-class="translate-y-full opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transition duration-300 ease-in"
        leave-from-class="translate-y-0 opacity-100"
        leave-to-class="translate-y-full opacity-0"
    >
        <div
            v-if="isVisible && !isInstalled"
            class="fixed bottom-[88px] left-3 right-3 z-[200] md:bottom-6 md:left-auto md:right-6 md:w-80"
        >
            <div class="glass-card overflow-hidden p-4">
                <!-- Top row -->
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-2xl bg-primary text-white shadow-sm">
                            <img src="/images/Maketu_logo.png" alt="Maketu" class="h-7 w-7 object-contain" />
                        </div>
                        <div>
                            <p class="text-sm font-bold text-foreground">{{ t('pwa.installTitle') }}</p>
                            <p class="mt-0.5 text-xs text-muted-foreground">{{ t('pwa.installSubtitle') }}</p>
                        </div>
                    </div>
                    <button
                        @click="dismiss"
                        class="flex-shrink-0 rounded-xl p-1.5 text-muted-foreground hover:bg-black/5 dark:hover:bg-white/10 transition-colors"
                        :aria-label="t('pwa.dismiss')"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <!-- CTA -->
                <button
                    @click="install"
                    class="mt-3 flex w-full items-center justify-center gap-2 rounded-2xl bg-primary px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-primary/90 active:scale-95 transition-all duration-200"
                >
                    <Download class="h-4 w-4" />
                    {{ t('pwa.installButton') }}
                </button>
            </div>
        </div>
    </Transition>
</template>
