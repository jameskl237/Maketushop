<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    shopId: { type: Number, required: true },
    active: { type: String, required: true },
    // Avant la connexion WhatsApp, seuls l'activation et les paramètres ont un sens.
    connected: { type: Boolean, default: true },
});

const tabs = [
    { key: 'activation', labelKey: 'tabActivation', route: 'backoffice.supplier.assistant.show' },
    { key: 'inbox', labelKey: 'tabInbox', route: 'backoffice.supplier.assistant.inbox.index', needsConnection: true },
    { key: 'clients', labelKey: 'tabClients', route: 'backoffice.supplier.assistant.clients.index', needsConnection: true },
    { key: 'templates', labelKey: 'tabTemplates', route: 'backoffice.supplier.assistant.templates.index', needsConnection: true },
    { key: 'campaigns', labelKey: 'tabCampaigns', route: 'backoffice.supplier.assistant.campaigns.index', needsConnection: true },
    { key: 'settings', labelKey: 'tabSettings', route: 'backoffice.supplier.assistant.settings.edit' },
    { key: 'stats', labelKey: 'tabStats', route: 'backoffice.supplier.assistant.stats' },
];

const visibleTabs = computed(() => tabs.filter((tab) => props.connected || !tab.needsConnection));
</script>

<template>
    <nav class="flex flex-wrap gap-1 border-b border-border">
        <Link
            v-for="tab in visibleTabs"
            :key="tab.key"
            :href="route(tab.route, shopId)"
            class="-mb-px border-b-2 px-3 py-2 text-sm"
            :class="active === tab.key ? 'border-primary font-medium text-primary' : 'border-transparent text-muted-foreground hover:text-foreground'"
        >
            {{ $t(`supplier.assistant.${tab.labelKey}`) }}
        </Link>
    </nav>
</template>
