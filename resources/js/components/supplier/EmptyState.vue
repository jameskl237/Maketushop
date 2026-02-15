<script setup>
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Store } from 'lucide-vue-next';

defineProps({
    title: { type: String, required: true },
    description: { type: String, required: true },
    actionLabel: { type: String, default: 'Créer' },
});

defineEmits(['action']);
</script>

<template>
    <!-- Exemple:
      <EmptyState title="Aucune boutique" description="..." action-label="Créer ma boutique" @action="openDialog" />
    -->
    <Card class="border-dashed border-border/80">
        <CardHeader class="text-center">
            <div class="mx-auto mb-2 flex h-14 w-14 items-center justify-center rounded-xl bg-primary/10 text-primary">
                <Store class="h-7 w-7" />
            </div>
            <CardTitle>{{ title }}</CardTitle>
            <CardDescription>{{ description }}</CardDescription>
        </CardHeader>
        <CardContent class="text-center">
            <Button @click="$emit('action')">{{ actionLabel }}</Button>
        </CardContent>
    </Card>
</template>

