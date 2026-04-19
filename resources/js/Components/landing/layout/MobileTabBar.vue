<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { Home, Grid2x2, ShoppingBag, LogIn, Store } from 'lucide-vue-next';
import { computed } from 'vue';

const { t } = useI18n();
const page = usePage();

const isActive = (href) => {
    if (href.startsWith('#')) return false;
    return page.url === href || page.url.startsWith(href + '/');
};

const tabs = computed(() => [
    { label: t('landing.navHome', 'Accueil'), href: '/', icon: Home, anchor: '#top' },
    { label: t('landing.navCategories', 'Catégories'), href: '#categories', icon: Grid2x2, anchor: '#categories' },
    { label: t('landing.start', 'Explorer'), href: route('products.index'), icon: ShoppingBag, anchor: null, primary: true },
    { label: t('landing.navHowItWorks', 'Comment'), href: '#how-it-works', icon: Store, anchor: '#how-it-works' },
    { label: t('landing.login', 'Connexion'), href: route('login'), icon: LogIn, anchor: null },
]);

const handleClick = (evt, tab) => {
    if (tab.anchor) {
        evt.preventDefault();
        document.querySelector(tab.anchor)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
};
</script>

<template>
    <nav
        aria-label="Navigation mobile"
        class="fixed bottom-0 inset-x-0 z-50 md:hidden"
    >
        <!-- Backdrop blur bar -->
        <div class="border-t border-border/60 bg-card/90 backdrop-blur-xl pb-safe">
            <div class="flex items-stretch">
                <component
                    :is="tab.anchor ? 'a' : Link"
                    v-for="tab in tabs"
                    :key="tab.label"
                    :href="tab.anchor ?? tab.href"
                    class="group relative flex flex-1 flex-col items-center justify-center gap-1 py-3 text-center transition-colors duration-150"
                    :class="[
                        tab.primary
                            ? 'text-primary'
                            : isActive(tab.href) ? 'text-foreground' : 'text-muted-foreground hover:text-foreground'
                    ]"
                    @click="(e) => handleClick(e, tab)"
                >
                    <!-- Primary tab: elevated circle -->
                    <template v-if="tab.primary">
                        <span class="relative -mt-5 flex h-12 w-12 items-center justify-center rounded-full bg-primary text-primary-foreground shadow-[0_4px_16px_-4px_hsl(var(--primary)/0.5)] transition-transform duration-200 active:scale-95">
                            <component :is="tab.icon" class="h-5 w-5" />
                        </span>
                        <span class="text-[10px] font-medium text-primary">{{ tab.label }}</span>
                    </template>

                    <!-- Regular tab -->
                    <template v-else>
                        <component
                            :is="tab.icon"
                            class="h-5 w-5 transition-transform duration-150 group-active:scale-90"
                        />
                        <span class="text-[10px] font-medium leading-none">{{ tab.label }}</span>

                        <!-- Active dot -->
                        <span
                            v-if="isActive(tab.href)"
                            class="absolute bottom-1.5 h-1 w-1 rounded-full bg-primary"
                        />
                    </template>
                </component>
            </div>
        </div>
    </nav>
</template>
