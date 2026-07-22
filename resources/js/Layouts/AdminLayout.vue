<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AdminSidebar from '@/components/admin/AdminSidebar.vue';
import { Button } from '@/components/ui/button';
import { Link, usePage } from '@inertiajs/vue3';
import { ChevronDown, ExternalLink, Menu } from 'lucide-vue-next';
import { ref, computed } from 'vue';

const props = defineProps({
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

const page = usePage();
const currentUser = computed(() => page.props.auth?.user);
const mobileSidebarVisible = ref(false);
</script>

<template>
    <AuthenticatedLayout :show-logo="false">
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div>
                        <h2 class="text-xl font-semibold leading-tight text-foreground sm:text-2xl">{{ title }}</h2>
                        <p v-if="subtitle" class="mt-1 text-sm text-muted-foreground">{{ subtitle }}</p>
                    </div>
                    <div v-if="currentUser" class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider"
                        :class="currentUser.role === 'superadmin' ? 'bg-primary/20 text-primary border border-primary/30' : 'bg-muted text-muted-foreground border border-border'">
                        {{ currentUser.role }}
                    </div>
                </div>
                <Link :href="route('home')">
                    <Button variant="outline" size="sm" class="gap-1.5">
                        <ExternalLink class="h-4 w-4" />
                        <span class="hidden sm:inline">Voir la plateforme</span>
                    </Button>
                </Link>
            </div>
        </template>

        <div class="overflow-x-hidden py-6 sm:py-8">
            <div class="mx-auto max-w-7xl overflow-x-hidden px-4 sm:px-6 lg:px-8">
                <div class="mb-4 lg:hidden">
                    <button
                        type="button"
                        class="flex w-full items-center justify-between gap-2 rounded-xl border border-border bg-card px-4 py-3 text-sm font-semibold text-foreground shadow-sm"
                        :aria-expanded="mobileSidebarVisible"
                        @click="mobileSidebarVisible = !mobileSidebarVisible"
                    >
                        <span class="flex items-center gap-2">
                            <Menu class="h-4 w-4" />
                            {{ title }}
                        </span>
                        <ChevronDown class="h-4 w-4 transition-transform" :class="mobileSidebarVisible ? 'rotate-180' : ''" />
                    </button>
                    <div v-if="mobileSidebarVisible" class="mt-2">
                        <AdminSidebar :active-route="$props.activeRoute" />
                    </div>
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
