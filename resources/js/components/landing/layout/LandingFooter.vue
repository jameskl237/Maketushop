<script setup>
import { useI18n } from 'vue-i18n';
import { computed } from 'vue';

const { t } = useI18n();

const columns = computed(() => [
    {
        title: t('landing.footerColProduct'),
        links: [
            { label: t('landing.footerLinkFeatures'), href: '#features' },
            { label: t('landing.footerLinkCategories'), href: '#categories' },
            { label: t('landing.footerLinkPricing'), href: '#' },
            { label: t('landing.footerLinkFaq'), href: '#faq' },
        ],
    },
    {
        title: t('landing.footerColCompany'),
        links: [
            { label: t('landing.footerLinkAbout'), href: '/about' },
            { label: t('landing.footerLinkCareers'), href: '#' },
            { label: t('landing.footerLinkPartners'), href: '#' },
            { label: t('landing.footerLinkContact'), href: '/contact' },
        ],
    },
    {
        title: t('landing.footerColResources'),
        links: [
            { label: t('landing.footerLinkBlog'), href: '#' },
            { label: t('landing.footerLinkSellerGuide'), href: '#' },
            { label: t('landing.footerLinkHelp'), href: '#' },
            { label: t('landing.footerLinkDocs'), href: '#' },
        ],
    },
    {
        title: t('landing.footerColLegal'),
        links: [
            { label: t('landing.footerLinkPrivacy'), href: '#' },
            { label: t('landing.footerLinkTerms'), href: '#' },
            { label: t('landing.footerLinkCookies'), href: '#' },
            { label: t('landing.footerLinkLegalNotice'), href: '#' },
        ],
    },
]);
</script>

<template>
    <footer class="border-t border-border/60 bg-card/40">
        <div class="mx-auto max-w-7xl px-5 py-14 sm:px-8 lg:px-10">
            <div class="grid gap-10 md:grid-cols-2 lg:grid-cols-5">
                <!-- Brand -->
                <div class="lg:col-span-1">
                    <a href="#top" class="group flex items-center gap-2.5">
                        <img src="/images/Maketu1.png" alt="Logo MaketuShop" class="h-8 w-auto transition-transform duration-300 group-hover:scale-105" loading="lazy" />
                        <span class="font-display text-lg font-semibold text-foreground">MaketuShop</span>
                    </a>
                    <p class="mt-4 text-[12px] leading-relaxed text-muted-foreground">
                        {{ t('landing.footerTagline') }}
                    </p>
                </div>

                <!-- Link columns -->
                <div
                    v-for="col in columns"
                    :key="col.title"
                    class="space-y-4"
                >
                    <h3 class="text-[11px] font-semibold uppercase tracking-widest text-foreground">{{ col.title }}</h3>
                    <ul class="space-y-2.5">
                        <li
                            v-for="item in col.links"
                            :key="item.label"
                        >
                            <a
                                :href="item.href"
                                class="text-[12px] text-muted-foreground transition-colors duration-150 hover:text-foreground"
                            >
                                {{ item.label }}
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Bottom bar -->
            <div class="mt-12 flex flex-col items-center justify-between gap-3 border-t border-border/50 pt-6 sm:flex-row">
                <p class="text-[11px] text-muted-foreground">
                    © {{ new Date().getFullYear() }} MaketuShop. {{ t('landing.footerRights') }}
                </p>
                <div class="flex items-center gap-1">
                    <div class="h-1 w-1 rounded-full bg-primary/60" />
                    <div class="h-1 w-1 rounded-full bg-primary/40" />
                    <div class="h-1 w-1 rounded-full bg-primary/20" />
                </div>
            </div>
        </div>
    </footer>
</template>
