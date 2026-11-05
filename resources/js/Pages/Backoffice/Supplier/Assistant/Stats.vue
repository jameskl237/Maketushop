<script setup>
import SupplierLayout from '@/Layouts/SupplierLayout.vue';
import AssistantTabs from '@/components/supplier/AssistantTabs.vue';
import StatCard from '@/components/supplier/StatCard.vue';
import { Head } from '@inertiajs/vue3';
import { Coins, Inbox, Megaphone, MessagesSquare, Send, Users } from 'lucide-vue-next';

defineProps({
    shop: { type: Object, required: true },
    stats: { type: Object, required: true },
});
</script>

<template>
    <Head :title="$t('supplier.assistant.tabStats')" />

    <SupplierLayout
        :title="$t('supplier.assistantNav')"
        :subtitle="shop.name"
        active-route="backoffice.supplier.assistant.index"
        :can-create-shop="false"
    >
        <template #content>
            <div class="space-y-4">
                <AssistantTabs :shop-id="shop.id" active="stats" />

                <div class="grid grid-cols-2 gap-3 lg:grid-cols-3">
                    <StatCard
                        :title="$t('supplier.assistant.statConversations')"
                        :value="stats.conversations"
                        :hint="$t('supplier.assistant.statConversationsHint', { count: stats.conversations_30d })"
                        :icon="MessagesSquare"
                    />
                    <StatCard :title="$t('supplier.assistant.statClients')" :value="stats.clients" :icon="Users" />
                    <StatCard :title="$t('supplier.assistant.statInbound')" :value="stats.messages_inbound" :icon="Inbox" />
                    <StatCard :title="$t('supplier.assistant.statOutbound')" :value="stats.messages_outbound" :icon="Send" />
                    <StatCard
                        :title="$t('supplier.assistant.statCampaigns')"
                        :value="stats.campaigns"
                        :hint="$t('supplier.assistant.statCampaignsHint', { count: stats.campaign_recipients })"
                        :icon="Megaphone"
                    />
                    <StatCard
                        :title="$t('supplier.assistant.statCredits')"
                        :value="stats.credits_month"
                        :hint="$t('supplier.assistant.statCreditsHint', { quota: stats.credits_quota, total: stats.credits_total })"
                        :icon="Coins"
                    />
                </div>

                <p class="text-xs text-muted-foreground">{{ $t('supplier.assistant.statsNote') }}</p>
            </div>
        </template>
    </SupplierLayout>
</template>
