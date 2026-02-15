<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head :title="$t('common.dashboard')" />

    <AuthenticatedLayout>
        <template #header>
            <h2
                class="text-xl font-semibold leading-tight text-foreground"
            >
                {{ $t('common.dashboard') }}
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div
                    class="overflow-hidden border border-border bg-card shadow-sm sm:rounded-lg"
                >
                    <div class="p-6 text-foreground">
                        {{ $t('dashboard.loggedIn') }}
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
