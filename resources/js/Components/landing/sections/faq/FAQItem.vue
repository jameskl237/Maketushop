<script setup>
import { ref } from 'vue';

defineProps({
    item: { type: Object, required: true },
});

const open = ref(false);
</script>

<template>
    <div
        class="group overflow-hidden rounded-2xl border border-border/60 bg-card transition-all duration-200"
        :class="open ? 'shadow-sm' : 'hover:border-border'"
    >
        <button
            type="button"
            class="flex w-full items-center justify-between px-6 py-4 text-left"
            :aria-expanded="open"
            @click="open = !open"
        >
            <span class="pr-4 text-[14px] font-semibold text-foreground">{{ item.question }}</span>
            <span
                class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full border border-border/60 text-muted-foreground transition-all duration-300"
                :class="open ? 'bg-primary border-primary text-primary-foreground rotate-45' : 'group-hover:border-primary/40 group-hover:text-primary'"
            >
                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 5v14M5 12h14" />
                </svg>
            </span>
        </button>
        <Transition
            enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="opacity-0 max-h-0"
            enter-to-class="opacity-100 max-h-96"
            leave-active-class="transition-all duration-200 ease-in"
            leave-from-class="opacity-100 max-h-96"
            leave-to-class="opacity-0 max-h-0"
        >
            <div v-if="open" class="overflow-hidden">
                <p class="px-6 pb-5 text-[13px] leading-relaxed text-muted-foreground">
                    {{ item.answer }}
                </p>
            </div>
        </Transition>
    </div>
</template>
