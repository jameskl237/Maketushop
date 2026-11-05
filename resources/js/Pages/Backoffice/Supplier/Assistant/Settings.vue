<script setup>
import SupplierLayout from '@/Layouts/SupplierLayout.vue';
import AssistantTabs from '@/components/supplier/AssistantTabs.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    shop: { type: Object, required: true },
    hasAccess: { type: Boolean, required: true },
    settings: { type: Object, required: true },
});

const keywords = ref(props.settings.human_transfer_keywords.join(', '));
const form = useForm({
    additional_instructions: props.settings.additional_instructions ?? '',
    ai_temperature: props.settings.ai_temperature ?? '',
    tone: props.settings.tone ?? '',
    language: props.settings.language,
    delivery_policy: props.settings.delivery_policy ?? '',
    return_policy: props.settings.return_policy ?? '',
    welcome_message_enabled: props.settings.welcome_message_enabled,
    welcome_message: props.settings.welcome_message ?? '',
    away_message_enabled: props.settings.away_message_enabled,
    away_message: props.settings.away_message ?? '',
    business_address: props.settings.business_address ?? '',
    ai_share_location: props.settings.ai_share_location,
});

const save = () => form
    .transform((data) => ({
        ...data,
        human_transfer_keywords: keywords.value.split(',').map((word) => word.trim()).filter(Boolean),
    }))
    .put(route('backoffice.supplier.assistant.settings.update', props.shop.id), { preserveScroll: true });

const isActive = computed(() => props.settings.status === 'active');
const setStatus = (active) => router.post(
    route('backoffice.supplier.assistant.status', props.shop.id),
    { active },
    { preserveScroll: true },
);

const removal = useForm({ confirmation: '' });
const canDelete = computed(() => removal.confirmation === props.shop.name);
const destroy = (message) => {
    if (window.confirm(message)) {
        removal.delete(route('backoffice.supplier.assistant.destroy', props.shop.id));
    }
};
</script>

<template>
    <Head :title="$t('supplier.assistant.tabSettings')" />

    <SupplierLayout
        :title="$t('supplier.assistantNav')"
        :subtitle="shop.name"
        active-route="backoffice.supplier.assistant.index"
        :can-create-shop="false"
    >
        <template #content>
            <div class="space-y-4">
                <AssistantTabs :shop-id="shop.id" :connected="settings.connected" active="settings" />

                <Card v-if="settings.connected">
                    <CardContent class="flex flex-wrap items-center justify-between gap-3 p-4">
                        <div>
                            <h3 class="font-semibold">{{ $t('supplier.assistant.statusTitle') }}</h3>
                            <p class="text-sm text-muted-foreground">
                                {{ isActive ? $t('supplier.assistant.statusActive') : $t('supplier.assistant.statusInactive') }}
                            </p>
                        </div>
                        <Button v-if="isActive" size="sm" variant="outline" @click="setStatus(false)">{{ $t('supplier.assistant.deactivate') }}</Button>
                        <Button v-else size="sm" :disabled="!hasAccess" @click="setStatus(true)">{{ $t('supplier.assistant.reactivate') }}</Button>
                    </CardContent>
                </Card>

                <form class="space-y-4" @submit.prevent="save">
                    <Card>
                        <CardContent class="space-y-3 p-4">
                            <h3 class="font-semibold">{{ $t('supplier.assistant.settingsAi') }}</h3>
                            <div>
                                <p class="text-sm">{{ $t('supplier.assistant.aiModel', { model: settings.ai_model }) }}</p>
                                <p class="text-xs text-muted-foreground">{{ $t('supplier.assistant.aiModelHelp') }}</p>
                            </div>
                            <div class="grid gap-3 sm:grid-cols-3">
                                <div class="space-y-1">
                                    <Label for="set-tone">{{ $t('supplier.assistant.tone') }}</Label>
                                    <Input id="set-tone" v-model="form.tone" />
                                    <InputError :message="form.errors.tone" />
                                </div>
                                <div class="space-y-1">
                                    <Label for="set-language">{{ $t('supplier.assistant.language') }}</Label>
                                    <select id="set-language" v-model="form.language" class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm">
                                        <option value="fr">{{ $t('supplier.assistant.languageFr') }}</option>
                                        <option value="en">{{ $t('supplier.assistant.languageEn') }}</option>
                                    </select>
                                </div>
                                <div class="space-y-1">
                                    <Label for="set-temperature">{{ $t('supplier.assistant.temperature') }}</Label>
                                    <Input id="set-temperature" v-model="form.ai_temperature" type="number" min="0" max="2" step="0.1" />
                                    <p class="text-xs text-muted-foreground">{{ $t('supplier.assistant.temperatureHelp') }}</p>
                                    <InputError :message="form.errors.ai_temperature" />
                                </div>
                            </div>
                            <div class="space-y-1">
                                <Label for="set-delivery">{{ $t('supplier.assistant.deliveryPolicy') }}</Label>
                                <textarea id="set-delivery" v-model="form.delivery_policy" rows="2" class="w-full rounded-md border border-input bg-background p-3 text-sm" />
                                <InputError :message="form.errors.delivery_policy" />
                            </div>
                            <div class="space-y-1">
                                <Label for="set-return">{{ $t('supplier.assistant.returnPolicy') }}</Label>
                                <textarea id="set-return" v-model="form.return_policy" rows="2" class="w-full rounded-md border border-input bg-background p-3 text-sm" />
                                <InputError :message="form.errors.return_policy" />
                            </div>
                            <div class="space-y-1">
                                <Label for="set-extra">{{ $t('supplier.assistant.additionalInstructions') }}</Label>
                                <textarea id="set-extra" v-model="form.additional_instructions" rows="3" class="w-full rounded-md border border-input bg-background p-3 text-sm" />
                                <InputError :message="form.errors.additional_instructions" />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent class="space-y-3 p-4">
                            <h3 class="font-semibold">{{ $t('supplier.assistant.settingsMessages') }}</h3>
                            <label class="flex items-center gap-2 text-sm">
                                <input v-model="form.welcome_message_enabled" type="checkbox" />
                                {{ $t('supplier.assistant.welcomeEnabled') }}
                            </label>
                            <div v-if="form.welcome_message_enabled">
                                <textarea v-model="form.welcome_message" rows="2" maxlength="1024" class="w-full rounded-md border border-input bg-background p-3 text-sm" :aria-label="$t('supplier.assistant.welcomeMessage')" />
                                <InputError :message="form.errors.welcome_message" />
                            </div>
                            <label class="flex items-center gap-2 text-sm">
                                <input v-model="form.away_message_enabled" type="checkbox" />
                                {{ $t('supplier.assistant.awayEnabled') }}
                            </label>
                            <div v-if="form.away_message_enabled">
                                <textarea v-model="form.away_message" rows="2" maxlength="1024" class="w-full rounded-md border border-input bg-background p-3 text-sm" :aria-label="$t('supplier.assistant.awayMessage')" />
                                <InputError :message="form.errors.away_message" />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent class="space-y-3 p-4">
                            <h3 class="font-semibold">{{ $t('supplier.assistant.settingsTransfer') }}</h3>
                            <div class="space-y-1">
                                <Label for="set-keywords">{{ $t('supplier.assistant.transferKeywords') }}</Label>
                                <Input id="set-keywords" v-model="keywords" />
                                <p class="text-xs text-muted-foreground">{{ $t('supplier.assistant.transferKeywordsHelp') }}</p>
                                <InputError :message="form.errors.human_transfer_keywords" />
                            </div>
                            <div class="space-y-1">
                                <Label for="set-address">{{ $t('supplier.assistant.businessAddress') }}</Label>
                                <Input id="set-address" v-model="form.business_address" />
                                <InputError :message="form.errors.business_address" />
                            </div>
                            <label class="flex items-center gap-2 text-sm">
                                <input v-model="form.ai_share_location" type="checkbox" />
                                {{ $t('supplier.assistant.shareLocation') }}
                            </label>
                        </CardContent>
                    </Card>

                    <Button type="submit" :disabled="form.processing || !hasAccess">
                        {{ form.processing ? $t('supplier.assistant.saving') : $t('supplier.assistant.save') }}
                    </Button>
                </form>

                <Card class="border-destructive/40">
                    <CardContent class="space-y-3 p-4">
                        <h3 class="font-semibold text-destructive">{{ $t('supplier.assistant.dangerTitle') }}</h3>
                        <p class="text-sm text-muted-foreground">{{ $t('supplier.assistant.dangerText') }}</p>
                        <div class="space-y-1">
                            <Label for="set-confirm">{{ $t('supplier.assistant.dangerConfirm', { name: shop.name }) }}</Label>
                            <Input id="set-confirm" v-model="removal.confirmation" autocomplete="off" />
                            <InputError :message="removal.errors.confirmation" />
                        </div>
                        <Button variant="destructive" size="sm" :disabled="!canDelete || removal.processing" @click="destroy($t('supplier.assistant.dangerPrompt'))">
                            {{ $t('supplier.assistant.dangerButton') }}
                        </Button>
                    </CardContent>
                </Card>
            </div>
        </template>
    </SupplierLayout>
</template>
