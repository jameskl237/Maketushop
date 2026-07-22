<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    user: Object,
});

const form = useForm({
    name: props.user.name || '',
    username: props.user.username || '',
    email: props.user.email || '',
    phone: props.user.phone || '',
    address: props.user.address || '',
    google_id: props.user.google_id || '',
    email_verified: !!props.user.email_verified_at,
    role: props.user.role || 'user',
    password: '',
    password_confirmation: '',
});

const submitting = ref(false);

const submit = () => {
    submitting.value = true;
    form.put(route('backoffice.admin.users.update', { user: props.user.id }), {
        preserveScroll: true,
        onFinish: () => (submitting.value = false),
    });
};
</script>

<template>
    <Head :title="$t('admin.editUser.pageTitle')" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <h2 class="text-xl font-semibold leading-tight">{{ $t('admin.editUser.headingPrefix', { name: user.name }) }}</h2>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <div class="border border-border bg-card text-foreground shadow sm:rounded-lg p-6">
                    <form @submit.prevent="submit" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-muted-foreground">{{ $t('admin.editUser.name') }}</label>
                            <input v-model="form.name" type="text" class="form-input" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-muted-foreground">{{ $t('admin.editUser.username') }}</label>
                            <input v-model="form.username" type="text" class="form-input" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-muted-foreground">{{ $t('admin.editUser.email') }}</label>
                            <input v-model="form.email" type="email" class="form-input" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-muted-foreground">{{ $t('admin.editUser.phone') }}</label>
                            <input v-model="form.phone" type="text" class="form-input" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-muted-foreground">{{ $t('admin.editUser.address') }}</label>
                            <input v-model="form.address" type="text" class="form-input" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-muted-foreground">{{ $t('admin.editUser.role') }}</label>
                            <select v-model="form.role" class="form-select">
                                <option value="admin">{{ $t('admin.editUser.roleAdmin') }}</option>
                                <option value="supplier">{{ $t('admin.editUser.roleSupplier') }}</option>
                                <option value="user">{{ $t('admin.editUser.roleUser') }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-muted-foreground">{{ $t('admin.editUser.googleId') }}</label>
                            <input v-model="form.google_id" type="text" class="form-input" />
                        </div>

                        <div class="flex items-center gap-2">
                            <input id="email_verified_edit" type="checkbox" v-model="form.email_verified" class="form-checkbox" />
                            <label for="email_verified_edit" class="text-sm font-medium text-muted-foreground">{{ $t('admin.editUser.emailVerified') }}</label>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-muted-foreground">{{ $t('admin.editUser.passwordKeepEmpty') }}</label>
                            <input v-model="form.password" type="password" class="form-input" />
                        </div>

                        <div class="flex items-center justify-end gap-3">
                            <button type="submit" :disabled="submitting" class="btn btn-primary">{{ $t('admin.editUser.save') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
