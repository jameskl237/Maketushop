<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AdminSidebar from '@/components/admin/AdminSidebar.vue';
import { ref } from 'vue';

defineProps({
    title: {
        type: String,
        default: 'Backoffice Admin',
    },
    subtitle: {
        type: String,
        default: '',
    },
    activeRoute: {
        type: String,
        default: 'backoffice.admin.dashboard',
    },
});

const mobileSidebarVisible = ref(false);
</script>

<template>
    <AuthenticatedLayout :show-logo="false">
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-foreground sm:text-2xl">{{ title }}</h2>
                    <p v-if="subtitle" class="mt-1 text-sm text-muted-foreground">{{ subtitle }}</p>
                </div>
            </div>
        </template>

        <div class="overflow-x-hidden py-6 sm:py-8">
            <div class="mx-auto max-w-7xl overflow-x-hidden px-4 sm:px-6 lg:px-8">
                <div class="mb-4 md:hidden">
                    <!-- mobile toggle could go here -->
                </div>

                <div class="grid gap-6 lg:grid-cols-[260px_1fr]">
                    <aside class="hidden lg:block">
                        <div class="sticky top-6">
                            <AdminSidebar :active-route="$props.activeRoute" />
                        </div>
                    </aside>

                    <main class="min-w-0 space-y-6 overflow-x-hidden">
                        <slot name="content" />
                    </main>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
