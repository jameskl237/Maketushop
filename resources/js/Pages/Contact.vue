<script setup>
import LanguageSwitcher from '@/components/LanguageSwitcher.vue';
import ThemeToggle from '@/components/ThemeToggle.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Head, Link } from '@inertiajs/vue3';
import { Facebook, Mail, MessageCircle, Phone, Share2 } from 'lucide-vue-next';

const contacts = [
    {
        label: 'WhatsApp',
        value: 'Canal officiel Maketu',
        href: 'https://whatsapp.com/channel/0029VbBpVyo545v2xLJpev2X',
        icon: MessageCircle,
    },
    {
        label: 'Téléphone',
        value: '+237 695988879',
        href: 'tel:+237695988879',
        icon: Phone,
    },
    {
        label: 'Email',
        value: 'djielejames@gmail.com',
        href: 'mailto:djielejames@gmail.com',
        icon: Mail,
    },
    {
        label: 'Facebook',
        value: 'Page Facebook Maketu',
        href: 'https://www.facebook.com/share/14ZUXMqhSuY/',
        icon: Facebook,
    },
    {
        label: 'Réseaux',
        value: 'Autres canaux officiels',
        href: '#',
        icon: Share2,
    },
];
</script>

<template>
    <Head title="Contact MaketuShop" />

    <div class="min-h-screen bg-background">
        <header class="sticky top-0 z-40 border-b border-border bg-background/95 backdrop-blur">
            <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-4 sm:px-6 lg:px-8">
                <Link href="/" class="text-base font-semibold">MaketuShop</Link>
                <div class="flex items-center gap-2">
                    <Link href="/">
                        <Button variant="outline">Accueil</Button>
                    </Link>
                    <LanguageSwitcher :floating="false" />
                    <ThemeToggle :floating="false" />
                    <Link :href="route('about')">
                        <Button variant="outline">À propos</Button>
                    </Link>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-6xl space-y-8 px-4 py-8 sm:px-6 lg:px-8">
            <section class="rounded-2xl border border-border bg-card/70 p-6 sm:p-8">
                <Badge class="mb-4">Contact</Badge>
                <h1 class="text-3xl font-bold sm:text-4xl">Contactez MaketuShop</h1>
                <p class="mt-4 max-w-3xl text-muted-foreground">
                    Retrouvez ici tous les moyens de communication officiels pour joindre l’équipe MaketuShop.
                </p>
            </section>

            <section class="grid gap-4 md:grid-cols-2">
                <Card v-for="item in contacts" :key="item.label">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-lg">
                            <component :is="item.icon" class="h-5 w-5 text-primary" />
                            {{ item.label }}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <a
                            :href="item.href"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="text-sm text-muted-foreground transition-colors hover:text-foreground hover:underline"
                        >
                            {{ item.value }}
                        </a>
                    </CardContent>
                </Card>
            </section>
        </main>
    </div>
</template>

