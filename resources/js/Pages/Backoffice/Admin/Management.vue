<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Pagination from '@/Components/Pagination.vue';
import DeleteConfirmationModal from '@/Components/DeleteConfirmationModal.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    users: Object,
    shops: Object,
    products: Object,
    categories: Object,
    orders: Object,
    all_users: Array,
    all_shops: Array,
    all_categories: Array,
});

const activeTab = ref(new URLSearchParams(window.location.search).get('tab') || 'users');

const setActiveTab = (tab) => {
    activeTab.value = tab;
    // Optional: update URL without reload to persist tab on refresh
    const url = new URL(window.location);
    url.searchParams.set('tab', tab);
    window.history.pushState({}, '', url);
};

// --- MODALS & FORMS ---

const deleteModalOpen = ref(false);
const itemToDelete = ref(null);
const deleteType = ref('');

const confirmDelete = (item, type) => {
    itemToDelete.value = item;
    deleteType.value = type;
    deleteModalOpen.value = true;
};

const executeDelete = () => {
    const routes = {
        user: 'backoffice.admin.users.destroy',
        shop: 'backoffice.admin.shops.destroy',
        product: 'backoffice.admin.products.destroy',
        category: 'backoffice.admin.categories.destroy',
        order: 'backoffice.admin.orders.destroy',
    };

    router.delete(route(routes[deleteType.value], { [deleteType.value]: itemToDelete.value.id }), {
        preserveScroll: true,
        onSuccess: () => {
            deleteModalOpen.value = false;
            itemToDelete.value = null;
        },
    });
};

// Forms
const userForm = useForm({
    id: null,
    name: '',
    username: '',
    email: '',
    google_id: '',
    email_verified: false,
    phone: '',
    address: '',
    role: 'user',
    password: '',
    password_confirmation: '',
});

const shopForm = useForm({
    id: null,
    name: '',
    description: '',
    city: '',
    district: '',
    phone: '',
    user_id: '',
});

const productForm = useForm({
    id: null,
    name: '',
    description: '',
    long_description: '',
    price: 0,
    promotion_price: '',
    in_stock: true,
    quantity: 0,
    category_id: '',
    shop_id: '',
    user_id: '',
    code: '',
});

const categoryForm = useForm({
    id: null,
    name: '',
    slug: '',
    description: '',
});

// Modal control
const modalOpen = ref({
    user: false,
    shop: false,
    product: false,
    category: false,
});

const isEditing = ref({
    user: false,
    shop: false,
    product: false,
    category: false,
});

const openModal = (type, item = null) => {
    if (item) {
        isEditing.value[type] = true;
        if (type === 'user') {
            userForm.id = item.id;
            userForm.name = item.name;
            userForm.username = item.username;
            userForm.email = item.email;
            userForm.google_id = item.google_id;
            userForm.email_verified = !!item.email_verified_at;
            userForm.phone = item.phone;
            userForm.address = item.address;
            userForm.role = item.role;
        } else if (type === 'shop') {
            shopForm.id = item.id;
            shopForm.name = item.name;
            shopForm.description = item.description;
            shopForm.city = item.city;
            shopForm.district = item.district;
            shopForm.phone = item.phone;
            shopForm.user_id = item.user_id;
        } else if (type === 'product') {
            productForm.id = item.id;
            productForm.name = item.name;
            productForm.description = item.description;
            productForm.long_description = item.long_description;
            productForm.price = item.price;
            productForm.promotion_price = item.promotion_price;
            productForm.in_stock = item.in_stock;
            productForm.quantity = item.quantity;
            productForm.category_id = item.category_id;
            productForm.shop_id = item.shop_id;
            productForm.user_id = item.user_id;
            productForm.code = item.code;
        } else if (type === 'category') {
            categoryForm.id = item.id;
            categoryForm.name = item.name;
            categoryForm.slug = item.slug;
            categoryForm.description = item.description;
        }
    } else {
        isEditing.value[type] = false;
        if (type === 'user') userForm.reset();
        else if (type === 'shop') shopForm.reset();
        else if (type === 'product') productForm.reset();
        else if (type === 'category') categoryForm.reset();
    }
    modalOpen.value[type] = true;
};

const submitForm = (type) => {
    const form = type === 'user' ? userForm : (type === 'shop' ? shopForm : (type === 'product' ? productForm : categoryForm));
    const singularType = type;
    const baseRoute = `backoffice.admin.${type}s`;
    
    if (isEditing.value[type]) {
        form.put(route(`${baseRoute}.update`, { [singularType]: form.id }), {
            onSuccess: () => modalOpen.value[type] = false,
        });
    } else {
        form.post(route(`${baseRoute}.store`), {
            onSuccess: () => modalOpen.value[type] = false,
        });
    }
};

</script>

<template>
    <Head title="Gestion du Catalogue" />

    <AdminLayout title="Gestion du Catalogue" subtitle="Gérez les utilisateurs, boutiques, produits et catégories.">
        <template #content>
            <div class="space-y-6">
                <!-- Tabs -->
                <div class="border-b border-border">
                    <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                        <button v-for="tab in ['users', 'shops', 'products', 'categories', 'orders']" :key="tab"
                            @click="setActiveTab(tab)"
                            :class="[
                                activeTab === tab
                                    ? 'border-primary text-primary font-semibold'
                                    : 'border-transparent text-muted-foreground hover:text-foreground hover:border-border',
                                'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm capitalize transition-colors'
                            ]">
                            {{ tab === 'users' ? 'Utilisateurs' : (tab === 'shops' ? 'Boutiques' : (tab === 'products' ? 'Produits' : (tab === 'categories' ? 'Catégories' : 'Commandes'))) }}
                        </button>
                    </nav>
                </div>

                <!-- Action Bar -->
                <div class="flex justify-between items-center">
                    <h3 class="text-xl font-bold text-foreground">
                        {{ activeTab === 'users' ? 'Utilisateurs' : (activeTab === 'shops' ? 'Boutiques' : (activeTab === 'products' ? 'Produits' : (activeTab === 'categories' ? 'Catégories' : 'Commandes'))) }}
                    </h3>
                    <PrimaryButton v-if="activeTab !== 'orders'" @click="openModal(activeTab.slice(0, -1))">
                        Ajouter {{ activeTab === 'users' ? 'un utilisateur' : (activeTab === 'shops' ? 'une boutique' : (activeTab === 'products' ? 'un produit' : 'une catégorie')) }}
                    </PrimaryButton>
                </div>

                <!-- Tables -->
                <div class="overflow-x-auto rounded-xl border border-border bg-card shadow-sm">
                    
                    <!-- Users -->
                    <table v-if="activeTab === 'users'" class="w-full text-left text-sm">
                        <thead class="bg-muted/50 text-muted-foreground border-b border-border">
                            <tr>
                                <th class="p-4 font-medium">ID</th>
                                <th class="p-4 font-medium">Utilisateur</th>
                                <th class="p-4 font-medium">Contact</th>
                                <th class="p-4 font-medium">Rôle</th>
                                <th class="p-4 font-medium text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <tr v-for="user in props.users.data" :key="user.id" class="hover:bg-muted/30 transition-colors">
                                <td class="p-4 font-mono text-xs">{{ user.id }}</td>
                                <td class="p-4">
                                    <div class="font-medium text-foreground">{{ user.name }}</div>
                                    <div class="text-xs text-muted-foreground">@{{ user.username }}</div>
                                </td>
                                <td class="p-4">
                                    <div class="text-foreground">{{ user.email }}</div>
                                    <div class="text-xs text-muted-foreground">{{ user.phone || 'Pas de téléphone' }}</div>
                                </td>
                                <td class="p-4">
                                    <span :class="[
                                        'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium capitalize',
                                        user.role === 'admin' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' : 
                                        (user.role === 'supplier' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400' : 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-400')
                                    ]">
                                        {{ user.role }}
                                    </span>
                                </td>
                                <td class="p-4 text-right space-x-2">
                                    <button @click="openModal('user', user)" class="text-primary hover:underline font-medium">Éditer</button>
                                    <button @click="confirmDelete(user, 'user')" class="text-destructive hover:underline font-medium">Supprimer</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Shops -->
                    <table v-if="activeTab === 'shops'" class="w-full text-left text-sm">
                        <thead class="bg-muted/50 text-muted-foreground border-b border-border">
                            <tr>
                                <th class="p-4 font-medium">ID</th>
                                <th class="p-4 font-medium">Boutique</th>
                                <th class="p-4 font-medium">Localisation</th>
                                <th class="p-4 font-medium">Vendeur</th>
                                <th class="p-4 font-medium text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <tr v-for="shop in props.shops.data" :key="shop.id" class="hover:bg-muted/30 transition-colors">
                                <td class="p-4 font-mono text-xs">{{ shop.id }}</td>
                                <td class="p-4">
                                    <div class="font-medium text-foreground">{{ shop.name }}</div>
                                    <div class="text-xs text-muted-foreground">{{ shop.phone }}</div>
                                </td>
                                <td class="p-4">
                                    <div class="text-foreground">{{ shop.city }}</div>
                                    <div class="text-xs text-muted-foreground">{{ shop.district }}</div>
                                </td>
                                <td class="p-4 text-muted-foreground">{{ shop.user?.name || 'N/A' }}</td>
                                <td class="p-4 text-right space-x-2">
                                    <button @click="openModal('shop', shop)" class="text-primary hover:underline font-medium">Éditer</button>
                                    <button @click="confirmDelete(shop, 'shop')" class="text-destructive hover:underline font-medium">Supprimer</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Products -->
                    <table v-if="activeTab === 'products'" class="w-full text-left text-sm">
                        <thead class="bg-muted/50 text-muted-foreground border-b border-border">
                            <tr>
                                <th class="p-4 font-medium">ID</th>
                                <th class="p-4 font-medium">Produit</th>
                                <th class="p-4 font-medium">Prix</th>
                                <th class="p-4 font-medium">Stock</th>
                                <th class="p-4 font-medium">Catégorie / Boutique</th>
                                <th class="p-4 font-medium text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <tr v-for="product in props.products.data" :key="product.id" class="hover:bg-muted/30 transition-colors">
                                <td class="p-4 font-mono text-xs">{{ product.id }}</td>
                                <td class="p-4">
                                    <div class="font-medium text-foreground">{{ product.name }}</div>
                                    <div class="text-xs text-muted-foreground">Code: {{ product.code || 'N/A' }}</div>
                                </td>
                                <td class="p-4">
                                    <div class="font-bold text-foreground">{{ product.price }} FCFA</div>
                                    <div v-if="product.promotion_price" class="text-xs text-green-600">Promo: {{ product.promotion_price }}</div>
                                </td>
                                <td class="p-4">
                                    <span :class="[
                                        'px-2 py-0.5 rounded text-xs font-medium',
                                        product.in_stock ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'
                                    ]">
                                        {{ product.in_stock ? 'En Stock' : 'Rupture' }}
                                    </span>
                                    <div class="text-xs text-muted-foreground mt-1">Qté: {{ product.quantity }}</div>
                                </td>
                                <td class="p-4">
                                    <div class="text-foreground">{{ product.category?.name || 'Sans catégorie' }}</div>
                                    <div class="text-xs text-muted-foreground">{{ product.shop?.name || 'Sans boutique' }}</div>
                                </td>
                                <td class="p-4 text-right space-x-2">
                                    <button @click="openModal('product', product)" class="text-primary hover:underline font-medium">Éditer</button>
                                    <button @click="confirmDelete(product, 'product')" class="text-destructive hover:underline font-medium">Supprimer</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Categories -->
                    <table v-if="activeTab === 'categories'" class="w-full text-left text-sm">
                        <thead class="bg-muted/50 text-muted-foreground border-b border-border">
                            <tr>
                                <th class="p-4 font-medium">ID</th>
                                <th class="p-4 font-medium">Nom</th>
                                <th class="p-4 font-medium">Slug</th>
                                <th class="p-4 font-medium">Description</th>
                                <th class="p-4 font-medium text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <tr v-for="category in props.categories.data" :key="category.id" class="hover:bg-muted/30 transition-colors">
                                <td class="p-4 font-mono text-xs">{{ category.id }}</td>
                                <td class="p-4 font-medium text-foreground">{{ category.name }}</td>
                                <td class="p-4 text-muted-foreground">{{ category.slug }}</td>
                                <td class="p-4 text-muted-foreground truncate max-w-xs">{{ category.description }}</td>
                                <td class="p-4 text-right space-x-2">
                                    <button @click="openModal('category', category)" class="text-primary hover:underline font-medium">Éditer</button>
                                    <button @click="confirmDelete(category, 'category')" class="text-destructive hover:underline font-medium">Supprimer</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Orders -->
                    <table v-if="activeTab === 'orders'" class="w-full text-left text-sm">
                        <thead class="bg-muted/50 text-muted-foreground border-b border-border">
                            <tr>
                                <th class="p-4 font-medium">ID</th>
                                <th class="p-4 font-medium">Numéro</th>
                                <th class="p-4 font-medium">Client</th>
                                <th class="p-4 font-medium">Articles</th>
                                <th class="p-4 font-medium">Total</th>
                                <th class="p-4 font-medium text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <tr v-for="order in props.orders.data" :key="order.id" class="hover:bg-muted/30 transition-colors">
                                <td class="p-4 font-mono text-xs">{{ order.id }}</td>
                                <td class="p-4 font-bold text-foreground">{{ order.order_number }}</td>
                                <td class="p-4 text-muted-foreground">{{ order.user?.name || 'Client inconnu' }}</td>
                                <td class="p-4 text-foreground">{{ order.total_products }}</td>
                                <td class="p-4 font-bold text-primary">{{ order.total_price }} FCFA</td>
                                <td class="p-4 text-right">
                                    <button @click="confirmDelete(order, 'order')" class="text-destructive hover:underline font-medium">Annuler / Suppr</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-4 flex justify-center">
                    <Pagination :links="props[activeTab].links" />
                </div>
            </div>

            <!-- MODALS -->

            <!-- User Modal -->
            <Modal :show="modalOpen.user" @close="modalOpen.user = false">
                <form @submit.prevent="submitForm('user')" class="p-6">
                    <h2 class="text-lg font-medium text-foreground mb-4">{{ isEditing.user ? 'Modifier l\'utilisateur' : 'Nouvel utilisateur' }}</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="name" value="Nom Complet" />
                            <TextInput id="name" type="text" class="mt-1 block w-full" v-model="userForm.name" required />
                            <InputError :message="userForm.errors.name" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="username" value="Nom d'utilisateur" />
                            <TextInput id="username" type="text" class="mt-1 block w-full" v-model="userForm.username" required />
                            <InputError :message="userForm.errors.username" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="email" value="Email" />
                            <TextInput id="email" type="email" class="mt-1 block w-full" v-model="userForm.email" required />
                            <InputError :message="userForm.errors.email" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="phone" value="Téléphone" />
                            <TextInput id="phone" type="text" class="mt-1 block w-full" v-model="userForm.phone" />
                            <InputError :message="userForm.errors.phone" class="mt-2" />
                        </div>
                        <div class="md:col-span-2">
                            <InputLabel for="address" value="Adresse" />
                            <TextInput id="address" type="text" class="mt-1 block w-full" v-model="userForm.address" />
                            <InputError :message="userForm.errors.address" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="role" value="Rôle" />
                            <select id="role" v-model="userForm.role" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-primary focus:ring-primary rounded-md shadow-sm">
                                <option value="user">Utilisateur / Client</option>
                                <option value="supplier">Vendeur / Supplier</option>
                                <option value="admin">Administrateur</option>
                            </select>
                            <InputError :message="userForm.errors.role" class="mt-2" />
                        </div>
                        <div class="flex items-center pt-6">
                            <label class="flex items-center">
                                <input type="checkbox" v-model="userForm.email_verified" class="rounded border-gray-300 text-primary shadow-sm focus:ring-primary" />
                                <span class="ms-2 text-sm text-muted-foreground">Email vérifié</span>
                            </label>
                        </div>
                        <template v-if="!isEditing.user">
                            <div>
                                <InputLabel for="password" value="Mot de passe" />
                                <TextInput id="password" type="password" class="mt-1 block w-full" v-model="userForm.password" required />
                            </div>
                            <div>
                                <InputLabel for="password_confirmation" value="Confirmation" />
                                <TextInput id="password_confirmation" type="password" class="mt-1 block w-full" v-model="userForm.password_confirmation" required />
                            </div>
                        </template>
                    </div>
                    <div class="mt-6 flex justify-end space-x-3">
                        <SecondaryButton @click="modalOpen.user = false">Annuler</SecondaryButton>
                        <PrimaryButton :disabled="userForm.processing">Enregistrer</PrimaryButton>
                    </div>
                </form>
            </Modal>

            <!-- Shop Modal -->
            <Modal :show="modalOpen.shop" @close="modalOpen.shop = false">
                <form @submit.prevent="submitForm('shop')" class="p-6">
                    <h2 class="text-lg font-medium text-foreground mb-4">{{ isEditing.shop ? 'Modifier la boutique' : 'Nouvelle boutique' }}</h2>
                    <div class="space-y-4">
                        <div>
                            <InputLabel for="shop_name" value="Nom de la boutique" />
                            <TextInput id="shop_name" type="text" class="mt-1 block w-full" v-model="shopForm.name" required />
                            <InputError :message="shopForm.errors.name" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="shop_desc" value="Description" />
                            <textarea id="shop_desc" v-model="shopForm.description" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-primary focus:ring-primary rounded-md shadow-sm" rows="3"></textarea>
                            <InputError :message="shopForm.errors.description" class="mt-2" />
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <InputLabel for="shop_city" value="Ville" />
                                <TextInput id="shop_city" type="text" class="mt-1 block w-full" v-model="shopForm.city" />
                            </div>
                            <div>
                                <InputLabel for="shop_district" value="Quartier" />
                                <TextInput id="shop_district" type="text" class="mt-1 block w-full" v-model="shopForm.district" />
                            </div>
                        </div>
                        <div>
                            <InputLabel for="shop_phone" value="Téléphone boutique" />
                            <TextInput id="shop_phone" type="text" class="mt-1 block w-full" v-model="shopForm.phone" />
                        </div>
                        <div>
                            <InputLabel for="shop_user" value="Vendeur (Propriétaire)" />
                            <select id="shop_user" v-model="shopForm.user_id" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-primary focus:ring-primary rounded-md shadow-sm">
                                <option value="">Sélectionner un vendeur</option>
                                <option v-for="user in all_users" :key="user.id" :value="user.id">{{ user.name }}</option>
                            </select>
                            <InputError :message="shopForm.errors.user_id" class="mt-2" />
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end space-x-3">
                        <SecondaryButton @click="modalOpen.shop = false">Annuler</SecondaryButton>
                        <PrimaryButton :disabled="shopForm.processing">Enregistrer</PrimaryButton>
                    </div>
                </form>
            </Modal>

            <!-- Product Modal -->
            <Modal :show="modalOpen.product" @close="modalOpen.product = false">
                <form @submit.prevent="submitForm('product')" class="p-6">
                    <h2 class="text-lg font-medium text-foreground mb-4">{{ isEditing.product ? 'Modifier le produit' : 'Nouveau produit' }}</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <InputLabel for="prod_name" value="Nom du produit" />
                            <TextInput id="prod_name" type="text" class="mt-1 block w-full" v-model="productForm.name" required />
                            <InputError :message="productForm.errors.name" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="prod_code" value="Code SKU" />
                            <TextInput id="prod_code" type="text" class="mt-1 block w-full" v-model="productForm.code" />
                        </div>
                        <div>
                            <InputLabel for="prod_price" value="Prix (FCFA)" />
                            <TextInput id="prod_price" type="number" class="mt-1 block w-full" v-model="productForm.price" required />
                        </div>
                        <div>
                            <InputLabel for="prod_promo" value="Prix Promotionnel" />
                            <TextInput id="prod_promo" type="number" class="mt-1 block w-full" v-model="productForm.promotion_price" />
                        </div>
                        <div>
                            <InputLabel for="prod_qty" value="Quantité en stock" />
                            <TextInput id="prod_qty" type="number" class="mt-1 block w-full" v-model="productForm.quantity" />
                        </div>
                        <div>
                            <InputLabel for="prod_cat" value="Catégorie" />
                            <select id="prod_cat" v-model="productForm.category_id" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-primary focus:ring-primary rounded-md shadow-sm">
                                <option value="">Sélectionner</option>
                                <option v-for="cat in all_categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                            </select>
                        </div>
                        <div>
                            <InputLabel for="prod_shop" value="Boutique" />
                            <select id="prod_shop" v-model="productForm.shop_id" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-primary focus:ring-primary rounded-md shadow-sm">
                                <option value="">Sélectionner</option>
                                <option v-for="shop in all_shops" :key="shop.id" :value="shop.id">{{ shop.name }}</option>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <InputLabel value="Description Courte" />
                            <textarea v-model="productForm.description" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-primary focus:ring-primary rounded-md shadow-sm" rows="2"></textarea>
                        </div>
                        <div class="flex items-center">
                            <label class="flex items-center">
                                <input type="checkbox" v-model="productForm.in_stock" class="rounded border-gray-300 text-primary shadow-sm focus:ring-primary" />
                                <span class="ms-2 text-sm text-muted-foreground">En stock</span>
                            </label>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end space-x-3">
                        <SecondaryButton @click="modalOpen.product = false">Annuler</SecondaryButton>
                        <PrimaryButton :disabled="productForm.processing">Enregistrer</PrimaryButton>
                    </div>
                </form>
            </Modal>

            <!-- Category Modal -->
            <Modal :show="modalOpen.category" @close="modalOpen.category = false">
                <form @submit.prevent="submitForm('category')" class="p-6">
                    <h2 class="text-lg font-medium text-foreground mb-4">{{ isEditing.category ? 'Modifier la catégorie' : 'Nouvelle catégorie' }}</h2>
                    <div class="space-y-4">
                        <div>
                            <InputLabel for="cat_name" value="Nom" />
                            <TextInput id="cat_name" type="text" class="mt-1 block w-full" v-model="categoryForm.name" required />
                            <InputError :message="categoryForm.errors.name" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="cat_slug" value="Slug (URL)" />
                            <TextInput id="cat_slug" type="text" class="mt-1 block w-full" v-model="categoryForm.slug" placeholder="laisse vide pour générer" />
                        </div>
                        <div>
                            <InputLabel for="cat_desc" value="Description" />
                            <textarea id="cat_desc" v-model="categoryForm.description" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-primary focus:ring-primary rounded-md shadow-sm" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end space-x-3">
                        <SecondaryButton @click="modalOpen.category = false">Annuler</SecondaryButton>
                        <PrimaryButton :disabled="categoryForm.processing">Enregistrer</PrimaryButton>
                    </div>
                </form>
            </Modal>

            <!-- Delete Confirmation -->
            <DeleteConfirmationModal 
                :show="deleteModalOpen" 
                @close="deleteModalOpen = false" 
                @confirm="executeDelete"
                :title="'Confirmer la suppression'"
                :message="'Êtes-vous sûr de vouloir supprimer cet élément (' + deleteType + ') ? Cette action est irréversible.'"
            />

        </template>
    </AdminLayout>
</template>
