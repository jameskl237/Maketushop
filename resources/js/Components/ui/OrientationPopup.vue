<script setup>
import { X } from 'lucide-vue-next';
import { ref, onMounted } from 'vue';

const props = defineProps({
    title: { type: String, required: true },
    description: { type: String, required: true },
    storageKey: { type: String, required: true },
    delay: { type: Number, default: 2000 },
});

const isVisible = ref(false);

const closePopup = () => {
    isVisible.value = false;
    localStorage.setItem(props.storageKey, 'true');
};

onMounted(() => {
    const hasSeen = localStorage.getItem(props.storageKey);
    if (!hasSeen) {
        setTimeout(() => {
            isVisible.value = true;
        }, props.delay);
    }
});
</script>

<template>
    <Transition
        enter-active-class="transition duration-500 ease-out"
        enter-from-class="translate-y-4 opacity-0 scale-95"
        enter-to-class="translate-y-0 opacity-100 scale-100"
        leave-active-class="transition duration-300 ease-in"
        leave-from-class="translate-y-0 opacity-100 scale-100"
        leave-to-class="translate-y-4 opacity-0 scale-95"
    >
        <div v-if="isVisible" class="fixed bottom-24 right-4 z-[100] w-64 md:bottom-6 md:right-6">
            <div class="relative overflow-hidden rounded-2xl border border-white/20 bg-white/80 p-4 shadow-2xl backdrop-blur-xl dark:bg-slate-900/80">
                <!-- Close Button -->
                <button
                    @click="closePopup"
                    class="absolute right-2 top-2 rounded-full p-1 text-muted-foreground hover:bg-slate-100 dark:hover:bg-slate-800"
                >
                    <X class="h-4 w-4" />
                </button>

                <!-- Indicator Icon -->
                <div class="mb-3 flex h-8 w-8 items-center justify-center rounded-lg bg-orange-500 text-white shadow-lg">
                    <slot name="icon">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </slot>
                </div>

                <h4 class="font-display text-sm font-black text-foreground">{{ title }}</h4>
                <p class="mt-1 text-xs leading-relaxed text-muted-foreground">{{ description }}</p>

                <div class="mt-3 flex justify-end">
                    <button
                        @click="closePopup"
                        class="text-[10px] font-black uppercase tracking-widest text-primary hover:underline"
                    >
                        Compris !
                    </button>
                </div>

                <!-- Animated Background Mesh (subtle) -->
                <div class="absolute -right-4 -top-4 -z-10 h-16 w-16 rounded-full bg-orange-500/10 blur-xl"></div>
            </div>
        </div>
    </Transition>
</template>
