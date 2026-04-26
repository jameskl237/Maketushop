<script setup>
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';

defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: 'Confirmer la suppression',
    },
    message: {
        type: String,
        default: 'Êtes-vous sûr de vouloir supprimer cet élément ? Cette action est irréversible.',
    },
});

const emit = defineEmits(['close', 'confirm']);

const close = () => {
    emit('close');
};

const confirm = () => {
    emit('confirm');
};
</script>

<template>
    <Modal :show="show" @close="close" maxWidth="md">
        <div class="p-6">
            <h2 class="text-lg font-medium text-foreground">
                {{ title }}
            </h2>

            <p class="mt-1 text-sm text-muted-foreground">
                {{ message }}
            </p>

            <div class="mt-6 flex justify-end">
                <SecondaryButton @click="close">
                    Annuler
                </SecondaryButton>

                <DangerButton
                    class="ms-3"
                    @click="confirm"
                >
                    Supprimer
                </DangerButton>
            </div>
        </div>
    </Modal>
</template>
