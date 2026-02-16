<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import LanguageSwitcher from '@/components/LanguageSwitcher.vue';
import ThemeToggle from '@/components/ThemeToggle.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div
        class="flex min-h-screen flex-col items-center bg-gradient-to-b from-background via-primary/5 to-accent/30 px-4 pb-6 pt-16 sm:justify-center sm:py-0"
    >
        <LanguageSwitcher />
        <ThemeToggle />
        <div class="mb-2">
            <Link href="/">
                <ApplicationLogo class="h-16 w-16 fill-current text-primary sm:h-20 sm:w-20" />
            </Link>
        </div>
        <Link
            href="/"
            class="mb-4 inline-flex items-center rounded-md border border-border px-3 py-2 text-sm font-medium text-muted-foreground transition hover:bg-accent hover:text-foreground"
        >
            {{ $t('auth.backToLanding') }}
        </Link>

        <div
            class="mt-6 w-full max-w-xl overflow-hidden rounded-2xl border border-border/60 bg-card/90 px-4 py-4 shadow-2xl sm:max-w-2xl sm:px-6 sm:py-6"
        >
            <slot />
        </div>
    </div>
</template>
