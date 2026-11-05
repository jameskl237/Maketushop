<script setup>
import Pagination from '@/components/Pagination.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { router, useForm, usePoll } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import { datetime } from '@/lib/orders';

const props = defineProps({
    shopId: { type: Number, required: true },
    enabled: { type: Boolean, required: true },
    hasAccess: { type: Boolean, required: true },
    stats: { type: Object, default: null },
    catalog: { type: Object, default: null },
});

const form = useForm({});

const sync = () => form.post(route('backoffice.supplier.assistant.catalog.sync', props.shopId), { preserveScroll: true });

const toggleHighlight = (item) => router.patch(
    route('backoffice.supplier.assistant.catalog.update', [props.shopId, item.id]),
    { is_ad: !item.is_ad },
    { preserveScroll: true, only: ['catalog', 'catalogStats'] },
);

const pending = computed(() => props.enabled && props.stats
    && props.stats.synced + props.stats.errors < props.stats.total);

const { start, stop } = usePoll(5000, { only: ['catalog', 'catalogStats'] }, { autoStart: false });

watch(pending, (value) => (value ? start() : stop()), { immediate: true });
</script>

<template>
    <Card>
        <CardContent class="space-y-3 p-6">
            <h3 class="font-semibold">{{ $t('supplier.assistant.stepCatalog') }}</h3>
            <p class="text-sm text-muted-foreground">{{ $t('supplier.assistant.catalogIntro') }}</p>

            <div v-if="enabled && stats" class="space-y-0.5 text-sm">
                <p>{{ $t('supplier.assistant.catalogProgress', { synced: stats.synced, total: stats.total }) }}</p>
                <p v-if="pending" class="text-muted-foreground">{{ $t('supplier.assistant.catalogPending') }}</p>
                <p v-if="stats.errors" class="text-destructive">{{ $t('supplier.assistant.catalogErrors', { count: stats.errors }) }}</p>
                <p v-if="stats.last_synced_at" class="text-muted-foreground">
                    {{ $t('supplier.assistant.catalogLastSync', { date: datetime(stats.last_synced_at) }) }}
                </p>
            </div>

            <Button size="sm" :variant="enabled ? 'outline' : 'default'" :disabled="form.processing || !hasAccess" @click="sync">
                {{ enabled ? $t('supplier.assistant.catalogResync') : $t('supplier.assistant.catalogSync') }}
            </Button>

            <template v-if="catalog?.data?.length">
                <p class="pt-2 text-xs text-muted-foreground">{{ $t('supplier.assistant.catalogHighlightHelp') }}</p>
                <ul class="divide-y divide-border rounded-lg border border-border">
                    <li v-for="item in catalog.data" :key="item.id" class="flex flex-wrap items-center justify-between gap-2 p-3 text-sm">
                        <div class="min-w-0">
                            <p class="truncate font-medium">{{ item.name ?? $t('supplier.assistant.catalogDeleted') }}</p>
                            <p class="text-xs text-muted-foreground">
                                {{ item.type === 'service' ? $t('supplier.assistant.catalogService') : $t('supplier.assistant.catalogProduct') }}
                            </p>
                            <p v-if="item.sync_error" class="text-xs text-destructive">{{ item.sync_error }}</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <Badge v-if="!item.sync_error" :variant="item.synced ? 'secondary' : 'outline'">
                                {{ item.synced ? $t('supplier.assistant.catalogStatusSynced') : $t('supplier.assistant.catalogStatusPending') }}
                            </Badge>
                            <label class="flex items-center gap-1.5 text-xs">
                                <input type="checkbox" :checked="item.is_ad" :disabled="!hasAccess" @change="toggleHighlight(item)" />
                                {{ $t('supplier.assistant.catalogHighlight') }}
                            </label>
                        </div>
                    </li>
                </ul>
                <Pagination :links="catalog.links" />
            </template>
        </CardContent>
    </Card>
</template>
