<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import ProductsNavbar from '@/components/products/layout/ProductsNavbar.vue';
import PriceDisplay from '@/components/products/shared/PriceDisplay.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useCartStore } from '@/stores/cart';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ArrowLeft, Check, CreditCard, Lock, MapPin, User, Phone, ArrowRight } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps({
    context: {
        type: String,
        required: true,
    },
    product: {
        type: Object,
        default: null,
    },
    methods: {
        type: Array,
        default: () => [],
    },
});

const { t } = useI18n();
const cartStore = useCartStore();
const page = usePage();
const authUser = computed(() => page.props.auth.user);

const currentStep = ref(1);
const selectedMethod = ref(props.methods[0] || null);

const form = ref({
    first_name: authUser.value?.name?.split(' ')[0] || '',
    last_name: authUser.value?.name?.split(' ').slice(1).join(' ') || '',
    delivery_address: '',
    phone_number: authUser.value?.phone || '',
});

const formatPrice = (value) =>
    new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'XAF',
        maximumFractionDigits: 0,
    }).format(value);

const isCart = computed(() => props.context === 'cart');
const cartItems = computed(() => cartStore.items);
const cartTotal = computed(() => cartStore.totalAmount);
const cartItemsCount = computed(() => cartStore.itemsCount);

const isFormValid = computed(() => {
    return form.value.first_name && form.value.last_name && form.value.delivery_address && form.value.phone_number;
});

const canSubmit = computed(() => {
    if (currentStep.value === 1) return isFormValid.value;
    if (!selectedMethod.value) return false;
    return !isCart.value || cartItems.value.length > 0;
});

const nextStep = () => {
    if (currentStep.value === 1 && isFormValid.value) {
        currentStep.value = 2;
    }
};

const prevStep = () => {
    if (currentStep.value === 2) {
        currentStep.value = 1;
    }
};

const submitPayment = () => {
    if (!canSubmit.value || currentStep.value !== 2) return;

    const payload = {
        payment_channel: selectedMethod.value.channel,
        ...form.value
    };

    if (isCart.value) {
        payload.items = cartItems.value.map((item) => ({
            id: item.id,
            quantity: item.quantity,
        }));

        router.post(route('payments.cart.checkout'), payload);
        return;
    }

    router.post(route('payments.checkout', { product: props.product.id }), payload);
};
</script>

<template>
    <Head :title="t('payments.methodTitle')" />

    <div class="min-h-screen bg-background">
        <ProductsNavbar />

        <main class="mx-auto max-w-5xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between">
                <Link
                    v-if="currentStep === 1"
                    :href="isCart ? route('cart.index') : route('products.buy', { product: product.id })"
                    class="inline-flex items-center gap-2 text-sm text-muted-foreground underline-offset-4 hover:text-foreground hover:underline"
                >
                    <ArrowLeft class="h-4 w-4" />
                    {{ t('payments.back') }}
                </Link>
                <button
                    v-else
                    type="button"
                    @click="prevStep"
                    class="inline-flex items-center gap-2 text-sm text-muted-foreground underline-offset-4 hover:text-foreground hover:underline"
                >
                    <ArrowLeft class="h-4 w-4" />
                    {{ t('payments.previous') }}
                </button>

                <!-- Steps Indicator -->
                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-2">
                        <span :class="['flex h-6 w-6 items-center justify-center rounded-full text-xs font-bold', currentStep >= 1 ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground']">1</span>
                        <span :class="['text-xs font-medium hidden sm:inline', currentStep >= 1 ? 'text-foreground' : 'text-muted-foreground']">{{ t('payments.step1') }}</span>
                    </div>
                    <div class="h-px w-8 bg-muted"></div>
                    <div class="flex items-center gap-2">
                        <span :class="['flex h-6 w-6 items-center justify-center rounded-full text-xs font-bold', currentStep === 2 ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground']">2</span>
                        <span :class="['text-xs font-medium hidden sm:inline', currentStep === 2 ? 'text-foreground' : 'text-muted-foreground']">{{ t('payments.step2') }}</span>
                    </div>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-[1fr_340px]">
                <section class="space-y-6">
                    <!-- Step 1: Delivery Information -->
                    <div v-if="currentStep === 1" class="space-y-4">
                        <div>
                            <h1 class="text-2xl font-bold">{{ t('payments.deliveryInfo') }}</h1>
                            <p class="mt-1 text-sm text-muted-foreground">{{ t('auth.supplierRegisterHelp') }}</p>
                        </div>

                        <Card>
                            <CardContent class="pt-6">
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div class="space-y-2">
                                        <InputLabel for="first_name" :value="t('payments.firstName')" />
                                        <div class="relative">
                                            <User class="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                                            <TextInput
                                                id="first_name"
                                                v-model="form.first_name"
                                                type="text"
                                                class="block w-full pl-10"
                                                required
                                                autocomplete="given-name"
                                            />
                                        </div>
                                    </div>

                                    <div class="space-y-2">
                                        <InputLabel for="last_name" :value="t('payments.lastName')" />
                                        <div class="relative">
                                            <User class="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                                            <TextInput
                                                id="last_name"
                                                v-model="form.last_name"
                                                type="text"
                                                class="block w-full pl-10"
                                                required
                                                autocomplete="family-name"
                                            />
                                        </div>
                                    </div>

                                    <div class="space-y-2 sm:col-span-2">
                                        <InputLabel for="delivery_address" :value="t('payments.deliveryAddress')" />
                                        <div class="relative">
                                            <MapPin class="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                                            <TextInput
                                                id="delivery_address"
                                                v-model="form.delivery_address"
                                                type="text"
                                                class="block w-full pl-10"
                                                required
                                                :placeholder="t('auth.exampleAddress')"
                                            />
                                        </div>
                                    </div>

                                    <div class="space-y-2 sm:col-span-2">
                                        <InputLabel for="phone_number" :value="t('payments.phoneNumber')" />
                                        <div class="relative">
                                            <Phone class="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                                            <TextInput
                                                id="phone_number"
                                                v-model="form.phone_number"
                                                type="tel"
                                                class="block w-full pl-10"
                                                required
                                                placeholder="6XXXXXXXX"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <div class="flex justify-end">
                            <Button size="lg" :disabled="!isFormValid" @click="nextStep">
                                {{ t('payments.next') }}
                                <ArrowRight class="ml-2 h-4 w-4" />
                            </Button>
                        </div>
                    </div>

                    <!-- Step 2: Payment Method -->
                    <div v-if="currentStep === 2" class="space-y-4">
                        <div>
                            <h1 class="text-2xl font-bold">{{ t('payments.methodTitle') }}</h1>
                            <p class="mt-1 text-sm text-muted-foreground">{{ t('payments.methodSubtitle') }}</p>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <button
                                v-for="method in methods"
                                :key="method.key"
                                type="button"
                                :class="[
                                    'relative rounded-xl border bg-card p-5 text-left transition-all hover:border-primary/60 hover:shadow-md',
                                    selectedMethod?.key === method.key ? 'border-primary ring-2 ring-primary/10 shadow-sm' : 'border-border',
                                ]"
                                @click="selectedMethod = method"
                            >
                                <span
                                    v-if="selectedMethod?.key === method.key"
                                    class="absolute right-4 top-4 inline-flex h-6 w-6 items-center justify-center rounded-full bg-primary text-primary-foreground"
                                >
                                    <Check class="h-4 w-4" />
                                </span>
                                <div class="flex h-16 w-full items-center justify-center rounded-lg bg-muted/30 p-2">
                                    <img :src="method.image" :alt="method.name" class="h-full max-w-full object-contain" />
                                </div>
                                <span class="mt-4 block text-lg font-bold">{{ method.name }}</span>
                                <span class="mt-1 block text-sm text-muted-foreground">{{ method.description }}</span>
                            </button>
                        </div>

                        <div class="flex justify-between pt-4">
                            <Button variant="outline" @click="prevStep">
                                {{ t('payments.previous') }}
                            </Button>
                        </div>
                    </div>
                </section>

                <aside class="space-y-6">
                    <Card class="sticky top-6">
                        <CardHeader class="pb-3">
                            <CardTitle class="flex items-center gap-2 text-base">
                                <CreditCard class="h-4 w-4 text-primary" />
                                {{ t('payments.summary') }}
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div v-if="isCart" class="space-y-3">
                                <div v-if="!cartItems.length" class="rounded-md border border-dashed border-border p-4 text-sm text-muted-foreground">
                                    {{ t('cartPage.empty') }}
                                </div>
                                <div v-else class="space-y-3">
                                    <div
                                        v-for="item in cartItems"
                                        :key="item.id"
                                        class="flex items-start justify-between gap-3 text-sm"
                                    >
                                        <span class="line-clamp-2 leading-relaxed">{{ item.name }} <span class="text-muted-foreground">x{{ item.quantity }}</span></span>
                                        <span class="font-semibold">{{ formatPrice((Number(item.price) || 0) * item.quantity) }}</span>
                                    </div>
                                </div>
                                <div class="border-t border-border pt-4">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-muted-foreground">{{ t('dashboard.client.items', { count: cartItemsCount }) }}</span>
                                        <span class="text-xl font-black text-primary">{{ formatPrice(cartTotal) }}</span>
                                    </div>
                                </div>
                            </div>

                            <div v-else class="space-y-4">
                                <div class="flex items-start gap-3">
                                    <img :src="product.image || '/images/Maketu1.png'" alt="Produit" class="h-20 w-20 rounded-lg object-cover shadow-sm" />
                                    <div class="flex-1 min-w-0">
                                        <p class="font-bold truncate">{{ product.name }}</p>
                                        <p class="text-sm text-muted-foreground truncate">{{ product.shop_name }}</p>
                                        <div class="mt-1 font-bold text-primary">
                                            {{ formatPrice(product.price) }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- User Info Summary in Sidebar when at Step 2 -->
                            <div v-if="currentStep === 2" class="rounded-lg bg-muted/50 p-3 space-y-2 text-xs">
                                <div class="flex items-center gap-2 text-muted-foreground font-semibold uppercase tracking-wider">
                                    <MapPin class="h-3 w-3" />
                                    {{ t('payments.deliveryInfo') }}
                                </div>
                                <p class="font-medium">{{ form.first_name }} {{ form.last_name }}</p>
                                <p class="text-muted-foreground">{{ form.delivery_address }}</p>
                                <p class="text-muted-foreground">{{ form.phone_number }}</p>
                            </div>

                            <Button 
                                class="w-full h-12 text-base font-bold shadow-lg shadow-primary/20 transition-all hover:shadow-primary/30 active:scale-[0.98]" 
                                :disabled="!canSubmit || (currentStep === 2 && !selectedMethod)" 
                                @click="currentStep === 1 ? nextStep() : submitPayment()"
                            >
                                <template v-if="currentStep === 1">
                                    {{ t('payments.next') }}
                                    <ArrowRight class="ml-2 h-4 w-4" />
                                </template>
                                <template v-else>
                                    <Lock class="mr-2 h-4 w-4" />
                                    {{ t('payments.continueToNotchPay') }}
                                </template>
                            </Button>

                            <p class="text-[10px] text-center text-muted-foreground px-2">
                                En cliquant sur continuer, vous acceptez nos conditions générales de vente et notre politique de confidentialité.
                            </p>
                        </CardContent>
                    </Card>

                    <!-- Trust badges -->
                    <div class="flex items-center justify-center gap-4 text-muted-foreground opacity-50 grayscale hover:grayscale-0 transition-all">
                         <img src="/images/payments/mtn-momo.svg" class="h-6" alt="MTN" />
                         <img src="/images/payments/orange-money.svg" class="h-6" alt="Orange" />
                    </div>
                </aside>
            </div>
        </main>
    </div>
</template>
