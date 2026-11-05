<script setup>
import SupplierLayout from '@/Layouts/SupplierLayout.vue';
import AssistantTabs from '@/components/supplier/AssistantTabs.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

const props = defineProps({
    shop: { type: Object, required: true },
    hasAccess: { type: Boolean, required: true },
    templates: { type: Array, required: true },
});

const form = useForm({
    name: '',
    category: 'MARKETING',
    language: 'fr',
    body: '',
    examples: [],
    footer: '',
    buttons: ['', '', ''],
});

// Même règle que TemplateCatalog::slug côté serveur, pour afficher le nom final.
const slug = computed(() => form.name
    .normalize('NFD').replace(/[̀-ͯ]/g, '')
    .toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, ''));

const variableCount = computed(() => new Set(form.body.match(/\{\{\d+\}\}/g) ?? []).size);

watch(variableCount, (count) => {
    form.examples = Array.from({ length: count }, (_, index) => form.examples[index] ?? '');
});

const submit = () => form.post(route('backoffice.supplier.assistant.templates.store', props.shop.id), {
    preserveScroll: true,
    onSuccess: () => form.reset(),
});

const remove = (template, message) => {
    if (window.confirm(message)) {
        router.delete(route('backoffice.supplier.assistant.templates.destroy', [props.shop.id, template.id]), { preserveScroll: true });
    }
};

const statusVariant = (status) => ({ APPROVED: 'default', REJECTED: 'destructive' }[status] ?? 'outline');
</script>

<template>
    <Head :title="$t('supplier.assistant.tabTemplates')" />

    <SupplierLayout
        :title="$t('supplier.assistantNav')"
        :subtitle="shop.name"
        active-route="backoffice.supplier.assistant.index"
        :can-create-shop="false"
    >
        <template #content>
            <div class="space-y-4">
                <AssistantTabs :shop-id="shop.id" active="templates" />

                <Card>
                    <CardContent class="p-4">
                        <p v-if="!templates.length" class="text-sm text-muted-foreground">{{ $t('supplier.assistant.noTemplates') }}</p>
                        <ul class="divide-y divide-border">
                            <li v-for="template in templates" :key="template.id" class="flex flex-wrap items-start justify-between gap-2 py-3 text-sm">
                                <div class="min-w-0 flex-1">
                                    <p class="font-medium">{{ template.name }}</p>
                                    <p class="whitespace-pre-wrap break-words text-xs text-muted-foreground">{{ template.body }}</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <Badge :variant="statusVariant(template.status)">{{ $t(`supplier.assistant.status${template.status}`) }}</Badge>
                                    <Button size="sm" variant="outline" :disabled="!hasAccess" @click="remove(template, $t('supplier.assistant.confirmDeleteTemplate'))">
                                        {{ $t('supplier.assistant.delete') }}
                                    </Button>
                                </div>
                            </li>
                        </ul>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="p-4">
                        <h3 class="mb-3 font-semibold">{{ $t('supplier.assistant.newTemplate') }}</h3>
                        <form class="space-y-3" @submit.prevent="submit">
                            <div class="grid gap-3 sm:grid-cols-3">
                                <div class="space-y-1">
                                    <Label for="tpl-name">{{ $t('supplier.assistant.templateName') }}</Label>
                                    <Input id="tpl-name" v-model="form.name" required />
                                    <p v-if="slug" class="break-all text-xs text-muted-foreground">{{ $t('supplier.assistant.templateNameHelp', { slug }) }}</p>
                                    <InputError :message="form.errors.name" />
                                </div>
                                <div class="space-y-1">
                                    <Label for="tpl-category">{{ $t('supplier.assistant.category') }}</Label>
                                    <select id="tpl-category" v-model="form.category" class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm">
                                        <option value="MARKETING">{{ $t('supplier.assistant.categoryMarketing') }}</option>
                                        <option value="UTILITY">{{ $t('supplier.assistant.categoryUtility') }}</option>
                                    </select>
                                </div>
                                <div class="space-y-1">
                                    <Label for="tpl-language">{{ $t('supplier.assistant.templateLanguage') }}</Label>
                                    <select id="tpl-language" v-model="form.language" class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm">
                                        <option value="fr">{{ $t('supplier.assistant.languageFr') }}</option>
                                        <option value="en">{{ $t('supplier.assistant.languageEn') }}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="space-y-1">
                                <Label for="tpl-body">{{ $t('supplier.assistant.templateBody') }}</Label>
                                <textarea id="tpl-body" v-model="form.body" required rows="4" maxlength="1024" class="w-full rounded-md border border-input bg-background p-3 text-sm" placeholder="Bonjour {{1}}, -10 % sur {{2}} cette semaine !" />
                                <p class="text-xs text-muted-foreground">{{ $t('supplier.assistant.templateBodyHelp') }}</p>
                                <InputError :message="form.errors.body" />
                            </div>
                            <div v-for="(_, index) in form.examples" :key="index" class="space-y-1">
                                <Label :for="`tpl-example-${index}`">{{ $t('supplier.assistant.exampleFor', { number: index + 1 }) }}</Label>
                                <Input :id="`tpl-example-${index}`" v-model="form.examples[index]" required />
                            </div>
                            <InputError :message="form.errors.examples" />
                            <div class="space-y-1">
                                <Label for="tpl-footer">{{ $t('supplier.assistant.footer') }}</Label>
                                <Input id="tpl-footer" v-model="form.footer" maxlength="60" />
                            </div>
                            <div class="grid gap-3 sm:grid-cols-3">
                                <div v-for="(_, index) in form.buttons" :key="index" class="space-y-1">
                                    <Label :for="`tpl-button-${index}`">{{ $t('supplier.assistant.buttonText', { number: index + 1 }) }}</Label>
                                    <Input :id="`tpl-button-${index}`" v-model="form.buttons[index]" maxlength="25" />
                                </div>
                            </div>
                            <Button type="submit" :disabled="form.processing || !hasAccess">
                                {{ form.processing ? $t('supplier.assistant.submitting') : $t('supplier.assistant.submitTemplate') }}
                            </Button>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </template>
    </SupplierLayout>
</template>
