<script setup>
import SupplierLayout from '@/Layouts/SupplierLayout.vue';
import AssistantCatalog from '@/components/supplier/AssistantCatalog.vue';
import AssistantTabs from '@/components/supplier/AssistantTabs.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { AlertCircle, Check, ExternalLink } from 'lucide-vue-next';
import { computed } from 'vue';
import { date } from '@/lib/orders';

const props = defineProps({
    shop: { type: Object, required: true },
    shops: { type: Array, required: true },
    hasAccess: { type: Boolean, required: true },
    bot: { type: Object, default: null },
    catalogStats: { type: Object, default: null },
    catalog: { type: Object, default: null },
});

const form = useForm({
    tone: props.bot?.tone ?? '',
    language: props.bot?.language ?? 'fr',
    delivery_policy: props.bot?.delivery_policy ?? '',
    return_policy: props.bot?.return_policy ?? '',
});
const action = useForm({});

const provisioned = computed(() => !!props.bot?.provisioned);
const connected = computed(() => !!props.bot?.connected);
const active = computed(() => props.bot?.status === 'active');
const needsFinalize = computed(() => connected.value && !active.value && props.bot?.status !== 'inactive');
const whatsappLink = computed(() => `https://wa.me/${(props.bot?.whatsapp_phone_number ?? '').replace(/\D/g, '')}`);

const steps = computed(() => [
    { key: 'stepCreate', done: provisioned.value },
    { key: 'stepConnect', done: active.value },
    { key: 'stepCatalog', done: !!props.bot?.catalog_ai_enabled },
    { key: 'stepTest', done: false },
]);

const activate = () => form.post(route('backoffice.supplier.assistant.activate', props.shop.id), { preserveScroll: true });
const connect = () => action.post(route('backoffice.supplier.assistant.connect', props.shop.id));
const finalize = () => action.post(route('backoffice.supplier.assistant.finalize', props.shop.id), { preserveScroll: true });
</script>

<template>
    <Head :title="$t('supplier.assistantNav')" />

    <SupplierLayout
        :title="$t('supplier.assistantNav')"
        :subtitle="$t('supplier.assistantSubtitle')"
        active-route="backoffice.supplier.assistant.index"
        :can-create-shop="false"
    >
        <template #content>
            <div class="space-y-4">
                <nav v-if="shops.length > 1" class="flex flex-wrap gap-2" :aria-label="$t('supplier.shops')">
                    <Link
                        v-for="item in shops"
                        :key="item.id"
                        :href="route('backoffice.supplier.assistant.show', item.id)"
                        class="rounded-full border px-3 py-1 text-sm"
                        :class="item.id === shop.id ? 'border-primary bg-primary/10 text-primary' : 'border-border text-muted-foreground'"
                    >
                        {{ item.name }}
                    </Link>
                </nav>

                <AssistantTabs v-if="provisioned" :shop-id="shop.id" :connected="connected" active="activation" />

                <Card v-if="!hasAccess && !bot">
                    <CardContent class="space-y-3 p-6">
                        <h3 class="font-semibold">{{ $t('supplier.assistant.upgradeTitle') }}</h3>
                        <p class="text-sm text-muted-foreground">{{ $t('supplier.assistant.upgradeText') }}</p>
                        <Link :href="route('backoffice.supplier.subscriptions.index')">
                            <Button size="sm">{{ $t('supplier.assistant.upgradeCta') }}</Button>
                        </Link>
                    </CardContent>
                </Card>

                <template v-else>
                    <div v-if="bot?.last_error" class="flex items-start gap-2 rounded-lg border border-destructive/40 bg-destructive/10 p-3 text-sm text-destructive" role="alert">
                        <AlertCircle class="mt-0.5 h-4 w-4 shrink-0" />
                        <span>{{ bot.last_error }}</span>
                    </div>

                    <ol class="grid grid-cols-2 gap-2 md:grid-cols-4">
                        <li
                            v-for="(step, index) in steps"
                            :key="step.key"
                            class="flex items-center gap-2 rounded-lg border p-3 text-sm"
                            :class="step.done ? 'border-primary/40 bg-primary/5' : 'border-border'"
                        >
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-xs" :class="step.done ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground'">
                                <Check v-if="step.done" class="h-3.5 w-3.5" :aria-label="$t('supplier.assistant.done')" />
                                <template v-else>{{ index + 1 }}</template>
                            </span>
                            <span>{{ $t(`supplier.assistant.${step.key}`) }}</span>
                        </li>
                    </ol>

                    <Card v-if="!provisioned">
                        <CardContent class="p-6">
                            <h3 class="mb-4 font-semibold">{{ $t('supplier.assistant.stepCreate') }}</h3>
                            <form class="space-y-4" @submit.prevent="activate">
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div class="space-y-1.5">
                                        <Label for="tone">{{ $t('supplier.assistant.tone') }}</Label>
                                        <Input id="tone" v-model="form.tone" :placeholder="$t('supplier.assistant.tonePlaceholder')" />
                                        <InputError :message="form.errors.tone" />
                                    </div>
                                    <div class="space-y-1.5">
                                        <Label for="language">{{ $t('supplier.assistant.language') }}</Label>
                                        <select id="language" v-model="form.language" class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm">
                                            <option value="fr">{{ $t('supplier.assistant.languageFr') }}</option>
                                            <option value="en">{{ $t('supplier.assistant.languageEn') }}</option>
                                        </select>
                                        <InputError :message="form.errors.language" />
                                    </div>
                                </div>
                                <div class="space-y-1.5">
                                    <Label for="delivery">{{ $t('supplier.assistant.deliveryPolicy') }}</Label>
                                    <textarea id="delivery" v-model="form.delivery_policy" rows="3" class="w-full rounded-md border border-input bg-background p-3 text-sm" :placeholder="$t('supplier.assistant.deliveryPlaceholder')" />
                                    <InputError :message="form.errors.delivery_policy" />
                                </div>
                                <div class="space-y-1.5">
                                    <Label for="return">{{ $t('supplier.assistant.returnPolicy') }}</Label>
                                    <textarea id="return" v-model="form.return_policy" rows="3" class="w-full rounded-md border border-input bg-background p-3 text-sm" :placeholder="$t('supplier.assistant.returnPlaceholder')" />
                                    <InputError :message="form.errors.return_policy" />
                                </div>
                                <Button type="submit" :disabled="form.processing || !hasAccess">
                                    <template v-if="form.processing">{{ $t('supplier.assistant.activating') }}</template>
                                    <template v-else>{{ bot ? $t('supplier.assistant.resume') : $t('supplier.assistant.activate') }}</template>
                                </Button>
                            </form>
                        </CardContent>
                    </Card>

                    <Card v-else>
                        <CardContent class="space-y-3 p-6">
                            <h3 class="font-semibold">{{ $t('supplier.assistant.stepConnect') }}</h3>

                            <template v-if="connected">
                                <p class="text-sm">{{ $t('supplier.assistant.connectedNumber', { number: bot.whatsapp_phone_number }) }}</p>
                                <p v-if="bot.quality_rating" class="text-sm text-muted-foreground">{{ $t('supplier.assistant.qualityRating', { rating: bot.quality_rating }) }}</p>
                                <p v-if="bot.token_expires_at" class="text-sm text-muted-foreground">{{ $t('supplier.assistant.tokenExpires', { date: date(bot.token_expires_at) }) }}</p>
                                <Button v-if="needsFinalize" size="sm" :disabled="action.processing || !hasAccess" @click="finalize">
                                    {{ $t('supplier.assistant.finalize') }}
                                </Button>
                            </template>

                            <template v-else>
                                <div class="rounded-lg bg-muted/50 p-3 text-sm">
                                    <p class="mb-1 font-medium">{{ $t('supplier.assistant.prerequisitesTitle') }}</p>
                                    <ul class="list-inside list-disc space-y-0.5 text-muted-foreground">
                                        <li>{{ $t('supplier.assistant.prerequisiteMeta') }}</li>
                                        <li>{{ $t('supplier.assistant.prerequisitePhone') }}</li>
                                        <li>{{ $t('supplier.assistant.prerequisiteRedirect') }}</li>
                                    </ul>
                                </div>
                                <Button :disabled="action.processing || !hasAccess" @click="connect">
                                    <template v-if="action.processing">{{ $t('supplier.assistant.connecting') }}</template>
                                    <template v-else>{{ bot.status === 'pending' ? $t('supplier.assistant.connect') : $t('supplier.assistant.retryConnect') }}</template>
                                </Button>
                            </template>
                        </CardContent>
                    </Card>

                    <AssistantCatalog
                        v-if="active"
                        :shop-id="shop.id"
                        :enabled="bot.catalog_ai_enabled"
                        :has-access="hasAccess"
                        :stats="catalogStats"
                        :catalog="catalog"
                    />

                    <Card v-if="active">
                        <CardContent class="space-y-3 p-6">
                            <h3 class="font-semibold">{{ $t('supplier.assistant.stepTest') }}</h3>
                            <p class="text-sm text-muted-foreground">{{ $t('supplier.assistant.testText') }}</p>
                            <a :href="whatsappLink" target="_blank" rel="noopener noreferrer">
                                <Button size="sm" variant="outline" class="gap-1.5">
                                    <ExternalLink class="h-4 w-4" />
                                    {{ $t('supplier.assistant.testLink') }}
                                </Button>
                            </a>
                        </CardContent>
                    </Card>
                </template>
            </div>
        </template>
    </SupplierLayout>
</template>
