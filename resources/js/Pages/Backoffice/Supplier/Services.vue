<script setup>
import SupplierLayout from '@/Layouts/SupplierLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogScrollContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps({
    services: { type: Array, default: () => [] },
    shops: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
});

const dialogOpen = ref(false);
const editingId = ref(null);

const form = useForm({
    shop_id: props.shops[0]?.id ?? null,
    title: '',
    description: '',
    long_description: '',
    quote_only: false,
    price: '',
    city: '',
    is_active: true,
    category_id: null,
});

const openCreate = () => {
    editingId.value = null;
    form.reset();
    form.shop_id = props.shops[0]?.id ?? null;
    dialogOpen.value = true;
};

const openEdit = (service) => {
    editingId.value = service.id;
    form.shop_id = service.shop_id;
    form.title = service.title;
    form.description = service.description ?? '';
    form.long_description = service.long_description ?? '';
    form.quote_only = Boolean(service.quote_only);
    form.price = service.price ?? '';
    form.city = service.city ?? '';
    form.is_active = Boolean(service.is_active);
    form.category_id = service.category_id ?? null;
    dialogOpen.value = true;
};

const submit = () => {
    if (editingId.value) {
        form.put(route('backoffice.supplier.services.update', { service: editingId.value }), {
            preserveScroll: true,
            onSuccess: () => { dialogOpen.value = false; },
        });
    } else {
        form.post(route('backoffice.supplier.services.store'), {
            preserveScroll: true,
            onSuccess: () => { dialogOpen.value = false; },
        });
    }
};

const destroy = (service) => {
    if (!confirm(`Supprimer le service "${service.title}" ?`)) return;
    router.delete(route('backoffice.supplier.services.destroy', { service: service.id }), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head :title="$t('supplier.services')" />

    <SupplierLayout
        :title="$t('supplier.services')"
        :subtitle="$t('supplier.servicesSubtitle')"
        active-route="backoffice.supplier.services.index"
        :can-create-shop="false"
    >
        <template #content>
            <Card>
                <CardHeader class="flex flex-row items-center justify-between">
                    <CardTitle>{{ $t('supplier.services') }}</CardTitle>
                    <Button size="sm" :disabled="!shops.length" @click="openCreate">
                        <Plus class="h-4 w-4" />
                        Nouveau service
                    </Button>
                </CardHeader>
                <CardContent>
                    <div v-if="!shops.length" class="rounded-lg border border-dashed border-border p-8 text-center text-sm text-muted-foreground">
                        Crée d'abord une boutique pour proposer des services.
                    </div>
                    <div v-else-if="services.length === 0" class="rounded-lg border border-dashed border-border p-8 text-center text-sm text-muted-foreground">
                        {{ $t('supplier.noServices') }}
                    </div>

                    <div v-else class="w-full overflow-x-auto rounded-lg border border-border">
                        <table class="min-w-[800px] text-sm">
                            <thead class="bg-muted/40">
                                <tr class="text-left">
                                    <th class="px-4 py-3 font-medium">Titre</th>
                                    <th class="px-4 py-3 font-medium">Boutique</th>
                                    <th class="px-4 py-3 font-medium">Catégorie</th>
                                    <th class="px-4 py-3 font-medium">Prix</th>
                                    <th class="px-4 py-3 font-medium">Statut</th>
                                    <th class="px-4 py-3 font-medium">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="service in services" :key="service.id" class="border-t border-border">
                                    <td class="px-4 py-3 font-medium">{{ service.title }}</td>
                                    <td class="px-4 py-3">{{ service.shop?.name || '-' }}</td>
                                    <td class="px-4 py-3">{{ service.category?.name || '-' }}</td>
                                    <td class="px-4 py-3">
                                        <span v-if="service.quote_only" class="text-muted-foreground">Sur devis</span>
                                        <span v-else>{{ service.price }} FCFA</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <Badge :variant="service.is_active ? 'secondary' : 'outline'">
                                            {{ service.is_active ? 'Actif' : 'Inactif' }}
                                        </Badge>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <button type="button" class="text-primary hover:underline" @click="openEdit(service)">
                                                <Pencil class="h-4 w-4" />
                                            </button>
                                            <button type="button" class="text-destructive hover:underline" @click="destroy(service)">
                                                <Trash2 class="h-4 w-4" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>

            <Dialog v-model:open="dialogOpen">
                <DialogScrollContent class="max-h-[90vh] overflow-y-auto rounded-2xl">
                    <DialogHeader>
                        <DialogTitle>{{ editingId ? 'Modifier le service' : 'Nouveau service' }}</DialogTitle>
                    </DialogHeader>

                    <div class="space-y-3">
                        <div v-if="!editingId" class="space-y-1.5">
                            <Label>Boutique</Label>
                            <select v-model="form.shop_id" class="h-10 w-full rounded-md border border-border bg-background px-3 text-sm">
                                <option v-for="shop in shops" :key="shop.id" :value="shop.id">{{ shop.name }}</option>
                            </select>
                        </div>

                        <div class="space-y-1.5">
                            <Label>Titre</Label>
                            <Input v-model="form.title" placeholder="Ex : Création de logo" />
                            <p v-if="form.errors.title" class="text-[11px] text-destructive">{{ form.errors.title }}</p>
                        </div>

                        <div class="space-y-1.5">
                            <Label>Description courte</Label>
                            <Input v-model="form.description" placeholder="Résumé en une ligne" />
                        </div>

                        <div class="space-y-1.5">
                            <Label>Description détaillée</Label>
                            <Textarea v-model="form.long_description" rows="3" placeholder="Détails de la prestation..." />
                        </div>

                        <div class="space-y-1.5">
                            <Label>Catégorie</Label>
                            <select v-model="form.category_id" class="h-10 w-full rounded-md border border-border bg-background px-3 text-sm">
                                <option :value="null">— Aucune —</option>
                                <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                            </select>
                        </div>

                        <label class="flex items-center gap-2 text-sm">
                            <input v-model="form.quote_only" type="checkbox" class="h-4 w-4 rounded border-border" />
                            Service sur devis (pas de prix fixe)
                        </label>

                        <div v-if="!form.quote_only" class="space-y-1.5">
                            <Label>Prix (FCFA)</Label>
                            <Input v-model="form.price" type="number" min="0" placeholder="25000" />
                            <p v-if="form.errors.price" class="text-[11px] text-destructive">{{ form.errors.price }}</p>
                        </div>

                        <div class="space-y-1.5">
                            <Label>Ville</Label>
                            <Input v-model="form.city" placeholder="Douala" />
                        </div>

                        <label class="flex items-center gap-2 text-sm">
                            <input v-model="form.is_active" type="checkbox" class="h-4 w-4 rounded border-border" />
                            Service actif (visible sur la marketplace)
                        </label>
                    </div>

                    <Button :disabled="form.processing" class="mt-2 w-full" @click="submit">
                        {{ editingId ? 'Enregistrer' : 'Créer le service' }}
                    </Button>
                </DialogScrollContent>
            </Dialog>
        </template>
    </SupplierLayout>
</template>
