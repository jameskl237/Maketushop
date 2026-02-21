<script setup>
import ProductsNavbar from '@/components/products/layout/ProductsNavbar.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Head, router } from '@inertiajs/vue3';
import { AlertCircle } from 'lucide-vue-next';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const goBack = () => {
    if (window.history.length > 1) {
        window.history.back();
        return;
    }
    router.visit(route('products.index'));
};
</script>

<template>
    <Head :title="t('payments.headTitle')" />

    <div>
        <ProductsNavbar />
        <div class="mx-auto flex min-h-[70vh] max-w-3xl items-center px-4 py-10 sm:px-6 lg:px-8">
            <Card class="w-full">
                <CardHeader class="text-center">
                    <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-destructive/10">
                        <AlertCircle class="h-6 w-6 text-destructive" />
                    </div>
                    <CardTitle>{{ t('payments.title') }}</CardTitle>
                </CardHeader>
                <CardContent class="space-y-4 text-center">
                    <p class="text-sm text-muted-foreground">
                        {{ t('payments.text1') }}
                    </p>
                    <p class="text-sm text-muted-foreground">
                        {{ t('payments.text2') }}
                    </p>
                    <Button class="w-full sm:w-auto" @click="goBack">
                        {{ t('payments.back') }}
                    </Button>
                </CardContent>
            </Card>
        </div>
    </div>
</template>

