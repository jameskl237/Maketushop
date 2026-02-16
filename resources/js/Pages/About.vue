<script setup>
import LanguageSwitcher from '@/components/LanguageSwitcher.vue';
import ThemeToggle from '@/components/ThemeToggle.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Head, Link } from '@inertiajs/vue3';
import { Globe2, Lightbulb, Target, UserRound } from 'lucide-vue-next';
</script>

<template>
    <Head title="À propos de MaketuShop" />

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
                    <Link :href="route('contact')">
                        <Button variant="outline">Contact</Button>
                    </Link>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-6xl space-y-8 px-4 py-8 sm:px-6 lg:px-8">
            <section class="rounded-2xl border border-border bg-card/70 p-6 sm:p-8">
                <Badge class="mb-4">À propos</Badge>
                <h1 class="text-3xl font-bold sm:text-4xl">MaketuShop, la vitrine du business en ligne au Cameroun</h1>
                <p class="mt-4 max-w-3xl text-muted-foreground">
                    MaketuShop est une plateforme e-commerce pensée pour aider les entrepreneurs camerounais
                    à vendre en ligne, structurer leur activité et toucher plus de clients avec des outils simples,
                    modernes et accessibles.
                </p>
            </section>

            <section class="grid gap-4 md:grid-cols-3">
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-lg"><Target class="h-5 w-5 text-primary" /> Mission</CardTitle>
                    </CardHeader>
                    <CardContent class="text-sm text-muted-foreground">
                        Digitaliser le commerce local et faciliter la rencontre entre vendeurs et clients partout au Cameroun.
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-lg"><Lightbulb class="h-5 w-5 text-primary" /> Vision</CardTitle>
                    </CardHeader>
                    <CardContent class="text-sm text-muted-foreground">
                        Construire un écosystème fiable pour développer durablement le business en ligne camerounais.
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-lg"><Globe2 class="h-5 w-5 text-primary" /> Impact</CardTitle>
                    </CardHeader>
                    <CardContent class="text-sm text-muted-foreground">
                        Donner plus de visibilité aux boutiques locales et créer de nouvelles opportunités de croissance.
                    </CardContent>
                </Card>
            </section>

            <section class="rounded-2xl border border-border bg-card p-6 sm:p-8">
                <h2 class="text-2xl font-semibold">Le concepteur de MaketuShop</h2>
                <div class="mt-6 grid items-center gap-6 md:grid-cols-[220px_1fr]">
                    <div class="mx-auto h-48 w-48 overflow-hidden rounded-2xl border border-border shadow">
                        <img src="/images/james.png" alt="DJIELE NKAMGA James Leeman" class="h-full w-full object-cover" />
                    </div>
                    <div class="space-y-3">
                        <p class="flex items-center gap-2 text-lg font-medium">
                            <UserRound class="h-5 w-5 text-primary" />
                            DJIELE NKAMGA James Leeman
                        </p>
                        <p class="text-muted-foreground">
                            Jeune entrepreneur camerounais et ingénieur informatique, initiateur et propriétaire de MaketuShop.
                        </p>
                        <p class="text-muted-foreground">
                            Sa démarche vise à proposer des solutions numériques utiles, concrètes et adaptées à la réalité du terrain
                            pour accélérer la transformation du commerce local.
                        </p>
                    </div>
                </div>
            </section>
        </main>
    </div>
</template>

