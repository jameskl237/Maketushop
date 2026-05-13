<script setup>
import LanguageSwitcher from '@/components/LanguageSwitcher.vue';
import ThemeToggle from '@/components/ThemeToggle.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="relative flex min-h-screen flex-col items-center justify-center overflow-hidden bg-shop-bg px-4 py-8 dark:bg-[#0D0820]">
        <!-- Background blobs -->
        <div class="pointer-events-none absolute inset-0 overflow-hidden">
            <div class="absolute -left-24 -top-24 h-72 w-72 rounded-full bg-primary/20 blur-3xl dark:bg-primary/10" />
            <div class="absolute -bottom-24 -right-24 h-72 w-72 rounded-full bg-orange/20 blur-3xl dark:bg-orange/10" />
            <div class="absolute left-1/2 top-1/2 h-48 w-48 -translate-x-1/2 -translate-y-1/2 rounded-full bg-primary/10 blur-2xl" />
        </div>

        <!-- Top row: back link + controls -->
        <div class="relative z-10 mb-6 flex w-full max-w-md items-center justify-between">
            <Link
                href="/"
                class="flex items-center gap-1.5 text-[12px] font-medium text-shop-muted transition hover:text-foreground"
            >
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="M19 12H5M5 12l7-7M5 12l7 7" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                {{ $t('auth.backToLanding') }}
            </Link>
            <div class="flex items-center gap-2">
                <LanguageSwitcher />
                <ThemeToggle :floating="false" class="h-8 w-8 rounded-xl border border-border bg-white/70 backdrop-blur dark:bg-white/5" />
            </div>
        </div>

        <!-- Logo -->
        <Link href="/" class="relative z-10 mb-6 flex items-center gap-2">
            <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-primary text-white shadow-lg shadow-primary/30">
                <span class="font-display text-base font-black">M</span>
            </div>
            <span class="font-display text-[20px] font-extrabold tracking-tight text-foreground">
                Maketu<span class="text-primary">Shop</span>
            </span>
        </Link>

        <!-- Card -->
        <div class="relative z-10 w-full max-w-md">
            <div class="glass-card px-5 py-6 sm:px-7 sm:py-8">
                <slot />
            </div>
        </div>
    </div>
</template>
