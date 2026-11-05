<script setup>
import SupplierLayout from '@/Layouts/SupplierLayout.vue';
import AssistantTabs from '@/components/supplier/AssistantTabs.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Head, router, useForm } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';

const props = defineProps({
    shop: { type: Object, required: true },
    hasAccess: { type: Boolean, required: true },
    clients: { type: Array, required: true },
    hasMore: { type: Boolean, required: true },
    page: { type: Number, required: true },
    filters: { type: Object, required: true },
    loadError: { type: String, default: null },
});

const parseTags = (value) => value.split(',').map((tag) => tag.trim()).filter(Boolean);

const filters = reactive({
    search: props.filters.search ?? '',
    tag: props.filters.tag ?? '',
    opted_out: props.filters.opted_out ?? '',
});

const load = (page = 1) => {
    const query = { page };
    Object.entries(filters).forEach(([key, value]) => {
        if (value !== '') query[key] = value;
    });
    router.get(route('backoffice.supplier.assistant.clients.index', props.shop.id), query, { preserveState: true, preserveScroll: true });
};

const createForm = useForm({ phone: '', name: '', tags: '' });
const create = () => createForm
    .transform((data) => ({ ...data, tags: parseTags(data.tags) }))
    .post(route('backoffice.supplier.assistant.clients.store', props.shop.id), {
        preserveScroll: true,
        onSuccess: () => createForm.reset(),
    });

const editing = ref(null);
const editForm = useForm({ name: '', tags: '', opted_out: false });

const startEdit = (client) => {
    editing.value = client.id;
    editForm.defaults({ name: client.name ?? '', tags: client.tags.join(', '), opted_out: client.opted_out });
    editForm.reset();
};

const saveEdit = (client) => editForm
    .transform((data) => ({ ...data, tags: parseTags(data.tags) }))
    .put(route('backoffice.supplier.assistant.clients.update', [props.shop.id, client.id]), {
        preserveScroll: true,
        onSuccess: () => { editing.value = null; },
    });

const remove = (client, message) => {
    if (window.confirm(message)) {
        router.delete(route('backoffice.supplier.assistant.clients.destroy', [props.shop.id, client.id]), { preserveScroll: true });
    }
};
</script>

<template>
    <Head :title="$t('supplier.assistant.tabClients')" />

    <SupplierLayout
        :title="$t('supplier.assistantNav')"
        :subtitle="shop.name"
        active-route="backoffice.supplier.assistant.index"
        :can-create-shop="false"
    >
        <template #content>
            <div class="space-y-4">
                <AssistantTabs :shop-id="shop.id" active="clients" />

                <p v-if="loadError" class="rounded-lg border border-destructive/40 bg-destructive/10 p-3 text-sm text-destructive" role="alert">{{ loadError }}</p>

                <Card>
                    <CardContent class="p-4">
                        <form class="grid gap-2 sm:grid-cols-[1fr_10rem_10rem_auto]" @submit.prevent="load()">
                            <Input v-model="filters.search" :placeholder="$t('supplier.assistant.search')" :aria-label="$t('supplier.assistant.search')" />
                            <Input v-model="filters.tag" :placeholder="$t('supplier.assistant.allTags')" :aria-label="$t('supplier.assistant.tags')" />
                            <select v-model="filters.opted_out" class="h-10 rounded-md border border-input bg-background px-3 text-sm" :aria-label="$t('supplier.assistant.allClients')">
                                <option value="">{{ $t('supplier.assistant.allClients') }}</option>
                                <option value="0">{{ $t('supplier.assistant.subscribed') }}</option>
                                <option value="1">{{ $t('supplier.assistant.unsubscribed') }}</option>
                            </select>
                            <Button type="submit" variant="outline">{{ $t('supplier.assistant.filter') }}</Button>
                        </form>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="p-4">
                        <p v-if="!clients.length" class="text-sm text-muted-foreground">{{ $t('supplier.assistant.noClients') }}</p>
                        <ul class="divide-y divide-border">
                            <li v-for="client in clients" :key="client.id" class="py-3 text-sm">
                                <form v-if="editing === client.id" class="space-y-2" @submit.prevent="saveEdit(client)">
                                    <div class="grid gap-2 sm:grid-cols-2">
                                        <Input v-model="editForm.name" :placeholder="$t('supplier.assistant.name')" :aria-label="$t('supplier.assistant.name')" />
                                        <Input v-model="editForm.tags" :placeholder="$t('supplier.assistant.tagsHelp')" :aria-label="$t('supplier.assistant.tags')" />
                                    </div>
                                    <label class="flex items-center gap-2 text-xs">
                                        <input v-model="editForm.opted_out" type="checkbox" />
                                        {{ $t('supplier.assistant.markOptedOut') }}
                                    </label>
                                    <div class="flex gap-2">
                                        <Button type="submit" size="sm" :disabled="editForm.processing">{{ $t('supplier.assistant.save') }}</Button>
                                        <Button type="button" size="sm" variant="outline" @click="editing = null">{{ $t('supplier.assistant.cancel') }}</Button>
                                    </div>
                                </form>
                                <div v-else class="flex flex-wrap items-center justify-between gap-2">
                                    <div class="min-w-0">
                                        <p class="truncate font-medium">{{ client.name || client.phone }}</p>
                                        <p class="text-xs text-muted-foreground">{{ client.phone }}</p>
                                        <div class="mt-1 flex flex-wrap gap-1">
                                            <Badge v-for="tag in client.tags" :key="tag" variant="secondary">{{ tag }}</Badge>
                                            <Badge v-if="client.opted_out" variant="destructive">{{ $t('supplier.assistant.optedOut') }}</Badge>
                                        </div>
                                    </div>
                                    <div class="flex gap-2">
                                        <Button size="sm" variant="outline" :disabled="!hasAccess" @click="startEdit(client)">{{ $t('supplier.assistant.edit') }}</Button>
                                        <Button size="sm" variant="outline" :disabled="!hasAccess" @click="remove(client, $t('supplier.assistant.confirmDeleteClient'))">{{ $t('supplier.assistant.delete') }}</Button>
                                    </div>
                                </div>
                            </li>
                        </ul>
                        <div v-if="page > 1 || hasMore" class="mt-3 flex justify-between">
                            <Button size="sm" variant="outline" :disabled="page <= 1" @click="load(page - 1)">{{ $t('supplier.assistant.previous') }}</Button>
                            <Button size="sm" variant="outline" :disabled="!hasMore" @click="load(page + 1)">{{ $t('supplier.assistant.next') }}</Button>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="p-4">
                        <h3 class="mb-3 font-semibold">{{ $t('supplier.assistant.addClient') }}</h3>
                        <form class="grid gap-2 sm:grid-cols-[1fr_1fr_1fr_auto]" @submit.prevent="create">
                            <div>
                                <Input v-model="createForm.phone" required :placeholder="$t('supplier.assistant.phone')" :aria-label="$t('supplier.assistant.phone')" />
                                <InputError :message="createForm.errors.phone" />
                            </div>
                            <Input v-model="createForm.name" :placeholder="$t('supplier.assistant.name')" :aria-label="$t('supplier.assistant.name')" />
                            <Input v-model="createForm.tags" :placeholder="$t('supplier.assistant.tagsHelp')" :aria-label="$t('supplier.assistant.tags')" />
                            <Button type="submit" :disabled="createForm.processing || !hasAccess">{{ $t('supplier.assistant.add') }}</Button>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </template>
    </SupplierLayout>
</template>
