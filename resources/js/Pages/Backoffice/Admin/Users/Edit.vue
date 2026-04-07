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
    <Head title="Éditer utilisateur" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <h2 class="text-xl font-semibold leading-tight">Éditer — {{ user.name }}</h2>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <div class="bg-white shadow sm:rounded-lg p-6">
                    <form @submit.prevent="submit" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nom</label>
                            <input v-model="form.name" type="text" class="mt-1 block w-full" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nom d'utilisateur</label>
                            <input v-model="form.username" type="text" class="mt-1 block w-full" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Email</label>
                            <input v-model="form.email" type="email" class="mt-1 block w-full" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Téléphone</label>
                            <input v-model="form.phone" type="text" class="mt-1 block w-full" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Adresse</label>
                            <input v-model="form.address" type="text" class="mt-1 block w-full" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Rôle</label>
                            <select v-model="form.role" class="mt-1 block w-full">
                                <option value="admin">admin</option>
                                <option value="supplier">supplier</option>
                                <option value="user">user</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Mot de passe (laisser vide pour conserver)</label>
                            <input v-model="form.password" type="password" class="mt-1 block w-full" />
                        </div>

                        <div class="flex items-center justify-end gap-3">
                            <button type="submit" :disabled="submitting" class="btn btn-primary">Enregistrer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
