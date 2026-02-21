<script setup>
import LanguageSwitcher from '@/components/LanguageSwitcher.vue';
import ThemeToggle from '@/components/ThemeToggle.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Head, Link } from '@inertiajs/vue3';
import { Globe2, Lightbulb, Target, UserRound } from 'lucide-vue-next';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
</script>

<template>
    <Head :title="t('aboutPage.headTitle')" />

    <div class="min-h-screen bg-background">
        <header class="sticky top-0 z-40 border-b border-border bg-background/95 backdrop-blur">
            <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-4 sm:px-6 lg:px-8">
                <Link href="/" class="text-base font-semibold">MaketuShop</Link>
                <div class="flex items-center gap-2">
                    <Link href="/">
                        <Button variant="outline">{{ t('public.home') }}</Button>
                    </Link>
                    <LanguageSwitcher :floating="false" />
                    <ThemeToggle :floating="false" />
                    <Link :href="route('contact')">
                        <Button variant="outline">{{ t('public.contact') }}</Button>
                    </Link>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-6xl space-y-8 px-4 py-8 sm:px-6 lg:px-8">
            <section class="rounded-2xl border border-border bg-card/70 p-6 sm:p-8">
                <Badge class="mb-4">{{ t('aboutPage.badge') }}</Badge>
                <h1 class="text-3xl font-bold sm:text-4xl">{{ t('aboutPage.title') }}</h1>
                <p class="mt-4 max-w-3xl text-muted-foreground">
                    {{ t('aboutPage.intro') }}
                </p>
            </section>

            <section class="grid gap-4 md:grid-cols-3">
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-lg"><Target class="h-5 w-5 text-primary" /> {{ t('aboutPage.missionTitle') }}</CardTitle>
                    </CardHeader>
                    <CardContent class="text-sm text-muted-foreground">
                        {{ t('aboutPage.missionText') }}
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-lg"><Lightbulb class="h-5 w-5 text-primary" /> {{ t('aboutPage.visionTitle') }}</CardTitle>
                    </CardHeader>
                    <CardContent class="text-sm text-muted-foreground">
                        {{ t('aboutPage.visionText') }}
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-lg"><Globe2 class="h-5 w-5 text-primary" /> {{ t('aboutPage.impactTitle') }}</CardTitle>
                    </CardHeader>
                    <CardContent class="text-sm text-muted-foreground">
                        {{ t('aboutPage.impactText') }}
                    </CardContent>
                </Card>
            </section>

            <section class="rounded-2xl border border-border bg-card p-6 sm:p-8">
                <h2 class="text-2xl font-semibold">{{ t('aboutPage.authorTitle') }}</h2>
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
                            {{ t('aboutPage.authorBio1') }}
                        </p>
                        <p class="text-muted-foreground">
                            {{ t('aboutPage.authorBio2') }}
                        </p>
                    </div>
                </div>
            </section>
        </main>
    </div>
</template>

