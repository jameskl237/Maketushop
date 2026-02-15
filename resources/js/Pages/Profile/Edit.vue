<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});
</script>

<template>
    <Head :title="$t('profile.title')" />

    <AuthenticatedLayout :show-logo="false">
        <template #header>
            <h2
                class="text-xl font-semibold leading-tight text-foreground"
            >
                {{ $t('profile.title') }}
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                <div>
                    <Link
                        :href="route('dashboard')"
                        class="inline-flex items-center rounded-md border border-border bg-card px-3 py-2 text-sm font-medium text-foreground transition hover:bg-accent hover:text-accent-foreground"
                    >
                        ← {{ $t('profile.backToDashboard') }}
                    </Link>
                </div>

                <div
                    class="border border-border bg-card p-4 shadow-sm sm:rounded-lg sm:p-8"
                >
                    <UpdateProfileInformationForm
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                        class="max-w-xl"
                    />
                </div>

                <div
                    class="border border-border bg-card p-4 shadow-sm sm:rounded-lg sm:p-8"
                >
                    <UpdatePasswordForm class="max-w-xl" />
                </div>

                <div
                    class="border border-border bg-card p-4 shadow-sm sm:rounded-lg sm:p-8"
                >
                    <DeleteUserForm class="max-w-xl" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
