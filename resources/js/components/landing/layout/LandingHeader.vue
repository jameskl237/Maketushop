<script setup>
import { Button } from '@/components/ui/button';
import LanguageSwitcher from '@/components/LanguageSwitcher.vue';
import ThemeToggle from '@/components/ThemeToggle.vue';
import { Link } from '@inertiajs/vue3';
import { Menu, X } from 'lucide-vue-next';
import { onMounted, onUnmounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps({
    navItems: { type: Array, default: () => [] },
});
const { t } = useI18n();

const scrolled = ref(false);
const open = ref(false);
const mobileMenuRef = ref(null);

const onScroll = () => {
    scrolled.value = window.scrollY > 16;
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
        class="sticky top-0 z-40 transition-all duration-500"
        :class="scrolled
            ? 'bg-background/90 backdrop-blur-xl shadow-[0_1px_0_0_hsl(var(--border))]'
            : 'bg-transparent'"
    >
        <div class="mx-auto flex h-[70px] max-w-7xl items-center justify-between px-5 sm:px-8 lg:px-10">

            <!-- Logo -->
            <a href="#top" class="group flex items-center gap-3" @click="handleNavClick($event, '#top')">
                <img src="/images/Maketu1.png" alt="Logo MaketuShop" class="h-9 w-auto transition-transform duration-300 group-hover:scale-105" />
                <span class="font-display text-xl font-semibold tracking-wide text-foreground">MaketuShop</span>
            </a>

            <!-- Nav desktop -->
            <nav aria-label="Navigation principale" class="hidden items-center gap-8 md:flex">
                <a
                    v-for="item in navItems"
                    :key="item.href"
                    :href="item.href"
                    class="relative text-[13px] font-medium tracking-wide text-muted-foreground transition-colors duration-200 hover:text-foreground after:absolute after:-bottom-0.5 after:left-0 after:h-px after:w-0 after:bg-primary after:transition-all after:duration-300 hover:after:w-full"
                    @click="handleNavClick($event, item.href)"
                >
                    {{ item.label }}
                </a>
            </nav>

            <!-- Actions desktop -->
            <div class="hidden items-center gap-2 md:flex">
                <LanguageSwitcher :floating="false" />
                <ThemeToggle :floating="false" />
                <Link :href="route('login')">
                    <Button variant="ghost" class="h-9 px-5 text-[13px] font-medium tracking-wide">
                        {{ t('landing.login') }}
                    </Button>
                </Link>
                <Link :href="route('products.index')">
                    <Button class="h-9 rounded-full px-6 text-[13px] font-medium tracking-wide shadow-sm">
                        {{ t('landing.start') }}
                    </Button>
                </Link>
            </div>

            <!-- Burger mobile -->
            <div ref="mobileMenuRef" class="relative md:hidden">
                <Button
                    variant="ghost"
                    size="icon"
                    class="h-9 w-9 rounded-full"
                    :aria-label="t('landing.openMobileMenu')"
                    :aria-expanded="open"
                    aria-haspopup="menu"
                    @click.stop="open = !open"
                >
                    <Transition name="icon-swap" mode="out-in">
                        <X v-if="open" class="h-4 w-4" />
                        <Menu v-else class="h-4 w-4" />
                    </Transition>
                </Button>

                <Transition name="dropdown">
                    <div
                        v-if="open"
                        class="absolute right-0 top-12 z-50 w-[88vw] max-w-sm overflow-hidden rounded-2xl border border-border bg-card/95 shadow-xl backdrop-blur-xl"
                    >
                        <div class="flex flex-col gap-1 p-3">
                            <a
                                v-for="item in navItems"
                                :key="`mobile-${item.href}`"
                                :href="item.href"
                                class="rounded-xl px-4 py-3 text-[13px] font-medium text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                                @click="handleNavClick($event, item.href)"
                            >
                                {{ item.label }}
                            </a>
                            <div class="my-1 h-px bg-border" />
                            <Link :href="route('login')" @click="open = false">
                                <Button variant="outline" class="w-full rounded-xl text-[13px]">
                                    {{ t('landing.login') }}
                                </Button>
                            </Link>
                            <Link :href="route('products.index')" @click="open = false">
                                <Button class="w-full rounded-xl text-[13px]">
                                    {{ t('landing.start') }}
                                </Button>
                            </Link>
                            <div class="mt-1 flex gap-2">
                                <LanguageSwitcher :floating="false" />
                                <ThemeToggle :floating="false" />
                            </div>
                        </div>
                    </div>
                </Transition>
            </div>
        </div>
    </header>
</template>

<style scoped>
.dropdown-enter-active,
.dropdown-leave-active { transition: opacity 0.2s ease, transform 0.2s ease; }
.dropdown-enter-from,
.dropdown-leave-to { opacity: 0; transform: translateY(-6px); }

.icon-swap-enter-active,
.icon-swap-leave-active { transition: opacity 0.15s, transform 0.15s; }
.icon-swap-enter-from,
.icon-swap-leave-to { opacity: 0; transform: rotate(15deg) scale(0.8); }
</style>
