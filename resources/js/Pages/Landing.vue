<script setup>
import LandingFooter from '@/components/landing/layout/LandingFooter.vue';
import LandingHeader from '@/components/landing/layout/LandingHeader.vue';
import MobileTabBar from '@/components/landing/layout/MobileTabBar.vue';
import OrientationPopup from '@/components/ui/OrientationPopup.vue';
import PwaInstallBanner from '@/components/PwaInstallBanner.vue';
import DynamicCategoriesSection from '@/components/landing/sections/categories/DynamicCategoriesSection.vue';
import { Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import {
    BadgeCheck,
    PackageSearch,
    ShieldCheck,
    ShoppingBag,
    Store,
    Truck,
} from 'lucide-vue-next';
import { computed, defineAsyncComponent } from 'vue';

const HeroSection = defineAsyncComponent(() => import('@/components/landing/sections/hero/HeroSection.vue'));
const FeaturesSection = defineAsyncComponent(() => import('@/components/landing/sections/features/FeaturesSection.vue'));
const HowItWorksSection = defineAsyncComponent(() => import('@/components/landing/sections/how-it-works/HowItWorksSection.vue'));
const CTASplit = defineAsyncComponent(() => import('@/components/landing/sections/cta/CTASplit.vue'));
const FAQSection = defineAsyncComponent(() => import('@/components/landing/sections/faq/FAQSection.vue'));
const CTASection = defineAsyncComponent(() => import('@/components/landing/sections/cta/CTASection.vue'));
const NewsletterSection = defineAsyncComponent(() => import('@/components/landing/sections/newsletter/NewsletterSection.vue'));

const props = defineProps({
    canLogin: { type: Boolean, default: true },
    canRegister: { type: Boolean, default: true },
    dynamicCategories: { type: Array, default: () => [] },
});

const { t } = useI18n();

const navItems = computed(() => [
    { label: t('landing.navFeatures'), href: '#features' },
    { label: t('landing.navHowItWorks'), href: '#how-it-works' },
    { label: t('landing.navCategories'), href: '#categories' },
    { label: t('landing.navFaq'), href: '#faq' },
]);

const heroStats = computed(() => [
    { value: 50, suffix: '+', label: t('landing.heroArtisans') },
    { value: 800, suffix: '+', label: t('public.products') },
    { value: 500, suffix: '+', label: t('landing.heroClients') },
]);

const features = computed(() => [
    { icon: ShoppingBag, title: t('landing.feature1Title'), description: t('landing.feature1Description'), color: 'blue' },
    { icon: Truck, title: t('landing.feature2Title'), description: t('landing.feature2Description'), color: 'green' },
    { icon: ShieldCheck, title: t('landing.feature3Title'), description: t('landing.feature3Description'), color: 'purple' },
    { icon: Store, title: t('landing.feature4Title'), description: t('landing.feature4Description'), color: 'orange' },
    { icon: BadgeCheck, title: t('landing.feature5Title'), description: t('landing.feature5Description'), color: 'teal' },
    { icon: PackageSearch, title: t('landing.feature6Title'), description: t('landing.feature6Description'), color: 'pink' },
]);

const steps = computed(() => [
    { title: t('landing.step1Title'), description: t('landing.step1Description') },
    { title: t('landing.step2Title'), description: t('landing.step2Description') },
    { title: t('landing.step3Title'), description: t('landing.step3Description') },
]);

const faqs = computed(() => [
    { question: t('landing.faq1Question'), answer: t('landing.faq1Answer') },
    { question: t('landing.faq2Question'), answer: t('landing.faq2Answer') },
    { question: t('landing.faq3Question'), answer: t('landing.faq3Answer') },
    { question: t('landing.faq4Question'), answer: t('landing.faq4Answer') },
    { question: t('landing.faq5Question'), answer: t('landing.faq5Answer') },
    { question: t('landing.faq6Question'), answer: t('landing.faq6Answer') },
]);
</script>

<template>
    <Head :title="t('landing.metaTitle')" />

    <div id="top" class="relative min-h-screen overflow-hidden bg-[#F7F6FF] text-foreground pb-16 md:pb-0 dark:bg-[#0F0A1E]">
        <!-- Global Background Elements -->
        <div class="fixed inset-0 z-0 overflow-hidden pointer-events-none">
            <div class="absolute -top-[10%] -left-[10%] w-[40%] h-[40%] bg-primary/10 rounded-full blur-[120px] animate-blob"></div>
            <div class="absolute top-[20%] -right-[5%] w-[35%] h-[35%] bg-orange-500/10 rounded-full blur-[100px] animate-blob [animation-delay:2s]"></div>
            <div class="absolute bottom-[10%] left-[5%] w-[30%] h-[30%] bg-primary/5 rounded-full blur-[100px] animate-blob [animation-delay:4s]"></div>
        </div>

        <LandingHeader :nav-items="navItems" class="relative z-50" />

        <main class="relative z-10 animate-reveal-fade">
            <HeroSection :stats="heroStats" />
            <FeaturesSection :features="features" />
            <div class="relative py-12">
                <div class="absolute inset-0 h-px bg-gradient-to-r from-transparent via-primary/20 to-transparent"></div>
            </div>
            <HowItWorksSection :steps="steps" />
            <DynamicCategoriesSection :categories="dynamicCategories" />
            <CTASplit />
            <FAQSection :faqs="faqs" />
            <CTASection />
            <NewsletterSection />
        </main>

        <OrientationPopup
            :title="t('pwa.welcomeTitle')"
            :description="t('pwa.welcomeDescription')"
            storage-key="welcome_popup"
            :delay="3000"
        />

        <OrientationPopup
            :title="t('pwa.sellerTitle')"
            :description="t('pwa.sellerDescription')"
            storage-key="seller_popup"
            :delay="15000"
        >
            <template #icon>
                <Store class="h-5 w-5" />
            </template>
        </OrientationPopup>

        <PwaInstallBanner />

        <LandingFooter class="relative z-10" />
        <MobileTabBar />
    </div>
</template>
