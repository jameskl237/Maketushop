<script setup>
import { Card, CardContent } from '@/components/ui/card';

defineProps({
    title: { type: String, required: true },
    value: { type: [String, Number], required: true },
    hint: { type: String, default: '' },
    loading: { type: Boolean, default: false },
    icon: { type: [Object, Function], default: null },
    trendType: { type: String, default: 'neutral' }, // neutral | positive
});
</script>

<template>
    <!-- Exemple:
      <StatCard title="Boutiques" :value="12" hint="+2 ce mois" :icon="Store" trend-type="positive" />
    -->
    <Card class="border-border/60 transition-all duration-300 hover:shadow-sm">
        <CardContent class="p-5">
            <div v-if="loading" class="space-y-3" aria-busy="true">
                <div class="h-4 w-1/2 animate-pulse rounded bg-muted" />
                <div class="h-8 w-1/3 animate-pulse rounded bg-muted" />
                <div class="h-3 w-2/3 animate-pulse rounded bg-muted" />
            </div>

            <div v-else class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-sm text-muted-foreground">{{ title }}</p>
                    <p class="mt-1 text-2xl font-semibold tracking-tight text-foreground">{{ value }}</p>
                    <p v-if="hint" class="mt-2 text-xs" :class="trendType === 'positive' ? 'text-emerald-600' : 'text-muted-foreground'">
                        {{ hint }}
                    </p>
                </div>
                <div v-if="icon" class="rounded-lg bg-primary/10 p-2 text-primary">
                    <component :is="icon" class="h-4 w-4" />
                </div>
            </div>
        </CardContent>
    </Card>
</template>

