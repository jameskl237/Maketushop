<script setup>
import { Button } from '@/components/ui/button';
import { Link, usePage } from '@inertiajs/vue3';
import { Grid2X2, HelpCircle, Home, LogIn, ShoppingBag } from 'lucide-vue-next';
import { computed } from 'vue';

const page = usePage();

const isActive = (href) => {
    if (href.startsWith('#')) return false;
    return page.url === href || page.url.startsWith(`${href}/`);
};

const tabs = computed(() => [
    { label: 'Accueil', href: '/', icon: Home },
    { label: 'Catégories', href: '/#categories', icon: Grid2X2 },
    { label: 'Commencer', href: route('products.index'), icon: ShoppingBag, primary: true },
    { label: 'Comment', href: '/#how-it-works', icon: HelpCircle },
    { label: 'Connexion', href: route('login'), icon: LogIn },
]);
</script>

<template>
    <div class="fixed bottom-6 left-1/2 z-50 w-full max-w-[calc(100%-32px)] -translate-x-1/2 px-4 md:hidden">
        <nav class="flex h-[64px] items-center justify-around rounded-[24px] border border-white/10 bg-[#0F0A1E]/85 px-2 backdrop-blur-xl shadow-[0_8px_32px_0_rgba(0,0,0,0.4)]">
            <Link
                v-for="tab in tabs"
                :key="tab.label"
                :href="tab.href"
                class="relative flex flex-col items-center justify-center gap-1 transition-all duration-300 active:scale-90"
                :class="isActive(tab.href) ? 'text-white' : 'text-white/40'"
            >
                <template v-if="tab.primary">
                    <div class="relative -mt-10 flex h-14 w-14 items-center justify-center rounded-[20px] bg-mesh-gradient text-white shadow-[0_8px_20px_rgba(91,46,255,0.4)] transition-transform hover:scale-105 active:scale-95">
                        <component :is="tab.icon" class="h-6 w-6" />
                        <!-- Subtle pulse effect -->
                        <div class="absolute inset-0 animate-ping rounded-[20px] bg-white/20 opacity-20 [animation-duration:3s]"></div>
                    </div>
                </template>
                <template v-else>
                    <div class="flex flex-col items-center">
                        <component :is="tab.icon" class="h-5 w-5" />
                        <span class="mt-1 text-[9px] font-medium tracking-wide uppercase">{{ tab.label }}</span>
                        <!-- Active Indicator Dot -->
                        <div v-if="isActive(tab.href)" class="absolute -bottom-1 h-1 w-1 rounded-full bg-pink"></div>
                    </div>
                </template>
            </Link>
        </nav>
    </div>
</template>
