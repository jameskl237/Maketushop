<script setup>
import { Button } from '@/components/ui/button';
import LanguageSwitcher from '@/components/LanguageSwitcher.vue';
import ThemeToggle from '@/components/ThemeToggle.vue';
import { Link } from '@inertiajs/vue3';
import { Menu } from 'lucide-vue-next';
import { onMounted, onUnmounted, ref } from 'vue';

/**
 * @component LandingHeader
 * @description Header sticky avec changement de fond au scroll et menu mobile.
 * @example <LandingHeader :nav-items="navItems" />
 */
const props = defineProps({
    navItems: { type: Array, default: () => [] },
});

const scrolled = ref(false);
const open = ref(false);
const mobileMenuRef = ref(null);

const onScroll = () => {
    scrolled.value = window.scrollY > 12;
};

const onDocumentClick = (event) => {
    if (!open.value) return;
    if (mobileMenuRef.value?.contains(event.target)) return;
    open.value = false;
};

const handleNavClick = (evt, href) => {
    if (!href?.startsWith('#')) return;
    evt.preventDefault();
    document.querySelector(href)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    open.value = false;
};

onMounted(() => {
    window.addEventListener('scroll', onScroll, { passive: true });
    document.addEventListener('click', onDocumentClick);
});
onUnmounted(() => {
    window.removeEventListener('scroll', onScroll);
    document.removeEventListener('click', onDocumentClick);
});
</script>

<template>
    <header
        class="sticky top-0 z-40 transition-all duration-300"
        :class="scrolled ? 'border-b border-border bg-background/95 backdrop-blur' : 'bg-transparent'"
    >
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
            <a href="#top" class="flex items-center gap-2" @click="handleNavClick($event, '#top')">
                <img src="/images/Maketu1.png" alt="Logo MaketuShop" class="h-9 w-auto" />
                <span class="text-base font-semibold text-foreground">MaketuShop</span>
            </a>

            <nav aria-label="Navigation principale" class="hidden items-center gap-6 md:flex">
                <a
                    v-for="item in navItems"
                    :key="item.href"
                    :href="item.href"
                    class="text-sm text-muted-foreground transition-colors hover:text-foreground"
                    @click="handleNavClick($event, item.href)"
                >
                    {{ item.label }}
                </a>
            </nav>

            <div class="hidden items-center gap-2 md:flex">
                <LanguageSwitcher :floating="false" />
                <ThemeToggle :floating="false" />
                <Link :href="route('login')">
                    <Button variant="ghost">Connexion</Button>
                </Link>
                <Link :href="route('products.index')">
                    <Button>Commencer</Button>
                </Link>
            </div>

            <div ref="mobileMenuRef" class="relative md:hidden">
                <Button
                    variant="outline"
                    size="icon"
                    aria-label="Ouvrir le menu mobile"
                    :aria-expanded="open"
                    aria-haspopup="menu"
                    @click.stop="open = !open"
                >
                        <Menu class="h-4 w-4" />
                </Button>
                <div
                    v-if="open"
                    class="absolute right-0 top-12 z-50 w-[88vw] max-w-sm rounded-lg border border-border bg-background p-3 shadow-lg"
                >
                    <div class="flex flex-col gap-3">
                        <a
                            v-for="item in navItems"
                            :key="`mobile-${item.href}`"
                            :href="item.href"
                            class="rounded-lg border border-border px-4 py-3 text-sm"
                            @click="handleNavClick($event, item.href)"
                        >
                            {{ item.label }}
                        </a>
                        <Link :href="route('login')" @click="open = false">
                            <Button variant="outline" class="w-full">Connexion</Button>
                        </Link>
                        <Link :href="route('products.index')" @click="open = false">
                            <Button class="w-full">Commencer</Button>
                        </Link>
                        <div class="mt-2 flex gap-2">
                            <LanguageSwitcher :floating="false" />
                            <ThemeToggle :floating="false" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>
</template>


