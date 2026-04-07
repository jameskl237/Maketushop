<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    user: Object,
});

const delForm = useForm();
const deleting = ref(false);

const destroy = () => {
    if (!confirm('Supprimer cet utilisateur ?')) return;

    deleting.value = true;
    delForm.delete(route('backoffice.admin.users.destroy', { user: props.user.id }), {
        preserveScroll: true,
        onFinish: () => (deleting.value = false),
    });
};
</script>

<template>
    <Head title="Utilisateur" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <h2 class="text-xl font-semibold leading-tight">Utilisateur — {{ user.name }}</h2>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <div class="bg-white shadow sm:rounded-lg p-6">
                    <dl class="divide-y divide-gray-200">
                        <div class="py-4 flex justify-between">
                            <dt class="text-sm font-medium text-gray-500">Nom</dt>
                            <dd class="text-sm text-gray-900">{{ user.name }}</dd>
                        </div>
                        <div class="py-4 flex justify-between">
                            <dt class="text-sm font-medium text-gray-500">Email</dt>
                            <dd class="text-sm text-gray-900">{{ user.email }}</dd>
                        </div>
                        <div class="py-4 flex justify-between">
                            <dt class="text-sm font-medium text-gray-500">Nom d'utilisateur</dt>
                            <dd class="text-sm text-gray-900">{{ user.username }}</dd>
                        </div>
                        <div class="py-4 flex justify-between">
                            <dt class="text-sm font-medium text-gray-500">Rôle</dt>
                            <dd class="text-sm text-gray-900">{{ user.role }}</dd>
                        </div>
                        <div class="py-4 flex justify-between">
                            <dt class="text-sm font-medium text-gray-500">Téléphone</dt>
                            <dd class="text-sm text-gray-900">{{ user.phone ?? '-' }}</dd>
                        </div>
                        <div class="py-4 flex justify-between">
                            <dt class="text-sm font-medium text-gray-500">Adresse</dt>
                            <dd class="text-sm text-gray-900">{{ user.address ?? '-' }}</dd>
                        </div>
                    </dl>

                    <div class="mt-6 flex items-center justify-end gap-3">
                        <inertia-link :href="route('backoffice.admin.users.edit', { user: user.id })" class="btn btn-primary">Éditer</inertia-link>
                        <button @click="destroy" class="btn btn-danger">Supprimer</button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
