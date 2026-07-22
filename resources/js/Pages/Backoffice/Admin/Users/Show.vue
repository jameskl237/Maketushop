<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps({
    user: Object,
});

const { t } = useI18n();

const delForm = useForm();
const deleting = ref(false);

const destroy = () => {
    if (!confirm(t('admin.showUser.confirmDelete'))) return;

    deleting.value = true;
    delForm.delete(route('backoffice.admin.users.destroy', { user: props.user.id }), {
        preserveScroll: true,
        onFinish: () => (deleting.value = false),
    });
};
</script>

<template>
    <Head :title="$t('admin.showUser.pageTitle')" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <h2 class="text-xl font-semibold leading-tight">{{ $t('admin.showUser.headingPrefix', { name: user.name }) }}</h2>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <div class="border border-border bg-card text-foreground shadow sm:rounded-lg p-6">
                    <dl class="divide-y divide-border">
                        <div class="py-4 flex justify-between">
                            <dt class="text-sm font-medium text-muted-foreground">{{ $t('admin.showUser.name') }}</dt>
                            <dd class="text-sm text-foreground">{{ user.name }}</dd>
                        </div>
                        <div class="py-4 flex justify-between">
                            <dt class="text-sm font-medium text-muted-foreground">{{ $t('admin.showUser.email') }}</dt>
                            <dd class="text-sm text-foreground">{{ user.email }}</dd>
                        </div>
                        <div class="py-4 flex justify-between">
                            <dt class="text-sm font-medium text-muted-foreground">{{ $t('admin.showUser.username') }}</dt>
                            <dd class="text-sm text-foreground">{{ user.username }}</dd>
                        </div>
                        <div class="py-4 flex justify-between">
                            <dt class="text-sm font-medium text-muted-foreground">{{ $t('admin.showUser.googleId') }}</dt>
                            <dd class="text-sm text-foreground">{{ user.google_id ?? '-' }}</dd>
                        </div>
                        <div class="py-4 flex justify-between">
                            <dt class="text-sm font-medium text-muted-foreground">{{ $t('admin.showUser.role') }}</dt>
                            <dd class="text-sm text-foreground">{{ user.role }}</dd>
                        </div>
                        <div class="py-4 flex justify-between">
                            <dt class="text-sm font-medium text-muted-foreground">{{ $t('admin.showUser.phone') }}</dt>
                            <dd class="text-sm text-foreground">{{ user.phone ?? '-' }}</dd>
                        </div>
                        <div class="py-4 flex justify-between">
                            <dt class="text-sm font-medium text-muted-foreground">{{ $t('admin.showUser.address') }}</dt>
                            <dd class="text-sm text-foreground">{{ user.address ?? '-' }}</dd>
                        </div>
                        <div class="py-4 flex justify-between">
                            <dt class="text-sm font-medium text-muted-foreground">{{ $t('admin.showUser.emailVerified') }}</dt>
                            <dd class="text-sm text-foreground">{{ user.email_verified_at ? new Date(user.email_verified_at).toLocaleString() : '-' }}</dd>
                        </div>
                    </dl>

                    <div class="mt-6 flex items-center justify-end gap-3">
                        <inertia-link :href="route('backoffice.admin.users.edit', { user: user.id })" class="btn btn-primary">{{ $t('admin.showUser.edit') }}</inertia-link>
                        <button @click="destroy" class="btn btn-danger">{{ $t('admin.showUser.delete') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
