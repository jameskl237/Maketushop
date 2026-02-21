<script setup>
import { useI18n } from 'vue-i18n';
import { computed } from 'vue';

/**
 * @component LandingFooter
 * @description Footer complet avec colonnes de liens et infos marque.
 * @example <LandingFooter />
 */
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
    <footer class="border-t border-border bg-card/50">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-5">
                <div class="lg:col-span-1">
                    <div class="flex items-center gap-2">
                        <img src="/images/Maketu1.png" alt="Logo MaketuShop" class="h-9 w-auto" loading="lazy" />
                        <span class="text-base font-semibold">MaketuShop</span>
                    </div>
                    <p class="mt-4 text-sm text-muted-foreground">{{ t('landing.footerTagline') }}</p>
                </div>

                <div
                    v-for="col in columns"
                    :key="col.title"
                    class="space-y-3"
                >
                    <h3 class="text-sm font-semibold text-foreground">{{ col.title }}</h3>
                    <ul class="space-y-2">
                        <li
                            v-for="item in col.links"
                            :key="item.label"
                            class="text-sm text-muted-foreground transition-colors hover:text-foreground"
                        >
                            <a :href="item.href">{{ item.label }}</a>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="mt-8 border-t border-border pt-6 text-xs text-muted-foreground">
                © {{ new Date().getFullYear() }} MaketuShop. {{ t('landing.footerRights') }}
            </div>
        </div>
    </footer>
</template>


