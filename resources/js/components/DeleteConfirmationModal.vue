<script setup>
import Modal from '@/components/Modal.vue';
import SecondaryButton from '@/components/SecondaryButton.vue';
import DangerButton from '@/components/DangerButton.vue';

defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: null,
    },
    message: {
        type: String,
        default: null,
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
                {{ title || $t('common.deleteConfirmTitle') }}
            </h2>

            <p class="mt-1 text-sm text-muted-foreground">
                {{ message || $t('common.deleteConfirmMessage') }}
            </p>

            <div class="mt-6 flex justify-end">
                <SecondaryButton @click="close">
                    {{ $t('common.cancel') }}
                </SecondaryButton>

                <DangerButton
                    class="ms-3"
                    @click="confirm"
                >
                    {{ $t('common.delete') }}
                </DangerButton>
            </div>
        </div>
    </Modal>
</template>
