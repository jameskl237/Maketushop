<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { 
    Home, 
    Users, 
    Store, 
    Package, 
    Tag, 
    CreditCard, 
    Settings,
    ShieldCheck,
    BarChart3
} from 'lucide-vue-next';

const props = defineProps({
    activeRoute: {
        type: String,
        default: 'backoffice.admin.dashboard',
    },
});

const page = usePage();
const currentUser = page && page.props && page.props.value && page.props.value.auth ? page.props.value.auth.user : null;
const isSuperAdmin = currentUser?.role === 'superadmin';
</script>

<template>
    <div class="rounded-xl border border-border bg-card p-4 shadow-sm">
        <nav class="space-y-6">
            <!-- SuperAdmin Section -->
            <div v-if="isSuperAdmin">
                <div class="text-xs font-bold uppercase text-primary px-3 mb-2 tracking-wider flex items-center gap-2">
                    <ShieldCheck class="w-3 h-3" />
                    {{ $t('admin.sidebar.superAdminSection') }}
                </div>
                <div class="space-y-1">
                    <Link :href="route('backoffice.superadmin.dashboard')" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm"
                        :class="activeRoute === 'backoffice.superadmin.dashboard' ? 'bg-primary/12 text-primary font-medium' : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground'">
                        <Home class="h-4 w-4" />
                        {{ $t('admin.sidebar.financialDashboard') }}
                    </Link>
                    <Link :href="route('backoffice.superadmin.accounting')" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm"
                        :class="activeRoute === 'backoffice.superadmin.accounting' ? 'bg-primary/12 text-primary font-medium' : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground'">
                        <BarChart3 class="h-4 w-4" />
                        {{ $t('admin.sidebar.accountingDetail') }}
                    </Link>
                </div>
            </div>

            <!-- Admin Section (Only for simple Admins or as secondary for SuperAdmins) -->
            <div v-if="!isSuperAdmin">
                <div class="text-xs font-bold uppercase text-muted-foreground px-3 mb-2 tracking-wider">
                    {{ $t('admin.sidebar.adminDashboardSection') }}
                </div>
                <div class="space-y-1">
                    <Link :href="route('backoffice.admin.dashboard')" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm"
                        :class="activeRoute === 'backoffice.admin.dashboard' ? 'bg-primary/12 text-primary font-medium' : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground'">
                        <Home class="h-4 w-4" />
                        {{ $t('admin.sidebar.overview') }}
                    </Link>
                </div>
            </div>

            <!-- Shared Management Section -->
            <div>
                <div class="text-xs font-bold uppercase text-muted-foreground px-3 mb-2 tracking-wider">
                    {{ $t('admin.sidebar.operationalManagement') }}
                </div>
                <div class="space-y-1">
                    <Link :href="route('backoffice.admin.management')" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm"
                        :class="activeRoute === 'backoffice.admin.management' ? 'bg-primary/12 text-primary font-medium' : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground'">
                        <Users class="h-4 w-4" />
                        {{ $t('admin.sidebar.globalManagement') }}
                    </Link>

                    <Link :href="route('backoffice.admin.shops.index')" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm"
                        :class="activeRoute === 'backoffice.admin.shops.index' ? 'bg-primary/12 text-primary font-medium' : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground'">
                        <Store class="h-4 w-4" />
                        {{ $t('admin.management.tabs.shops') }}
                    </Link>

                    <Link :href="route('backoffice.admin.products.index')" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm"
                        :class="activeRoute === 'backoffice.admin.products.index' ? 'bg-primary/12 text-primary font-medium' : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground'">
                        <Package class="h-4 w-4" />
                        {{ $t('admin.management.tabs.products') }}
                    </Link>

                    <Link :href="route('backoffice.admin.categories.index')" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm"
                        :class="activeRoute === 'backoffice.admin.categories.index' ? 'bg-primary/12 text-primary font-medium' : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground'">
                        <Tag class="h-4 w-4" />
                        {{ $t('admin.management.tabs.categories') }}
                    </Link>

                    <Link :href="route('backoffice.admin.orders.index')" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm"
                        :class="activeRoute === 'backoffice.admin.orders.index' ? 'bg-primary/12 text-primary font-medium' : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground'">
                        <CreditCard class="h-4 w-4" />
                        {{ $t('admin.management.tabs.orders') }}
                    </Link>
                </div>
            </div>

            <!-- Settings Section -->
            <div>
                <div class="text-xs font-bold uppercase text-muted-foreground px-3 mb-2 tracking-wider">
                    {{ $t('admin.sidebar.account') }}
                </div>
                <div class="mt-2">
                    <Link :href="route('profile.edit')" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm text-muted-foreground hover:bg-accent hover:text-accent-foreground">
                        <Settings class="h-4 w-4" />
                        {{ $t('admin.sidebar.settings') }}
                    </Link>
                </div>
            </div>
        </nav>
    </div>
</template>
