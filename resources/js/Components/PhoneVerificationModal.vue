<script setup>
import { ref, computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { countries } from '@/lib/countries';

const page = usePage();
const mustSetPhone = computed(() => page.props.auth.must_set_phone);

const selectedCode = ref('+237');
const phoneNumber = ref('');

const form = useForm({
    phone: '',
});

const submit = () => {
    // Concatenation: indice sans le "+" et le numéro
    const index = selectedCode.value.replace('+', '');
    form.phone = index + phoneNumber.value.replace(/\s+/g, '');
    
    form.patch(route('profile.phone.update'), {
        preserveScroll: true,
        onSuccess: () => {
            phoneNumber.value = '';
        },
    });
};
</script>

<template>
    <Modal :show="mustSetPhone" :closeable="false">
        <div class="p-6">
            <h2 class="text-lg font-medium text-foreground">
                Informations complémentaires requises
            </h2>

            <p class="mt-1 text-sm text-muted-foreground">
                Pour finaliser votre inscription, veuillez renseigner votre numéro de téléphone. Ce numéro sera également utilisé pour vos boutiques.
            </p>

            <form @submit.prevent="submit" class="mt-6">
                <div>
                    <InputLabel for="phone" value="Numéro de téléphone" />

                    <div class="flex mt-1">
                        <select
                            v-model="selectedCode"
                            class="rounded-l-md border border-input bg-background text-foreground shadow-sm focus:border-ring focus:ring-ring border-r-0"
                        >
                            <option v-for="country in countries" :key="country.code" :value="country.code">
                                {{ country.code }} ({{ country.name }})
                            </option>
                        </select>
                        <TextInput
                            id="phone"
                            type="text"
                            class="block w-full rounded-l-none"
                            v-model="phoneNumber"
                            required
                            placeholder="Ex: 6XXXXXXXX"
                        />
                    </div>

                    <InputError :message="form.errors.phone" class="mt-2" />
                </div>

                <div class="mt-6 flex justify-end">
                    <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                        Enregistrer
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </Modal>
</template>
