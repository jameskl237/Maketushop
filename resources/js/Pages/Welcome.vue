<script setup>
import LandingFooter from '@/components/landing/layout/LandingFooter.vue';
import LandingHeader from '@/components/landing/layout/LandingHeader.vue';
import { Head } from '@inertiajs/vue3';
import {
    BadgeCheck,
    PackageSearch,
    ShieldCheck,
    ShoppingBag,
    Store,
    Truck,
} from 'lucide-vue-next';
import { defineAsyncComponent } from 'vue';

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

const navItems = [
    { label: 'Fonctionnalités', href: '#features' },
    { label: 'Comment ça marche', href: '#how-it-works' },
    { label: 'Catégories', href: '#categories' },
    // { label: 'Avis', href: '#testimonials' },
    { label: 'FAQ', href: '#faq' },
];

const heroStats = [
    { value: 50, suffix: '+', label: 'Artisans' },
    { value: 800, suffix: '+', label: 'Produits' },
    { value: 500, suffix: '+', label: 'Clients' },
];

const features = [
    { icon: ShoppingBag, title: 'Catalogue riche', description: 'Publiez et trouvez rapidement des milliers de produits.', color: 'blue' },
    { icon: Truck, title: 'Livraison rapide', description: 'Suivi de commande clair et délai optimisé.', color: 'green' },
    { icon: ShieldCheck, title: 'Paiement sécurisé', description: 'Transactions fiables avec protection acheteur-vendeur.', color: 'purple' },
    { icon: Store, title: 'Boutiques personnalisées', description: 'Chaque vendeur valorise sa marque et ses offres.', color: 'orange' },
    { icon: BadgeCheck, title: 'Vendeurs vérifiés', description: 'Confiance renforcée grâce à la vérification des profils.', color: 'teal' },
    { icon: PackageSearch, title: 'Recherche intelligente', description: 'Filtres avancés pour retrouver vite le bon produit.', color: 'pink' },
];

const steps = [
    { title: 'Créez votre compte', description: 'Inscrivez-vous en quelques secondes comme acheteur ou vendeur.' },
    { title: 'Explorez ou publiez', description: 'Parcourez les catégories ou mettez en ligne vos produits.' },
    { title: 'Convertissez durablement', description: 'Profitez de la preuve sociale et des outils de suivi.' },
];

const categories = [
    { id: 1, name: 'Artisanat', count: 1234, image: '/images/artisanat.png' },
    { id: 2, name: 'Maison', count: 842, image: '/images/maison.png' },
    { id: 3, name: 'Mode', count: 1640, image: '/images/mode.png' },
    { id: 4, name: 'Beauté', count: 920, image: '/images/beaute.png' },
    { id: 5, name: 'Électronique', count: 630, image: '/images/electronique.png' },
    { id: 6, name: 'Alimentation', count: 1150, image: '/images/alimentation.png' },
    { id: 7, name: 'Sport', count: 455, image: '/images/sport.png' },
    { id: 8, name: 'Accessoires', count: 780, image: '/images/accessoire.png' },
];

const testimonials = [
    { name: 'Marie D.', role: 'Cliente', quote: "Super expérience d'achat, livraison rapide et service top.", avatar: '/images/Maketu_coeur.png' },
    { name: 'Samir K.', role: 'Vendeur', quote: 'Ma boutique a gagné en visibilité dès le premier mois.', avatar: '/images/Maketa.png' },
    { name: 'Aline M.', role: 'Cliente', quote: 'Interface claire, paiement sécurisé, je recommande.', avatar: '/images/Maketu_panier.png' },
    { name: 'Joseph T.', role: 'Vendeur', quote: 'Les statistiques m’aident à piloter mes ventes.', avatar: '/images/Maketu_logo.png' },
    { name: 'Nadia P.', role: 'Cliente', quote: 'Très bon choix de produits locaux et qualité au rendez-vous.', avatar: '/images/Maketu_phone.png' },
    { name: 'Franck E.', role: 'Vendeur', quote: 'Les outils marketing intégrés sont vraiment utiles.', avatar: '/images/Maketu_ordi.png' },
];

const faqs = [
    { question: 'Comment créer un compte ?', answer: 'Cliquez sur "Créer un compte", choisissez votre profil et complétez le formulaire.' },
    { question: 'Comment devenir vendeur ?', answer: 'Après inscription, activez votre espace fournisseur et créez votre boutique.' },
    { question: 'Les paiements sont-ils sécurisés ?', answer: 'Oui, la plateforme applique des standards de sécurité élevés.' },
    { question: 'Puis-je suivre mes commandes ?', answer: 'Oui, chaque commande dispose d’un suivi clair depuis votre espace.' },
    { question: 'Y a-t-il des frais cachés ?', answer: 'Non, les frais sont transparents et affichés avant validation.' },
    { question: 'Le support est-il disponible ?', answer: 'Oui, notre équipe support est disponible pour vous accompagner.' },
];

// const partnerLogos = [
//     { name: 'Partner One', src: '/images/Maketu1.png' },
//     { name: 'Partner Two', src: '/images/Maketu1.png' },
//     { name: 'Partner Three', src: '/images/Maketu1.png' },
//     { name: 'Partner Four', src: '/images/Maketu1.png' },
//     { name: 'Partner Five', src: '/images/Maketu1.png' },
// ];
</script>

<template>
    <Head title="MaketuShop - Marketplace moderne pour acheter et vendre localement" />

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


