<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
</script>

<template>
    <Head title="Dashboard Admin" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <h2 class="text-xl font-semibold leading-tight">Backoffice Admin</h2>
                <Badge>Admin</Badge>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto grid max-w-7xl gap-6 px-4 sm:px-6 lg:px-8 md:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle>Gestion des utilisateurs</CardTitle>
                        <CardDescription>
                            Supervise les comptes et les permissions de la plateforme.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <p class="text-sm text-muted-foreground">
                            Ton dashboard admin est maintenant isolé et protégé par rôle.
                        </p>
                        <Button>Configurer le module</Button>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Suivi global</CardTitle>
                        <CardDescription>
                            Vue d'ensemble des activités vendeurs et commandes.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <p class="text-sm text-muted-foreground">
                            Ajoute ici tes KPI (nb utilisateurs, commandes, revenus...).
                        </p>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

