<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    users: Object,
    shops: Object,
    products: Object,
    categories: Object,
});

const activeTab = ref('users');

// Users form (create/edit)
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
const userModalOpen = ref(false);
const isEditingUser = ref(false);

const openCreateUser = () => {
    isEditingUser.value = false;
    userForm.reset();
    userModalOpen.value = true;
};

const openEditUser = (user) => {
    isEditingUser.value = true;
    userForm.reset();
    userForm.id = user.id;
    userForm.name = user.name || '';
    userForm.username = user.username || '';
    userForm.email = user.email || '';
    userForm.google_id = user.google_id || '';
    userForm.email_verified = !!user.email_verified_at;
    userForm.phone = user.phone || '';
    userForm.address = user.address || '';
    userForm.role = user.role || 'user';
    userModalOpen.value = true;
};

const submitUser = () => {
    if (isEditingUser.value) {
        userForm.put(route('backoffice.admin.users.update', { user: userForm.id }), {
            preserveScroll: true,
            onSuccess: () => (userModalOpen.value = false),
        });
    } else {
        userForm.post(route('backoffice.admin.users.store'), {
            preserveScroll: true,
            onSuccess: () => (userModalOpen.value = false),
        });
    }
};

const deleteUser = (user) => {
    if (!confirm('Supprimer cet utilisateur ?')) return;
    const form = useForm();
    form.delete(route('backoffice.admin.users.destroy', { user: user.id }), { preserveScroll: true });
};

// For shops/products/categories we'll open simple create forms (similar pattern)
const shopForm = useForm({ id: null, name: '', description: '', city: '', district: '', phone: '', user_id: null });
const shopModalOpen = ref(false);
const isEditingShop = ref(false);
const openCreateShop = () => { isEditingShop.value = false; shopForm.reset(); shopModalOpen.value = true; };
const openEditShop = (shop) => {
    isEditingShop.value = true;
    shopForm.reset();
    shopForm.id = shop.id;
    shopForm.name = shop.name || '';
    shopForm.description = shop.description || '';
    shopForm.city = shop.city || '';
    shopForm.district = shop.district || '';
    shopForm.phone = shop.phone || '';
    shopForm.user_id = shop.user_id || null;
    shopModalOpen.value = true;
};
const submitShop = () => {
    if (isEditingShop.value) {
        shopForm.put(route('backoffice.admin.shops.update', { shop: shopForm.id }), { onSuccess: () => (shopModalOpen.value = false) });
    } else {
        shopForm.post(route('backoffice.admin.shops.store'), { onSuccess: () => (shopModalOpen.value = false) });
    }
};
const deleteShop = (shop) => {
    if (!confirm('Supprimer cette boutique ?')) return;
    const f = useForm();
    f.delete(route('backoffice.admin.shops.destroy', { shop: shop.id }), { preserveScroll: true });
};

const productForm = useForm({ id: null, name: '', description: '', price: 0, category_id: null, shop_id: null });
const productModalOpen = ref(false);
const isEditingProduct = ref(false);
const openCreateProduct = () => { isEditingProduct.value = false; productForm.reset(); productModalOpen.value = true; };
const openEditProduct = (product) => {
    isEditingProduct.value = true;
    productForm.reset();
    productForm.id = product.id;
    productForm.name = product.name || '';
    productForm.description = product.description || '';
    productForm.price = product.price || 0;
    productForm.category_id = product.category_id || null;
    productForm.shop_id = product.shop_id || null;
    productModalOpen.value = true;
};
const submitProduct = () => {
    if (isEditingProduct.value) {
        productForm.put(route('backoffice.admin.products.update', { product: productForm.id }), { onSuccess: () => (productModalOpen.value = false) });
    } else {
        productForm.post(route('backoffice.admin.products.store'), { onSuccess: () => (productModalOpen.value = false) });
    }
};
const deleteProduct = (product) => {
    if (!confirm('Supprimer ce produit ?')) return;
    const f = useForm();
    f.delete(route('backoffice.admin.products.destroy', { product: product.id }), { preserveScroll: true });
};

const categoryForm = useForm({ id: null, name: '', slug: '', description: '' });
const categoryModalOpen = ref(false);
const isEditingCategory = ref(false);
const openCreateCategory = () => { isEditingCategory.value = false; categoryForm.reset(); categoryModalOpen.value = true; };
const openEditCategory = (category) => {
    isEditingCategory.value = true;
    categoryForm.reset();
    categoryForm.id = category.id;
    categoryForm.name = category.name || '';
    categoryForm.slug = category.slug || '';
    categoryForm.description = category.description || '';
    categoryModalOpen.value = true;
};
const submitCategory = () => {
    if (isEditingCategory.value) {
        categoryForm.put(route('backoffice.admin.categories.update', { category: categoryForm.id }), { onSuccess: () => (categoryModalOpen.value = false) });
    } else {
        categoryForm.post(route('backoffice.admin.categories.store'), { onSuccess: () => (categoryModalOpen.value = false) });
    }
};
const deleteCategory = (category) => {
    if (!confirm('Supprimer cette catégorie ?')) return;
    const f = useForm();
    f.delete(route('backoffice.admin.categories.destroy', { category: category.id }), { preserveScroll: true });
};
</script>

<template>
    <Head title="Administration — Gestion" />

    <AdminLayout :title="'Gestion'" :active-route="'backoffice.admin.management'">
        <template #content>
            <div class="py-6">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-medium">Gestion — Tables</h3>
                        <div class="flex items-center gap-2">
                            <button v-if="activeTab === 'users'" @click="openCreateUser" class="btn btn-primary">Nouveau utilisateur</button>
                            <button v-if="activeTab === 'shops'" @click="openCreateShop" class="btn btn-primary">Nouvelle boutique</button>
                            <button v-if="activeTab === 'products'" @click="openCreateProduct" class="btn btn-primary">Nouveau produit</button>
                            <button v-if="activeTab === 'categories'" @click="openCreateCategory" class="btn btn-primary">Nouvelle catégorie</button>
                        </div>
                    </div>

                    <div class="mt-4">
                        <nav class="flex space-x-2">
                            <button @click="activeTab = 'users'" :class="activeTab==='users' ? 'px-3 py-1 rounded bg-primary/12 text-primary' : 'px-3 py-1 rounded text-muted-foreground'">Utilisateurs</button>
                            <button @click="activeTab = 'shops'" :class="activeTab==='shops' ? 'px-3 py-1 rounded bg-primary/12 text-primary' : 'px-3 py-1 rounded text-muted-foreground'">Boutiques</button>
                            <button @click="activeTab = 'products'" :class="activeTab==='products' ? 'px-3 py-1 rounded bg-primary/12 text-primary' : 'px-3 py-1 rounded text-muted-foreground'">Produits</button>
                            <button @click="activeTab = 'categories'" :class="activeTab==='categories' ? 'px-3 py-1 rounded bg-primary/12 text-primary' : 'px-3 py-1 rounded text-muted-foreground'">Catégories</button>
                        </nav>
                    </div>

                    <div class="mt-6">
                        <!-- Users Table -->
                        <div v-show="activeTab === 'users'">
                            <div class="border border-border bg-card shadow sm:rounded-lg">
                                <table class="min-w-full divide-y divide-border">
                                    <thead class="bg-muted/40"><tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">ID</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Nom</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Email</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Rôle</th>
                                        <th class="px-6 py-3"></th>
                                    </tr></thead>
                                    <tbody class="divide-y divide-border bg-card">
                                        <tr v-for="user in props.users.data" :key="user.id">
                                            <td class="px-6 py-4 text-sm text-foreground">{{ user.id }}</td>
                                            <td class="px-6 py-4 text-sm text-foreground">{{ user.name }}</td>
                                            <td class="px-6 py-4 text-sm text-muted-foreground">{{ user.email }}</td>
                                            <td class="px-6 py-4 text-sm text-muted-foreground">{{ user.role }}</td>
                                            <td class="px-6 py-4 text-right text-sm font-medium">
                                                <a :href="route('backoffice.admin.users.show', { user: user.id })" class="text-primary hover:text-primary/80 mr-2">Voir</a>
                                                <button @click.prevent="openEditUser(user)" class="text-secondary hover:text-secondary-foreground mr-2">Éditer</button>
                                                <button @click.prevent="deleteUser(user)" class="text-destructive hover:text-destructive/90">Supprimer</button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Shops Table -->
                        <div v-show="activeTab === 'shops'">
                            <div class="border border-border bg-card shadow sm:rounded-lg">
                                <table class="min-w-full divide-y divide-border">
                                    <thead class="bg-muted/40"><tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">ID</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Nom</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Ville</th>
                                        <th class="px-6 py-3"></th>
                                    </tr></thead>
                                    <tbody class="divide-y divide-border bg-card">
                                        <tr v-for="shop in props.shops.data" :key="shop.id">
                                            <td class="px-6 py-4 text-sm text-foreground">{{ shop.id }}</td>
                                            <td class="px-6 py-4 text-sm text-foreground">{{ shop.name }}</td>
                                            <td class="px-6 py-4 text-sm text-muted-foreground">{{ shop.city }}</td>
                                            <td class="px-6 py-4 text-right text-sm font-medium">
                                                <a :href="route('backoffice.admin.shops.show', { shop: shop.id })" class="text-primary hover:text-primary/80 mr-2">Voir</a>
                                                <button @click.prevent="openEditShop(shop)" class="text-secondary hover:text-secondary-foreground mr-2">Éditer</button>
                                                <button @click.prevent="deleteShop(shop)" class="text-destructive hover:text-destructive/90">Supprimer</button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Products Table -->
                        <div v-show="activeTab === 'products'">
                            <div class="border border-border bg-card shadow sm:rounded-lg">
                                <table class="min-w-full divide-y divide-border">
                                    <thead class="bg-muted/40"><tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">ID</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Nom</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Prix</th>
                                        <th class="px-6 py-3"></th>
                                    </tr></thead>
                                    <tbody class="divide-y divide-border bg-card">
                                        <tr v-for="product in props.products.data" :key="product.id">
                                            <td class="px-6 py-4 text-sm text-foreground">{{ product.id }}</td>
                                            <td class="px-6 py-4 text-sm text-foreground">{{ product.name }}</td>
                                            <td class="px-6 py-4 text-sm text-muted-foreground">{{ product.price }}</td>
                                            <td class="px-6 py-4 text-right text-sm font-medium">
                                                <a :href="route('backoffice.admin.products.show', { product: product.id })" class="text-primary hover:text-primary/80 mr-2">Voir</a>
                                                <button @click.prevent="openEditProduct(product)" class="text-secondary hover:text-secondary-foreground mr-2">Éditer</button>
                                                <button @click.prevent="deleteProduct(product)" class="text-destructive hover:text-destructive/90">Supprimer</button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Categories Table -->
                        <div v-show="activeTab === 'categories'">
                            <div class="border border-border bg-card shadow sm:rounded-lg">
                                <table class="min-w-full divide-y divide-border">
                                    <thead class="bg-muted/40"><tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">ID</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Nom</th>
                                        <th class="px-6 py-3"></th>
                                    </tr></thead>
                                    <tbody class="divide-y divide-border bg-card">
                                        <tr v-for="category in props.categories.data" :key="category.id">
                                            <td class="px-6 py-4 text-sm text-foreground">{{ category.id }}</td>
                                            <td class="px-6 py-4 text-sm text-foreground">{{ category.name }}</td>
                                            <td class="px-6 py-4 text-right text-sm font-medium">
                                                <a :href="route('backoffice.admin.categories.show', { category: category.id })" class="text-primary hover:text-primary/80 mr-2">Voir</a>
                                                <button @click.prevent="openEditCategory(category)" class="text-secondary hover:text-secondary-foreground mr-2">Éditer</button>
                                                <button @click.prevent="deleteCategory(category)" class="text-destructive hover:text-destructive/90">Supprimer</button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- User Modal -->
            <div v-if="userModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
                <div class="mb-6 transform overflow-hidden rounded-lg border border-border bg-card text-foreground shadow-xl p-6 w-full max-w-2xl">
                    <h4 class="text-lg font-semibold mb-4">{{ isEditingUser ? 'Éditer utilisateur' : 'Nouveau utilisateur' }}</h4>
                    <form @submit.prevent="submitUser" class="space-y-3">
                        <div>
                            <label class="block text-sm">Nom</label>
                            <input v-model="userForm.name" class="form-input" />
                            <p v-if="userForm.errors.name" class="text-sm text-destructive mt-1">{{ userForm.errors.name }}</p>
                        </div>
                        <div>
                            <label class="block text-sm">Username</label>
                            <input v-model="userForm.username" class="form-input" />
                            <p v-if="userForm.errors.username" class="text-sm text-destructive mt-1">{{ userForm.errors.username }}</p>
                        </div>
                        <div>
                            <label class="block text-sm">Email</label>
                            <input v-model="userForm.email" class="form-input" type="email" />
                            <p v-if="userForm.errors.email" class="text-sm text-destructive mt-1">{{ userForm.errors.email }}</p>
                        </div>
                        <div>
                            <label class="block text-sm">Google ID</label>
                            <input v-model="userForm.google_id" class="form-input" />
                            <p v-if="userForm.errors.google_id" class="text-sm text-destructive mt-1">{{ userForm.errors.google_id }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <input id="email_verified" type="checkbox" v-model="userForm.email_verified" class="form-checkbox" />
                            <label for="email_verified" class="text-sm">Email vérifié</label>
                        </div>
                        <div>
                            <label class="block text-sm">Rôle</label>
                            <select v-model="userForm.role" class="form-select">
                                <option value="admin">admin</option>
                                <option value="supplier">supplier</option>
                                <option value="user">user</option>
                            </select>
                            <p v-if="userForm.errors.role" class="text-sm text-destructive mt-1">{{ userForm.errors.role }}</p>
                        </div>
                        <div class="flex justify-end gap-2">
                            <button type="button" @click="userModalOpen = false" class="btn">Annuler</button>
                            <button type="submit" class="btn btn-primary">Enregistrer</button>
                        </div>
                        <p v-if="$page.props.flash?.success" class="text-sm text-green-600">{{ $page.props.flash.success }}</p>
                    </form>
                </div>
            </div>

            <!-- Shop Modal (create) -->
            <div v-if="shopModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
                <div class="mb-6 transform overflow-hidden rounded-lg border border-border bg-card text-foreground shadow-xl p-6 w-full max-w-2xl">
                    <h4 class="text-lg font-semibold mb-4">Nouvelle boutique</h4>
                    <form @submit.prevent="submitShop" class="space-y-3">
                        <div>
                            <label class="block text-sm">Nom</label>
                            <input v-model="shopForm.name" class="form-input" />
                            <p v-if="shopForm.errors.name" class="text-sm text-destructive mt-1">{{ shopForm.errors.name }}</p>
                        </div>
                        <div>
                            <label class="block text-sm">Ville</label>
                            <input v-model="shopForm.city" class="form-input" />
                            <p v-if="shopForm.errors.city" class="text-sm text-destructive mt-1">{{ shopForm.errors.city }}</p>
                        </div>
                        <div class="flex justify-end gap-2">
                            <button type="button" @click="shopModalOpen = false" class="btn">Annuler</button>
                            <button type="submit" class="btn btn-primary">{{ isEditingShop ? 'Enregistrer' : 'Créer' }}</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Product Modal (create) -->
            <div v-if="productModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
                <div class="mb-6 transform overflow-hidden rounded-lg border border-border bg-card text-foreground shadow-xl p-6 w-full max-w-2xl">
                    <h4 class="text-lg font-semibold mb-4">Nouveau produit</h4>
                    <form @submit.prevent="submitProduct" class="space-y-3">
                        <div>
                            <label class="block text-sm">Nom</label>
                            <input v-model="productForm.name" class="form-input" />
                            <p v-if="productForm.errors.name" class="text-sm text-destructive mt-1">{{ productForm.errors.name }}</p>
                        </div>
                        <div>
                            <label class="block text-sm">Prix</label>
                            <input v-model="productForm.price" class="form-input" type="number" />
                            <p v-if="productForm.errors.price" class="text-sm text-destructive mt-1">{{ productForm.errors.price }}</p>
                        </div>
                        <div class="flex justify-end gap-2">
                            <button type="button" @click="productModalOpen = false" class="btn">Annuler</button>
                            <button type="submit" class="btn btn-primary">{{ isEditingProduct ? 'Enregistrer' : 'Créer' }}</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Category Modal (create) -->
            <div v-if="categoryModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
                <div class="mb-6 transform overflow-hidden rounded-lg border border-border bg-card text-foreground shadow-xl p-6 w-full max-w-2xl">
                    <h4 class="text-lg font-semibold mb-4">Nouvelle catégorie</h4>
                    <form @submit.prevent="submitCategory" class="space-y-3">
                        <div>
                            <label class="block text-sm">Nom</label>
                            <input v-model="categoryForm.name" class="form-input" />
                            <p v-if="categoryForm.errors.name" class="text-sm text-destructive mt-1">{{ categoryForm.errors.name }}</p>
                        </div>
                        <div class="flex justify-end gap-2">
                            <button type="button" @click="categoryModalOpen = false" class="btn">Annuler</button>
                            <button type="submit" class="btn btn-primary">{{ isEditingCategory ? 'Enregistrer' : 'Créer' }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </template>
    </AdminLayout>
</template>
