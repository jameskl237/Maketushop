<script setup>
import SupplierLayout from '@/Layouts/SupplierLayout.vue';
import AssistantTabs from '@/components/supplier/AssistantTabs.vue';
import InputError from '@/components/InputError.vue';
import Pagination from '@/components/Pagination.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Head, router, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, ref, watch } from 'vue';
import { datetime } from '@/lib/orders';

const props = defineProps({
    shop: { type: Object, required: true },
    hasAccess: { type: Boolean, required: true },
    templates: { type: Array, required: true },
    campaigns: { type: Object, required: true },
    credits: { type: Object, required: true },
});

const form = useForm({ template_id: '', parameters: [], scheduled_at: '' });
const tagsInput = ref('');
const estimate = ref(null);
const estimating = ref(false);
const estimateFailed = ref(false);

const tags = computed(() => tagsInput.value.split(',').map((tag) => tag.trim()).filter(Boolean));
const selectedTemplate = computed(() => props.templates.find((item) => item.id === form.template_id));

watch(() => form.template_id, () => {
    form.parameters = Array(selectedTemplate.value?.variable_count ?? 0).fill('');
});
watch(tags, () => { estimate.value = null; });

const runEstimate = async () => {
    estimating.value = true;
    estimateFailed.value = false;
    try {
        const { data } = await axios.get(
            route('backoffice.supplier.assistant.campaigns.estimate', props.shop.id),
            { params: { tags: tags.value } },
        );
        estimate.value = data;
    } catch {
        estimateFailed.value = true;
    } finally {
        estimating.value = false;
    }
};

// La saisie est en heure locale : le serveur reçoit un instant sans ambiguïté.
const submit = () => form
    .transform((data) => ({
        ...data,
        tags: tags.value,
        scheduled_at: data.scheduled_at ? new Date(data.scheduled_at).toISOString() : null,
    }))
    .post(route('backoffice.supplier.assistant.campaigns.store', props.shop.id), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            tagsInput.value = '';
            estimate.value = null;
        },
    });

const cancel = (campaign) => router.post(
    route('backoffice.supplier.assistant.campaigns.cancel', [props.shop.id, campaign.id]),
    {},
    { preserveScroll: true },
);

const statusVariant = (status) => ({ sent: 'default', failed: 'destructive' }[status] ?? 'outline');
</script>

<template>
    <Head :title="$t('supplier.assistant.tabCampaigns')" />

    <SupplierLayout
        :title="$t('supplier.assistantNav')"
        :subtitle="shop.name"
        active-route="backoffice.supplier.assistant.index"
        :can-create-shop="false"
    >
        <template #content>
            <div class="space-y-4">
                <AssistantTabs :shop-id="shop.id" active="campaigns" />

                <Card>
                    <CardContent class="space-y-3 p-4">
                        <div class="flex flex-wrap items-baseline justify-between gap-2">
                            <h3 class="font-semibold">{{ $t('supplier.assistant.newCampaign') }}</h3>
                            <p class="text-xs text-muted-foreground">{{ $t('supplier.assistant.creditsLeft', credits) }}</p>
                        </div>

                        <p v-if="!templates.length" class="text-sm text-muted-foreground">{{ $t('supplier.assistant.noApprovedTemplate') }}</p>

                        <form v-else class="space-y-3" @submit.prevent="submit">
                            <div class="space-y-1">
                                <Label for="cmp-template">{{ $t('supplier.assistant.campaignTemplate') }}</Label>
                                <select id="cmp-template" v-model="form.template_id" required class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm">
                                    <option value="" disabled>{{ $t('supplier.assistant.chooseTemplate') }}</option>
                                    <option v-for="item in templates" :key="item.id" :value="item.id">{{ item.name }}</option>
                                </select>
                                <InputError :message="form.errors.template_id" />
                                <p v-if="selectedTemplate" class="whitespace-pre-wrap text-sm text-muted-foreground">{{ selectedTemplate.body }}</p>
                            </div>
                            <Input
                                v-for="(_, index) in form.parameters"
                                :key="index"
                                v-model="form.parameters[index]"
                                required
                                :aria-label="`{{${index + 1}}}`"
                                :placeholder="`{{${index + 1}}}`"
                            />
                            <InputError :message="form.errors.parameters" />
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div class="space-y-1">
                                    <Label for="cmp-tags">{{ $t('supplier.assistant.campaignTags') }}</Label>
                                    <Input id="cmp-tags" v-model="tagsInput" placeholder="vip, acheteur" />
                                    <p class="text-xs text-muted-foreground">{{ $t('supplier.assistant.campaignTagsHelp') }}</p>
                                </div>
                                <div class="space-y-1">
                                    <Label for="cmp-date">{{ $t('supplier.assistant.scheduledAt') }}</Label>
                                    <Input id="cmp-date" v-model="form.scheduled_at" type="datetime-local" />
                                    <InputError :message="form.errors.scheduled_at" />
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-3">
                                <Button type="button" size="sm" variant="outline" :disabled="estimating || !hasAccess" @click="runEstimate">
                                    {{ estimating ? $t('supplier.assistant.estimating') : $t('supplier.assistant.estimate') }}
                                </Button>
                                <p v-if="estimate && !estimate.exceeds_quota" class="text-sm">
                                    {{ $t('supplier.assistant.estimateResult', estimate) }}
                                </p>
                                <p v-if="estimate?.exceeds_quota" class="text-sm text-destructive">{{ $t('supplier.assistant.estimateExceeds') }}</p>
                                <p v-if="estimateFailed" class="text-sm text-destructive">{{ $t('supplier.assistant.estimateError') }}</p>
                            </div>

                            <Button type="submit" :disabled="form.processing || !hasAccess">
                                {{ form.scheduled_at ? $t('supplier.assistant.schedule') : $t('supplier.assistant.launch') }}
                            </Button>
                        </form>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="p-4">
                        <p v-if="!campaigns.data.length" class="text-sm text-muted-foreground">{{ $t('supplier.assistant.noCampaigns') }}</p>
                        <ul class="divide-y divide-border">
                            <li v-for="campaign in campaigns.data" :key="campaign.id" class="flex flex-wrap items-center justify-between gap-2 py-3 text-sm">
                                <div class="min-w-0">
                                    <p class="truncate font-medium">{{ campaign.template_name }}</p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ datetime(campaign.scheduled_at ?? campaign.created_at) }} ·
                                        {{ $t('supplier.assistant.campaignStats', { sent: campaign.sent, failed: campaign.failed, credits: campaign.credits_used }) }}
                                    </p>
                                    <p v-if="campaign.error" class="text-xs text-destructive">{{ campaign.error }}</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <Badge :variant="statusVariant(campaign.status)">{{ $t(`supplier.assistant.campaignStatus_${campaign.status}`) }}</Badge>
                                    <Button v-if="campaign.status === 'scheduled'" size="sm" variant="outline" :disabled="!hasAccess" @click="cancel(campaign)">
                                        {{ $t('supplier.assistant.cancel') }}
                                    </Button>
                                </div>
                            </li>
                        </ul>
                        <Pagination class="mt-3" :links="campaigns.links" />
                    </CardContent>
                </Card>
            </div>
        </template>
    </SupplierLayout>
</template>
