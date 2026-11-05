<script setup>
import SupplierLayout from '@/Layouts/SupplierLayout.vue';
import AssistantTabs from '@/components/supplier/AssistantTabs.vue';
import Pagination from '@/components/Pagination.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import InputError from '@/components/InputError.vue';
import { Head, Link, router, useForm, usePoll } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import { datetime } from '@/lib/orders';

const REPLY_COST = 10;

const props = defineProps({
    shop: { type: Object, required: true },
    hasAccess: { type: Boolean, required: true },
    conversations: { type: Object, required: true },
    conversation: { type: Object, default: null },
    messages: { type: Object, default: null },
    templates: { type: Array, default: () => [] },
});

usePoll(8000, { only: ['conversations', 'conversation', 'messages', 'templates'] });

const form = useForm({ message: '' });
const templateForm = useForm({ template_id: '', parameters: [] });
const selectedTemplate = computed(() => props.templates.find((item) => item.id === templateForm.template_id));

watch(() => templateForm.template_id, () => {
    templateForm.parameters = Array(selectedTemplate.value?.variable_count ?? 0).fill('');
});

const sendTemplate = () => templateForm.post(
    route('backoffice.supplier.assistant.inbox.template', [props.shop.id, props.conversation.id]),
    { preserveScroll: true, onSuccess: () => templateForm.reset() },
);

// Page la plus récente en premier côté serveur : on l'affiche dans l'ordre de lecture.
const orderedMessages = computed(() => [...(props.messages?.data ?? [])].reverse());
const clientLabel = (item) => item.client_name || item.client_phone;

const reply = () => form.post(
    route('backoffice.supplier.assistant.inbox.reply', [props.shop.id, props.conversation.id]),
    { preserveScroll: true, onSuccess: () => form.reset() },
);

const toggleAi = () => router.put(
    route('backoffice.supplier.assistant.inbox.ai', [props.shop.id, props.conversation.id]),
    { ai_enabled: !props.conversation.ai_enabled },
    { preserveScroll: true },
);
</script>

<template>
    <Head :title="$t('supplier.assistant.tabInbox')" />

    <SupplierLayout
        :title="$t('supplier.assistantNav')"
        :subtitle="shop.name"
        active-route="backoffice.supplier.assistant.index"
        :can-create-shop="false"
    >
        <template #content>
            <div class="space-y-4">
                <AssistantTabs :shop-id="shop.id" active="inbox" />

                <div class="grid gap-4 lg:grid-cols-[20rem_1fr]">
                    <Card>
                        <CardContent class="p-2">
                            <p v-if="!conversations.data.length" class="p-4 text-sm text-muted-foreground">
                                {{ $t('supplier.assistant.inboxEmpty') }}
                            </p>
                            <ul class="space-y-1">
                                <li v-for="item in conversations.data" :key="item.id">
                                    <Link
                                        :href="route('backoffice.supplier.assistant.inbox.show', [shop.id, item.id])"
                                        class="block rounded-lg p-3 text-sm hover:bg-accent"
                                        :class="conversation?.id === item.id ? 'bg-primary/10' : ''"
                                    >
                                        <span class="flex items-center justify-between gap-2">
                                            <span class="truncate font-medium">{{ clientLabel(item) || $t('supplier.assistant.unknownClient') }}</span>
                                            <Badge v-if="item.unread_count">{{ item.unread_count }}</Badge>
                                        </span>
                                        <span class="block truncate text-xs text-muted-foreground">{{ item.preview }}</span>
                                    </Link>
                                </li>
                            </ul>
                            <Pagination class="p-2" :links="conversations.links" />
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent v-if="conversation" class="space-y-4 p-4">
                            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-border pb-3">
                                <div class="min-w-0">
                                    <p class="truncate font-medium">{{ clientLabel(conversation) || $t('supplier.assistant.unknownClient') }}</p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ conversation.ai_enabled ? $t('supplier.assistant.aiActive') : $t('supplier.assistant.aiPaused') }}
                                    </p>
                                </div>
                                <Button size="sm" variant="outline" :disabled="!hasAccess" @click="toggleAi">
                                    {{ conversation.ai_enabled ? $t('supplier.assistant.takeOver') : $t('supplier.assistant.handBack') }}
                                </Button>
                            </div>

                            <Pagination v-if="messages" :links="messages.links" />

                            <ul class="max-h-[28rem] space-y-2 overflow-y-auto">
                                <li
                                    v-for="message in orderedMessages"
                                    :key="message.id"
                                    class="flex"
                                    :class="message.direction === 'inbound' ? 'justify-start' : 'justify-end'"
                                >
                                    <div
                                        class="max-w-[80%] whitespace-pre-wrap break-words rounded-lg px-3 py-2 text-sm"
                                        :class="message.direction === 'inbound' ? 'bg-muted' : 'bg-primary/10'"
                                    >
                                        <p>{{ message.body }}</p>
                                        <p class="mt-1 text-[10px] text-muted-foreground">
                                            {{ datetime(message.sent_at) }}
                                            <span v-if="message.status === 'failed'" class="text-destructive">· {{ $t('supplier.assistant.statusFailed') }}</span>
                                        </p>
                                    </div>
                                </li>
                            </ul>

                            <form v-if="conversation.can_send_free_message" class="space-y-2" @submit.prevent="reply">
                                <textarea
                                    v-model="form.message"
                                    rows="2"
                                    required
                                    maxlength="4096"
                                    class="w-full rounded-md border border-input bg-background p-3 text-sm"
                                    :placeholder="$t('supplier.assistant.replyPlaceholder')"
                                    :aria-label="$t('supplier.assistant.replyPlaceholder')"
                                />
                                <div class="flex items-center justify-between gap-2">
                                    <p class="text-xs text-muted-foreground">{{ $t('supplier.assistant.replyCost', { credits: REPLY_COST }) }}</p>
                                    <Button type="submit" size="sm" :disabled="form.processing || !hasAccess">
                                        {{ form.processing ? $t('supplier.assistant.sending') : $t('supplier.assistant.send') }}
                                    </Button>
                                </div>
                            </form>
                            <form v-else class="space-y-2 rounded-lg bg-muted/50 p-3" @submit.prevent="sendTemplate">
                                <p class="text-sm text-muted-foreground">{{ $t('supplier.assistant.windowClosed') }}</p>
                                <template v-if="templates.length">
                                    <select v-model="templateForm.template_id" required class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm" :aria-label="$t('supplier.assistant.chooseTemplate')">
                                        <option value="" disabled>{{ $t('supplier.assistant.chooseTemplate') }}</option>
                                        <option v-for="item in templates" :key="item.id" :value="item.id">{{ item.name }}</option>
                                    </select>
                                    <p v-if="selectedTemplate" class="whitespace-pre-wrap text-sm">{{ selectedTemplate.body }}</p>
                                    <Input
                                        v-for="(_, index) in templateForm.parameters"
                                        :key="index"
                                        v-model="templateForm.parameters[index]"
                                        required
                                        :aria-label="`{{${index + 1}}}`"
                                        :placeholder="`{{${index + 1}}}`"
                                    />
                                    <InputError :message="templateForm.errors.parameters" />
                                    <Button type="submit" size="sm" :disabled="templateForm.processing || !hasAccess">
                                        {{ templateForm.processing ? $t('supplier.assistant.sending') : $t('supplier.assistant.sendTemplate') }}
                                    </Button>
                                </template>
                                <Link v-else :href="route('backoffice.supplier.assistant.templates.index', shop.id)" class="text-sm text-primary underline">
                                    {{ $t('supplier.assistant.noApprovedTemplate') }}
                                </Link>
                            </form>
                        </CardContent>
                        <CardContent v-else class="p-8 text-center text-sm text-muted-foreground">
                            {{ $t('supplier.assistant.inboxSelect') }}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </template>
    </SupplierLayout>
</template>
