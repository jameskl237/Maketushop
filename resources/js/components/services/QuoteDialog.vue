<script setup>
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogScrollContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { router, usePage } from '@inertiajs/vue3';
import { FileText, Send } from 'lucide-vue-next';
import { computed, reactive, ref } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps({
    service: { type: Object, required: true },
});

const { t } = useI18n();
const page = usePage();
const open = ref(false);
const submitting = ref(false);

const form = reactive({
    customer_name: page.props?.auth?.user?.name ?? '',
    customer_phone: page.props?.auth?.user?.phone ?? '',
    budget: '',
    message: '',
});

const publicBaseUrl = (import.meta.env.VITE_PUBLIC_APP_URL || window.location.origin).replace(/\/+$/, '');

const ownerPhone = computed(() => props.service.shop?.owner_phone || null);

const sanitizePhone = (value) => {
    const digits = String(value || '').replace(/[^\d]/g, '');
    return digits.startsWith('0') ? `237${digits.replace(/^0+/, '')}` : digits;
};

const buildWhatsappUrl = () => {
    const phone = sanitizePhone(ownerPhone.value);
    if (!phone) return null;

    const serviceUrl = `${publicBaseUrl}/s/${props.service.id}`;
    const lines = [
        t('quoteDialog.waHello', { shop: props.service.shop?.name ?? '' }).trim() + ',',
        '',
        t('quoteDialog.waIntent', { service: props.service.name }),
        serviceUrl,
        '',
        form.budget ? t('quoteDialog.waBudget', { budget: form.budget }) : null,
        form.message ? t('quoteDialog.waDetails', { details: form.message }) : null,
        '',
        form.customer_name ? t('quoteDialog.waFrom', { name: form.customer_name }) : null,
        form.customer_phone ? t('quoteDialog.waPhone', { phone: form.customer_phone }) : null,
    ].filter((line) => line !== null);

    return `https://wa.me/${phone}?text=${encodeURIComponent(lines.join('\n'))}`;
};

const submit = () => {
    submitting.value = true;

    // 1) On enregistre la demande en base (suivi côté client/prestataire)
    router.post(
        route('services.quote', { service: props.service.id }),
        { ...form },
        {
            preserveScroll: true,
            preserveState: true,
            onFinish: () => {
                submitting.value = false;
                open.value = false;

                // 2) On ouvre WhatsApp pré-rempli vers le prestataire
                const url = buildWhatsappUrl();
                if (url) {
                    window.open(url, '_blank', 'noopener');
                }
            },
        },
    );
};
</script>

<template>
    <Dialog v-model:open="open">
        <DialogTrigger as-child>
            <Button variant="default" class="h-12 w-full rounded-2xl text-sm font-bold">
                <FileText class="h-4 w-4" />
                {{ t('servicesPage.requestQuote') }}
            </Button>
        </DialogTrigger>
    <DialogScrollContent class="rounded-2xl">
            <DialogHeader>
                <DialogTitle>{{ t('servicesPage.requestQuote') }}</DialogTitle>
                <DialogDescription>
                    {{ service.name }} — {{ service.shop?.name }}
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-3">
                <div class="space-y-1.5">
                    <Label for="quote_name">{{ t('quoteDialog.nameLabel') }}</Label>
                    <Input id="quote_name" v-model="form.customer_name" :placeholder="t('quoteDialog.namePlaceholder')" />
                </div>
                <div class="space-y-1.5">
                    <Label for="quote_phone">{{ t('quoteDialog.phoneLabel') }}</Label>
                    <Input id="quote_phone" v-model="form.customer_phone" :placeholder="t('quoteDialog.phonePlaceholder')" />
                </div>
                <div class="space-y-1.5">
                    <Label for="quote_budget">{{ t('quoteDialog.budgetLabel') }}</Label>
                    <Input id="quote_budget" v-model="form.budget" :placeholder="t('quoteDialog.budgetPlaceholder')" />
                </div>
                <div class="space-y-1.5">
                    <Label for="quote_message">{{ t('quoteDialog.messageLabel') }}</Label>
                    <Textarea id="quote_message" v-model="form.message" rows="3" :placeholder="t('quoteDialog.messagePlaceholder')" />
                </div>

                <p v-if="!ownerPhone" class="text-[11px] text-destructive">
                    {{ t('quoteDialog.phoneUnavailable') }}
                </p>
            </div>

            <Button :disabled="submitting" class="mt-2 h-11 w-full rounded-xl font-bold" @click="submit">
                <Send class="h-4 w-4" />
                {{ submitting ? t('quoteDialog.sending') : t('quoteDialog.sendButton') }}
            </Button>
    </DialogScrollContent>
    </Dialog>
</template>
