<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import Pagination from '@/components/Pagination.vue';
import DeleteConfirmationModal from '@/components/DeleteConfirmationModal.vue';
import Modal from '@/components/Modal.vue';
import InputLabel from '@/components/InputLabel.vue';
import TextInput from '@/components/TextInput.vue';
import InputError from '@/components/InputError.vue';
import PrimaryButton from '@/components/PrimaryButton.vue';
import SecondaryButton from '@/components/SecondaryButton.vue';
import { countries } from '@/lib/countries';

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

const { t } = useI18n();

const activeTab = ref(new URLSearchParams(window.location.search).get('tab') || 'users');

const setActiveTab = (tab) => {
    activeTab.value = tab;
    // Optional: update URL without reload to persist tab on refresh
    const url = new URL(window.location);
    url.searchParams.set('tab', tab);
    window.history.pushState({}, '', url);
};

const orderStatusLabel = (status) => ({
    pending: t('admin.management.orderPending'),
    delivered: t('admin.management.orderDelivered'),
}[status] || t('admin.management.orderPending'));

const orderStatusClass = (status) => status === 'delivered'
    ? 'border-green-500/20 bg-green-500/10 text-green-700 dark:text-green-400'
    : 'border-amber-500/20 bg-amber-500/10 text-amber-700 dark:text-amber-400';

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

const shopSelectedCode = ref('+237');
const shopPhoneNumber = ref('');

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

            const phone = item.phone ?? '';
            const country = countries.find(c => phone.startsWith(c.code.replace('+', '')));
            if (country) {
                shopSelectedCode.value = country.code;
                shopPhoneNumber.value = phone.substring(country.code.replace('+', '').length);
            } else {
                shopSelectedCode.value = '+237';
                shopPhoneNumber.value = phone;
            }
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
        else if (type === 'shop') {
            shopForm.reset();
            shopSelectedCode.value = '+237';
            shopPhoneNumber.value = '';
        }
        else if (type === 'product') productForm.reset();
        else if (type === 'category') categoryForm.reset();
    }
    modalOpen.value[type] = true;
};

const submitForm = (type) => {
    const form = type === 'user' ? userForm : (type === 'shop' ? shopForm : (type === 'product' ? productForm : categoryForm));
    
    if (type === 'shop') {
        const index = shopSelectedCode.value.replace('+', '');
        shopForm.phone = index + shopPhoneNumber.value.replace(/\s+/g, '');
    }

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
    <Head :title="$t('admin.management.pageTitle')" />

    <AdminLayout :title="$t('admin.management.pageTitle')" :subtitle="$t('admin.management.pageSubtitle')">
        <template #content>
            <div class="space-y-6">
                <!-- Tabs -->
                <div class="border-b border-border overflow-x-auto no-scrollbar">
                    <nav class="-mb-px flex space-x-4 sm:space-x-8" :aria-label="$t('admin.management.tabsAriaLabel')">
                        <button v-for="tab in ['users', 'shops', 'products', 'categories', 'orders']" :key="tab"
                            @click="setActiveTab(tab)"
                            :class="[
                                activeTab === tab
                                    ? 'border-primary text-primary font-semibold'
                                    : 'border-transparent text-muted-foreground hover:text-foreground hover:border-border',
                                'shrink-0 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm capitalize transition-colors'
                            ]">
                            {{ $t(`admin.management.tabs.${tab}`) }}
                        </button>
                    </nav>
                </div>

                <!-- Action Bar -->
                <div class="flex justify-between items-center">
                    <h3 class="text-xl font-bold text-foreground">
                        {{ $t(`admin.management.tabs.${activeTab}`) }}
                    </h3>
                    <PrimaryButton v-if="activeTab !== 'orders'" @click="openModal(activeTab.slice(0, -1))">
                        {{ activeTab === 'users' ? $t('admin.management.addUser') : (activeTab === 'shops' ? $t('admin.management.addShop') : (activeTab === 'products' ? $t('admin.management.addProduct') : $t('admin.management.addCategory'))) }}
                    </PrimaryButton>
                </div>

                <!-- Tables -->
                <div class="overflow-x-auto rounded-xl border border-border bg-card shadow-sm">
                    
                    <!-- Users -->
                    <table v-if="activeTab === 'users'" class="w-full text-left text-sm">
                        <thead class="bg-muted/50 text-muted-foreground border-b border-border">
                            <tr>
                                <th class="p-4 font-medium">{{ $t('admin.management.table.id') }}</th>
                                <th class="p-4 font-medium">{{ $t('admin.management.table.user') }}</th>
                                <th class="p-4 font-medium">{{ $t('admin.management.table.contact') }}</th>
                                <th class="p-4 font-medium">{{ $t('admin.management.table.role') }}</th>
                                <th class="p-4 font-medium text-right">{{ $t('admin.management.table.actions') }}</th>
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
                                    <div class="text-xs text-muted-foreground">{{ user.phone || $t('admin.management.noPhone') }}</div>
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
                                    <button @click="openModal('user', user)" class="text-primary hover:underline font-medium">{{ $t('admin.management.edit') }}</button>
                                    <button @click="confirmDelete(user, 'user')" class="text-destructive hover:underline font-medium">{{ $t('admin.management.delete') }}</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Shops -->
                    <table v-if="activeTab === 'shops'" class="w-full text-left text-sm">
                        <thead class="bg-muted/50 text-muted-foreground border-b border-border">
                            <tr>
                                <th class="p-4 font-medium">{{ $t('admin.management.table.id') }}</th>
                                <th class="p-4 font-medium">{{ $t('admin.management.table.shop') }}</th>
                                <th class="p-4 font-medium">{{ $t('admin.management.table.location') }}</th>
                                <th class="p-4 font-medium">{{ $t('admin.management.table.seller') }}</th>
                                <th class="p-4 font-medium text-right">{{ $t('admin.management.table.actions') }}</th>
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
                                <td class="p-4 text-muted-foreground">{{ shop.user?.name || $t('admin.management.notAvailable') }}</td>
                                <td class="p-4 text-right space-x-2">
                                    <button @click="openModal('shop', shop)" class="text-primary hover:underline font-medium">{{ $t('admin.management.edit') }}</button>
                                    <button @click="confirmDelete(shop, 'shop')" class="text-destructive hover:underline font-medium">{{ $t('admin.management.delete') }}</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Products -->
                    <table v-if="activeTab === 'products'" class="w-full text-left text-sm">
                        <thead class="bg-muted/50 text-muted-foreground border-b border-border">
                            <tr>
                                <th class="p-4 font-medium">{{ $t('admin.management.table.id') }}</th>
                                <th class="p-4 font-medium">{{ $t('admin.management.table.product') }}</th>
                                <th class="p-4 font-medium">{{ $t('admin.management.table.price') }}</th>
                                <th class="p-4 font-medium">{{ $t('admin.management.table.stock') }}</th>
                                <th class="p-4 font-medium">{{ $t('admin.management.table.categoryShop') }}</th>
                                <th class="p-4 font-medium text-right">{{ $t('admin.management.table.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <tr v-for="product in props.products.data" :key="product.id" class="hover:bg-muted/30 transition-colors">
                                <td class="p-4 font-mono text-xs">{{ product.id }}</td>
                                <td class="p-4">
                                    <div class="font-medium text-foreground">{{ product.name }}</div>
                                    <div class="text-xs text-muted-foreground">{{ $t('admin.management.code') }}: {{ product.code || $t('admin.management.notAvailable') }}</div>
                                </td>
                                <td class="p-4">
                                    <div class="font-bold text-foreground">{{ product.price }} FCFA</div>
                                    <div v-if="product.promotion_price" class="text-xs text-green-600">{{ $t('admin.management.promo') }}: {{ product.promotion_price }}</div>
                                </td>
                                <td class="p-4">
                                    <span :class="[
                                        'px-2 py-0.5 rounded text-xs font-medium',
                                        product.in_stock ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'
                                    ]">
                                        {{ product.in_stock ? $t('admin.management.inStock') : $t('admin.management.outOfStock') }}
                                    </span>
                                    <div class="text-xs text-muted-foreground mt-1">{{ $t('admin.management.qty') }}: {{ product.quantity }}</div>
                                </td>
                                <td class="p-4">
                                    <div class="text-foreground">{{ product.category?.name || $t('admin.management.noCategory') }}</div>
                                    <div class="text-xs text-muted-foreground">{{ product.shop?.name || $t('admin.management.noShop') }}</div>
                                </td>
                                <td class="p-4 text-right space-x-2">
                                    <button @click="openModal('product', product)" class="text-primary hover:underline font-medium">{{ $t('admin.management.edit') }}</button>
                                    <button @click="confirmDelete(product, 'product')" class="text-destructive hover:underline font-medium">{{ $t('admin.management.delete') }}</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Categories -->
                    <table v-if="activeTab === 'categories'" class="w-full text-left text-sm">
                        <thead class="bg-muted/50 text-muted-foreground border-b border-border">
                            <tr>
                                <th class="p-4 font-medium">{{ $t('admin.management.table.id') }}</th>
                                <th class="p-4 font-medium">{{ $t('admin.management.table.name') }}</th>
                                <th class="p-4 font-medium">{{ $t('admin.management.table.slug') }}</th>
                                <th class="p-4 font-medium">{{ $t('admin.management.table.description') }}</th>
                                <th class="p-4 font-medium text-right">{{ $t('admin.management.table.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <tr v-for="category in props.categories.data" :key="category.id" class="hover:bg-muted/30 transition-colors">
                                <td class="p-4 font-mono text-xs">{{ category.id }}</td>
                                <td class="p-4 font-medium text-foreground">{{ category.name }}</td>
                                <td class="p-4 text-muted-foreground">{{ category.slug }}</td>
                                <td class="p-4 text-muted-foreground truncate max-w-xs">{{ category.description }}</td>
                                <td class="p-4 text-right space-x-2">
                                    <button @click="openModal('category', category)" class="text-primary hover:underline font-medium">{{ $t('admin.management.edit') }}</button>
                                    <button @click="confirmDelete(category, 'category')" class="text-destructive hover:underline font-medium">{{ $t('admin.management.delete') }}</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Orders -->
                    <table v-if="activeTab === 'orders'" class="w-full text-left text-sm">
                        <thead class="bg-muted/50 text-muted-foreground border-b border-border">
                            <tr>
                                <th class="p-4 font-medium">{{ $t('admin.management.table.id') }}</th>
                                <th class="p-4 font-medium">{{ $t('admin.management.table.number') }}</th>
                                <th class="p-4 font-medium">{{ $t('admin.management.table.client') }}</th>
                                <th class="p-4 font-medium">{{ $t('admin.management.table.items') }}</th>
                                <th class="p-4 font-medium">{{ $t('admin.management.table.total') }}</th>
                                <th class="p-4 font-medium">{{ $t('admin.management.table.status') }}</th>
                                <th class="p-4 font-medium text-right">{{ $t('admin.management.table.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <tr v-for="order in props.orders.data" :key="order.id" class="hover:bg-muted/30 transition-colors">
                                <td class="p-4 font-mono text-xs">{{ order.id }}</td>
                                <td class="p-4 font-bold text-foreground">{{ order.order_number }}</td>
                                <td class="p-4 text-muted-foreground">{{ order.user?.name || $t('admin.management.unknownClient') }}</td>
                                <td class="p-4 text-foreground">{{ order.total_products }}</td>
                                <td class="p-4 font-bold text-primary">{{ order.total_price }} FCFA</td>
                                <td class="p-4">
                                    <span :class="['inline-flex rounded-full border px-2.5 py-1 text-xs font-medium', orderStatusClass(order.status)]">
                                        {{ orderStatusLabel(order.status) }}
                                    </span>
                                </td>
                                <td class="p-4 text-right">
                                    <button @click="confirmDelete(order, 'order')" class="text-destructive hover:underline font-medium">{{ $t('admin.management.cancelDelete') }}</button>
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
                    <h2 class="text-lg font-medium text-foreground mb-4">{{ isEditing.user ? $t('admin.management.editUserTitle') : $t('admin.management.newUserTitle') }}</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="name" :value="$t('admin.management.fullName')" />
                            <TextInput id="name" type="text" class="mt-1 block w-full" v-model="userForm.name" required />
                            <InputError :message="userForm.errors.name" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="username" :value="$t('admin.management.username')" />
                            <TextInput id="username" type="text" class="mt-1 block w-full" v-model="userForm.username" required />
                            <InputError :message="userForm.errors.username" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="email" :value="$t('admin.management.email')" />
                            <TextInput id="email" type="email" class="mt-1 block w-full" v-model="userForm.email" required />
                            <InputError :message="userForm.errors.email" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="phone" :value="$t('admin.management.phone')" />
                            <TextInput id="phone" type="text" class="mt-1 block w-full" v-model="userForm.phone" />
                            <InputError :message="userForm.errors.phone" class="mt-2" />
                        </div>
                        <div class="md:col-span-2">
                            <InputLabel for="address" :value="$t('admin.management.address')" />
                            <TextInput id="address" type="text" class="mt-1 block w-full" v-model="userForm.address" />
                            <InputError :message="userForm.errors.address" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="role" :value="$t('admin.management.table.role')" />
                            <select id="role" v-model="userForm.role" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-primary focus:ring-primary rounded-md shadow-sm">
                                <option value="user">{{ $t('admin.management.roleUser') }}</option>
                                <option value="supplier">{{ $t('admin.management.roleSupplier') }}</option>
                                <option value="admin">{{ $t('admin.management.roleAdmin') }}</option>
                            </select>
                            <InputError :message="userForm.errors.role" class="mt-2" />
                        </div>
                        <div class="flex items-center pt-6">
                            <label class="flex items-center">
                                <input type="checkbox" v-model="userForm.email_verified" class="rounded border-gray-300 text-primary shadow-sm focus:ring-primary" />
                                <span class="ms-2 text-sm text-muted-foreground">{{ $t('admin.management.emailVerified') }}</span>
                            </label>
                        </div>
                        <template v-if="!isEditing.user">
                            <div>
                                <InputLabel for="password" :value="$t('admin.management.password')" />
                                <TextInput id="password" type="password" class="mt-1 block w-full" v-model="userForm.password" required />
                            </div>
                            <div>
                                <InputLabel for="password_confirmation" :value="$t('admin.management.passwordConfirmation')" />
                                <TextInput id="password_confirmation" type="password" class="mt-1 block w-full" v-model="userForm.password_confirmation" required />
                            </div>
                        </template>
                    </div>
                    <div class="mt-6 flex justify-end space-x-3">
                        <SecondaryButton @click="modalOpen.user = false">{{ $t('admin.management.cancel') }}</SecondaryButton>
                        <PrimaryButton :disabled="userForm.processing">{{ $t('admin.management.save') }}</PrimaryButton>
                    </div>
                </form>
            </Modal>

            <!-- Shop Modal -->
            <Modal :show="modalOpen.shop" @close="modalOpen.shop = false">
                <form @submit.prevent="submitForm('shop')" class="p-6">
                    <h2 class="text-lg font-medium text-foreground mb-4">{{ isEditing.shop ? $t('admin.management.editShopTitle') : $t('admin.management.newShopTitle') }}</h2>
                    <div class="space-y-4">
                        <div>
                            <InputLabel for="shop_name" :value="$t('admin.management.shopName')" />
                            <TextInput id="shop_name" type="text" class="mt-1 block w-full" v-model="shopForm.name" required />
                            <InputError :message="shopForm.errors.name" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="shop_desc" :value="$t('admin.management.table.description')" />
                            <textarea id="shop_desc" v-model="shopForm.description" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-primary focus:ring-primary rounded-md shadow-sm" rows="3"></textarea>
                            <InputError :message="shopForm.errors.description" class="mt-2" />
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <InputLabel for="shop_city" :value="$t('admin.management.city')" />
                                <TextInput id="shop_city" type="text" class="mt-1 block w-full" v-model="shopForm.city" />
                            </div>
                            <div>
                                <InputLabel for="shop_district" :value="$t('admin.management.district')" />
                                <TextInput id="shop_district" type="text" class="mt-1 block w-full" v-model="shopForm.district" />
                            </div>
                        </div>
                        <div>
                            <InputLabel for="shop_phone" :value="$t('admin.management.shopPhone')" />
                            <div class="flex mt-1">
                                <select
                                    v-model="shopSelectedCode"
                                    class="rounded-l-md border border-input bg-background text-foreground shadow-sm focus:border-ring focus:ring-ring border-r-0"
                                >
                                    <option v-for="country in countries" :key="country.code" :value="country.code">
                                        {{ country.code }} ({{ country.name }})
                                    </option>
                                </select>
                                <TextInput
                                    id="shop_phone"
                                    type="text"
                                    class="block w-full rounded-l-none"
                                    v-model="shopPhoneNumber"
                                    :placeholder="$t('admin.management.phonePlaceholder')"
                                />
                            </div>
                            <InputError :message="shopForm.errors.phone" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="shop_user" :value="$t('admin.management.sellerOwner')" />
                            <select id="shop_user" v-model="shopForm.user_id" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-primary focus:ring-primary rounded-md shadow-sm">
                                <option value="">{{ $t('admin.management.selectSeller') }}</option>
                                <option v-for="user in all_users" :key="user.id" :value="user.id">{{ user.name }}</option>
                            </select>
                            <InputError :message="shopForm.errors.user_id" class="mt-2" />
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end space-x-3">
                        <SecondaryButton @click="modalOpen.shop = false">{{ $t('admin.management.cancel') }}</SecondaryButton>
                        <PrimaryButton :disabled="shopForm.processing">{{ $t('admin.management.save') }}</PrimaryButton>
                    </div>
                </form>
            </Modal>

            <!-- Product Modal -->
            <Modal :show="modalOpen.product" @close="modalOpen.product = false">
                <form @submit.prevent="submitForm('product')" class="p-6">
                    <h2 class="text-lg font-medium text-foreground mb-4">{{ isEditing.product ? $t('admin.management.editProductTitle') : $t('admin.management.newProductTitle') }}</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <InputLabel for="prod_name" :value="$t('admin.management.productName')" />
                            <TextInput id="prod_name" type="text" class="mt-1 block w-full" v-model="productForm.name" required />
                            <InputError :message="productForm.errors.name" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="prod_code" :value="$t('admin.management.skuCode')" />
                            <TextInput id="prod_code" type="text" class="mt-1 block w-full" v-model="productForm.code" />
                        </div>
                        <div>
                            <InputLabel for="prod_price" :value="$t('admin.management.priceFcfa')" />
                            <TextInput id="prod_price" type="number" class="mt-1 block w-full" v-model="productForm.price" required />
                        </div>
                        <div>
                            <InputLabel for="prod_promo" :value="$t('admin.management.promoPrice')" />
                            <TextInput id="prod_promo" type="number" class="mt-1 block w-full" v-model="productForm.promotion_price" />
                        </div>
                        <div>
                            <InputLabel for="prod_qty" :value="$t('admin.management.quantityStock')" />
                            <TextInput id="prod_qty" type="number" class="mt-1 block w-full" v-model="productForm.quantity" />
                        </div>
                        <div>
                            <InputLabel for="prod_cat" :value="$t('admin.management.category')" />
                            <select id="prod_cat" v-model="productForm.category_id" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-primary focus:ring-primary rounded-md shadow-sm">
                                <option value="">{{ $t('admin.management.select') }}</option>
                                <option v-for="cat in all_categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                            </select>
                        </div>
                        <div>
                            <InputLabel for="prod_shop" :value="$t('admin.management.table.shop')" />
                            <select id="prod_shop" v-model="productForm.shop_id" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-primary focus:ring-primary rounded-md shadow-sm">
                                <option value="">{{ $t('admin.management.select') }}</option>
                                <option v-for="shop in all_shops" :key="shop.id" :value="shop.id">{{ shop.name }}</option>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <InputLabel :value="$t('admin.management.shortDescription')" />
                            <textarea v-model="productForm.description" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-primary focus:ring-primary rounded-md shadow-sm" rows="2"></textarea>
                        </div>
                        <div class="flex items-center">
                            <label class="flex items-center">
                                <input type="checkbox" v-model="productForm.in_stock" class="rounded border-gray-300 text-primary shadow-sm focus:ring-primary" />
                                <span class="ms-2 text-sm text-muted-foreground">{{ $t('admin.management.inStockCheckbox') }}</span>
                            </label>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end space-x-3">
                        <SecondaryButton @click="modalOpen.product = false">{{ $t('admin.management.cancel') }}</SecondaryButton>
                        <PrimaryButton :disabled="productForm.processing">{{ $t('admin.management.save') }}</PrimaryButton>
                    </div>
                </form>
            </Modal>

            <!-- Category Modal -->
            <Modal :show="modalOpen.category" @close="modalOpen.category = false">
                <form @submit.prevent="submitForm('category')" class="p-6">
                    <h2 class="text-lg font-medium text-foreground mb-4">{{ isEditing.category ? $t('admin.management.editCategoryTitle') : $t('admin.management.newCategoryTitle') }}</h2>
                    <div class="space-y-4">
                        <div>
                            <InputLabel for="cat_name" :value="$t('admin.management.table.name')" />
                            <TextInput id="cat_name" type="text" class="mt-1 block w-full" v-model="categoryForm.name" required />
                            <InputError :message="categoryForm.errors.name" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="cat_slug" :value="$t('admin.management.slugUrl')" />
                            <TextInput id="cat_slug" type="text" class="mt-1 block w-full" v-model="categoryForm.slug" :placeholder="$t('admin.management.slugPlaceholder')" />
                        </div>
                        <div>
                            <InputLabel for="cat_desc" :value="$t('admin.management.table.description')" />
                            <textarea id="cat_desc" v-model="categoryForm.description" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-primary focus:ring-primary rounded-md shadow-sm" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end space-x-3">
                        <SecondaryButton @click="modalOpen.category = false">{{ $t('admin.management.cancel') }}</SecondaryButton>
                        <PrimaryButton :disabled="categoryForm.processing">{{ $t('admin.management.save') }}</PrimaryButton>
                    </div>
                </form>
            </Modal>

            <!-- Delete Confirmation -->
            <DeleteConfirmationModal
                :show="deleteModalOpen"
                @close="deleteModalOpen = false"
                @confirm="executeDelete"
                :title="$t('admin.management.deleteConfirmTitle')"
                :message="$t('admin.management.deleteConfirmMessage', { type: deleteType })"
            />

        </template>
    </AdminLayout>
</template>
