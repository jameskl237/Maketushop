<script setup>
import { Button } from '@/components/ui/button';
import { Copy } from 'lucide-vue-next';
import { computed, onBeforeUnmount, ref } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps({
    shopId: {
        type: [Number, String],
        required: true,
    },
    shopName: {
        type: String,
        default: '',
    },
    iconOnly: {
        type: Boolean,
        default: true,
    },
});

const { t } = useI18n();
const copied = ref(false);
let copiedTimeout = null;
const productionBaseUrl = (import.meta.env.VITE_PUBLIC_APP_URL || 'https://maketushop.com').replace(/\/+$/, '');

const slugify = (value) =>
    String(value || '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/(^-|-$)/g, '');

const shopPublicUrl = computed(() =>
    `${productionBaseUrl}${route('shops.show', { shop: props.shopId, slug: slugify(props.shopName) || 'boutique' }, false)}`,
);

const copyShopLink = async () => {
    try {
        if (navigator?.clipboard?.writeText) {
            await navigator.clipboard.writeText(shopPublicUrl.value);
        } else {
            const input = document.createElement('textarea');
            input.value = shopPublicUrl.value;
            input.setAttribute('readonly', '');
            input.style.position = 'absolute';
            input.style.left = '-9999px';
            document.body.appendChild(input);
            input.select();
            document.execCommand('copy');
            document.body.removeChild(input);
        }
        copied.value = true;
        if (copiedTimeout) clearTimeout(copiedTimeout);
        copiedTimeout = setTimeout(() => {
            copied.value = false;
        }, 1800);
    } catch (error) {
        copied.value = false;
    }
};

onBeforeUnmount(() => {
    if (copiedTimeout) clearTimeout(copiedTimeout);
});
</script>

<template>
    <div class="relative inline-flex items-center">
        <Button
            type="button"
            variant="ghost"
            :size="iconOnly ? 'icon' : 'sm'"
            :class="iconOnly ? 'h-8 w-8 shrink-0' : 'gap-2'"
            :aria-label="copied ? t('public.copied') : t('supplier.copyPublicShopLink')"
            :title="copied ? t('public.copied') : t('supplier.copyPublicShopLink')"
            @click="copyShopLink"
        >
            <Copy class="h-4 w-4" />
            <span v-if="!iconOnly">{{ copied ? t('public.copied') : t('supplier.copyPublicShopLink') }}</span>
        </Button>
        <span
            v-if="copied"
            class="absolute -top-8 left-1/2 -translate-x-1/2 whitespace-nowrap rounded-md border border-border bg-background px-2 py-0.5 text-[11px] font-medium text-foreground shadow-sm"
            role="status"
            aria-live="polite"
        >
            {{ t('public.linkCopied') }}
        </span>
    </div>
</template>


