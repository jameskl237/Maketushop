<script setup>
/**
 * @component LandingFooter
 * @description Footer complet avec colonnes de liens et infos marque.
 * @example <LandingFooter />
 */
const columns = [
    {
        title: 'Produit',
        links: [
            { label: 'Fonctionnalités', href: '#features' },
            { label: 'Catégories', href: '#categories' },
            { label: 'Tarification', href: '#' },
            { label: 'FAQ', href: '#faq' },
        ],
    },
    {
        title: 'Entreprise',
        links: [
            { label: 'À propos', href: '/about' },
            { label: 'Carrières', href: '#' },
            { label: 'Partenaires', href: '#' },
            { label: 'Contact', href: '/contact' },
        ],
    },
    {
        title: 'Ressources',
        links: [
            { label: 'Blog', href: '#' },
            { label: 'Guide vendeur', href: '#' },
            { label: 'Aide', href: '#' },
            { label: 'Documentation', href: '#' },
        ],
    },
    {
        title: 'Légal',
        links: [
            { label: 'Confidentialité', href: '#' },
            { label: 'Conditions', href: '#' },
            { label: 'Cookies', href: '#' },
            { label: 'Mentions légales', href: '#' },
        ],
    },
];
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
                    <p class="mt-4 text-sm text-muted-foreground">
                        La marketplace locale qui connecte clients et vendeurs avec confiance.
                    </p>
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
                © {{ new Date().getFullYear() }} MaketuShop. Tous droits réservés.
            </div>
        </div>
    </footer>
</template>


