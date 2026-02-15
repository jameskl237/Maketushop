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
    <Head :title="$t('admin.dashboardTitle')" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <h2 class="text-xl font-semibold leading-tight">Backoffice Admin</h2>
                <Badge>{{ $t('admin.role') }}</Badge>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto grid max-w-7xl gap-6 px-4 sm:px-6 lg:px-8 md:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle>{{ $t('admin.userManagementTitle') }}</CardTitle>
                        <CardDescription>
                            {{ $t('admin.userManagementDescription') }}
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <p class="text-sm text-muted-foreground">
                            {{ $t('admin.userManagementHint') }}
                        </p>
                        <Button>{{ $t('admin.configureModule') }}</Button>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>{{ $t('admin.globalTrackingTitle') }}</CardTitle>
                        <CardDescription>
                            {{ $t('admin.globalTrackingDescription') }}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <p class="text-sm text-muted-foreground">
                            {{ $t('admin.globalTrackingHint') }}
                        </p>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

