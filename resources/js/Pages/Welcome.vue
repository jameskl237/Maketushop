<script setup>
import LandingFooter from '@/components/landing/layout/LandingFooter.vue';
import LandingHeader from '@/components/landing/layout/LandingHeader.vue';
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
// const PartnersSection = defineAsyncComponent(() => import('@/components/landing/sections/partners/PartnersSection.vue'));
const FeaturesSection = defineAsyncComponent(() => import('@/components/landing/sections/features/FeaturesSection.vue'));
const HowItWorksSection = defineAsyncComponent(() => import('@/components/landing/sections/how-it-works/HowItWorksSection.vue'));
const CategoriesSection = defineAsyncComponent(() => import('@/components/landing/sections/categories/CategoriesSection.vue'));
const CTASplit = defineAsyncComponent(() => import('@/components/landing/sections/cta/CTASplit.vue'));
// const TestimonialsSection = defineAsyncComponent(() => import('@/components/landing/sections/testimonials/TestimonialsSection.vue'));
const FAQSection = defineAsyncComponent(() => import('@/components/landing/sections/faq/FAQSection.vue'));
const CTASection = defineAsyncComponent(() => import('@/components/landing/sections/cta/CTASection.vue'));
const NewsletterSection = defineAsyncComponent(() => import('@/components/landing/sections/newsletter/NewsletterSection.vue'));

defineProps({
    canLogin: { type: Boolean, default: true },
    canRegister: { type: Boolean, default: true },
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

const categories = computed(() => [
    { id: 1, name: 'Artisanat', count: 1234, image: '/images/artisanat.png' },
    { id: 2, name: 'Maison', count: 842, image: '/images/maison.png' },
    { id: 3, name: 'Mode', count: 1640, image: '/images/mode.png' },
    { id: 4, name: 'Beauté', count: 920, image: '/images/beaute.png' },
    { id: 5, name: 'Électronique', count: 630, image: '/images/electronique.png' },
    { id: 6, name: 'Alimentation', count: 1150, image: '/images/alimentation.png' },
    { id: 7, name: 'Sport', count: 455, image: '/images/sport.png' },
    { id: 8, name: 'Accessoires', count: 780, image: '/images/accessoire.png' },
]);

const testimonials = [
    { name: 'Marie D.', role: 'Cliente', quote: "Super expérience d'achat, livraison rapide et service top.", avatar: '/images/Maketu_coeur.png' },
    { name: 'Samir K.', role: 'Vendeur', quote: 'Ma boutique a gagné en visibilité dès le premier mois.', avatar: '/images/Maketa.png' },
    { name: 'Aline M.', role: 'Cliente', quote: 'Interface claire, paiement sécurisé, je recommande.', avatar: '/images/Maketu_panier.png' },
    { name: 'Joseph T.', role: 'Vendeur', quote: 'Les statistiques m’aident à piloter mes ventes.', avatar: '/images/Maketu_logo.png' },
    { name: 'Nadia P.', role: 'Cliente', quote: 'Très bon choix de produits locaux et qualité au rendez-vous.', avatar: '/images/Maketu_phone.png' },
    { name: 'Franck E.', role: 'Vendeur', quote: 'Les outils marketing intégrés sont vraiment utiles.', avatar: '/images/Maketu_ordi.png' },
];

const faqs = computed(() => [
    { question: t('landing.faq1Question'), answer: t('landing.faq1Answer') },
    { question: t('landing.faq2Question'), answer: t('landing.faq2Answer') },
    { question: t('landing.faq3Question'), answer: t('landing.faq3Answer') },
    { question: t('landing.faq4Question'), answer: t('landing.faq4Answer') },
    { question: t('landing.faq5Question'), answer: t('landing.faq5Answer') },
    { question: t('landing.faq6Question'), answer: t('landing.faq6Answer') },
]);

// const partnerLogos = [
//     { name: 'Partner One', src: '/images/Maketu1.png' },
//     { name: 'Partner Two', src: '/images/Maketu1.png' },
//     { name: 'Partner Three', src: '/images/Maketu1.png' },
//     { name: 'Partner Four', src: '/images/Maketu1.png' },
//     { name: 'Partner Five', src: '/images/Maketu1.png' },
// ];
</script>

<template>
    <Head :title="t('landing.metaTitle')" />

    <div id="top" class="min-h-screen bg-background text-foreground">
        <LandingHeader :nav-items="navItems" />

        <main>
            <HeroSection :stats="heroStats" />
            <!-- <PartnersSection :logos="partnerLogos" /> -->
            <FeaturesSection :features="features" />
            <HowItWorksSection :steps="steps" />
            <CategoriesSection :categories="categories" />
            <CTASplit />
            <!-- <TestimonialsSection :testimonials="testimonials" /> -->
            <FAQSection :faqs="faqs" />
            <CTASection />
            <NewsletterSection />
        </main>

        <LandingFooter />
    </div>
</template>


