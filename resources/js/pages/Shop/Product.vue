<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import ShopLayout from '@/layouts/ShopLayout.vue';
import { cn } from '@/lib/utils';
import { webFetch } from '@/lib/web-fetch';
import { Head, Link } from '@inertiajs/vue3';
import { LoaderCircle, Minus, Plus, ShoppingBag } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';

type ProductImage = {
    id: number;
    image_path: string;
    is_primary: boolean;
};

type Product = {
    id: number;
    name: string;
    category: string | null;
    slug: string;
    description: string | null;
    price: number | string;
    stock: number;
    images: ProductImage[];
};

const props = defineProps<{
    slug: string;
}>();

const product = ref<Product | null>(null);
const loading = ref(true);
const pageError = ref('');
const quantity = ref(1);
const adding = ref(false);
const toast = ref<{ type: 'success' | 'error'; message: string } | null>(null);
const activeImageIndex = ref(0);

const currency = new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
});

const imageUrl = (path?: string | null) => {
    if (!path) return 'https://placehold.co/800x800/f4efe6/7a4b2d?text=No+Image';
    return `/storage/${path}`;
};

const displayPrice = computed(() => {
    if (!product.value) return '';
    return currency.format(Number(product.value.price));
});

const galleryImages = computed(() => product.value?.images ?? []);

const activeImage = computed(() => {
    const images = galleryImages.value;
    if (!images.length) return null;
    return images[activeImageIndex.value] ?? images[0];
});

const loadProduct = async () => {
    loading.value = true;
    pageError.value = '';

    try {
        const response = await webFetch(`/api/products/${props.slug}`);
        if (!response.ok) {
            throw new Error('Product not found or unavailable.');
        }

        product.value = (await response.json()) as Product;
    } catch (error) {
        pageError.value = error instanceof Error ? error.message : 'Unable to load product.';
    } finally {
        loading.value = false;
    }
};

const showToast = (type: 'success' | 'error', message: string) => {
    toast.value = { type, message };
    window.setTimeout(() => {
        toast.value = null;
    }, 2800);
};

watch(
    () => product.value?.id,
    () => {
        if (!product.value?.images?.length) {
            activeImageIndex.value = 0;
            return;
        }

        const primaryIndex = product.value.images.findIndex((image) => image.is_primary);
        activeImageIndex.value = primaryIndex >= 0 ? primaryIndex : 0;
    },
);

const addToCart = async () => {
    if (!product.value) return;

    adding.value = true;
    try {
        const response = await webFetch('/api/cart/add', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                product_id: product.value.id,
                quantity: quantity.value,
            }),
        });

        const data = await response.json();
        if (!response.ok) {
            showToast('error', data.message ?? 'Unable to add to cart.');
            return;
        }

        showToast('success', `${product.value.name} added to cart.`);
    } catch (error) {
        showToast('error', error instanceof Error ? error.message : 'Unable to add to cart.');
    } finally {
        adding.value = false;
    }
};

onMounted(() => {
    void loadProduct();
});
</script>

<template>
    <Head :title="product ? product.name : 'Product'" />

    <ShopLayout>
        <div v-if="loading" class="grid gap-6 lg:grid-cols-2">
            <div class="h-[460px] animate-pulse rounded-2xl bg-slate-200/70" />
            <div class="h-[460px] animate-pulse rounded-2xl bg-slate-200/70" />
        </div>

        <div v-else-if="pageError" class="rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-700">
            {{ pageError }}
        </div>

        <div v-else-if="product" class="space-y-6">
            <div class="text-sm text-slate-500">
                <Link :href="route('home')" class="hover:text-slate-700">Shop</Link>
                <span class="mx-2">/</span>
                <span>{{ product.name }}</span>
            </div>

            <div class="grid gap-6 lg:grid-cols-[1fr_1fr]">
                <Card class="overflow-hidden border-slate-200">
                    <CardContent class="space-y-4 p-4">
                        <img
                            :src="imageUrl(activeImage?.image_path)"
                            :alt="product.name"
                            class="h-[480px] w-full rounded-xl object-cover"
                        />

                        <div
                            v-if="galleryImages.length > 1"
                            class="grid grid-cols-4 gap-2 sm:grid-cols-5"
                        >
                            <button
                                v-for="(image, index) in galleryImages"
                                :key="image.id"
                                type="button"
                                class="overflow-hidden rounded-lg border-2 transition"
                                :class="
                                    cn(
                                        activeImageIndex === index
                                            ? 'border-slate-900'
                                            : 'border-transparent hover:border-slate-300',
                                    )
                                "
                                @click="activeImageIndex = index"
                            >
                                <img :src="imageUrl(image.image_path)" :alt="`${product.name} image ${index + 1}`" class="h-20 w-full object-cover" />
                            </button>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-slate-200">
                    <CardContent class="space-y-5 p-6">
                        <p class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-500">{{ product.category || 'Uncategorized' }}</p>
                        <h1 class="text-3xl font-semibold text-slate-900">{{ product.name }}</h1>
                        <p class="text-sm text-slate-500">SKU {{ product.slug }}</p>
                        <p class="text-3xl font-bold text-slate-950">{{ displayPrice }}</p>

                        <div class="rounded-xl bg-slate-50 p-4">
                            <div class="flex items-center justify-between text-sm text-slate-600">
                                <span>Stock</span>
                                <span class="font-semibold text-slate-900">{{ product.stock }}</span>
                            </div>
                        </div>

                        <p class="text-sm leading-7 text-slate-600">
                            {{ product.description || 'No description available yet.' }}
                        </p>

                        <div class="grid gap-3 sm:grid-cols-[1fr_auto]">
                            <div class="flex items-center gap-2 rounded-2xl border border-slate-200 bg-slate-50 p-2">
                                <button type="button" class="rounded-xl bg-white p-2 text-slate-700 shadow-sm transition hover:bg-slate-100" @click="quantity = Math.max(1, quantity - 1)">
                                    <Minus class="h-4 w-4" />
                                </button>
                                <Input v-model="quantity" type="number" min="1" :max="product.stock" class="border-0 bg-transparent text-center text-base" />
                                <button type="button" class="rounded-xl bg-white p-2 text-slate-700 shadow-sm transition hover:bg-slate-100" @click="quantity = quantity + 1">
                                    <Plus class="h-4 w-4" />
                                </button>
                            </div>

                            <Button class="h-full rounded-2xl bg-slate-950 px-6 text-white hover:bg-slate-800" :disabled="adding || product.stock < 1" @click="addToCart">
                                <LoaderCircle v-if="adding" class="mr-2 h-4 w-4 animate-spin" />
                                <ShoppingBag class="mr-2 h-4 w-4" />
                                {{ adding ? 'Adding' : 'Add to cart' }}
                            </Button>
                        </div>

                        <div class="flex gap-2">
                            <Link :href="route('shop.cart')"><Button variant="outline" class="rounded-xl border-slate-300 bg-white">Go to cart</Button></Link>
                            <Link :href="route('shop.checkout')"><Button class="rounded-xl bg-amber-400 text-slate-950 hover:bg-amber-300">Checkout</Button></Link>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>

        <div v-if="toast" class="pointer-events-none fixed right-4 top-20 z-50">
            <div
                class="pointer-events-auto min-w-64 rounded-xl border px-4 py-3 text-sm shadow-lg"
                :class="toast.type === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-red-200 bg-red-50 text-red-700'"
            >
                {{ toast.message }}
            </div>
        </div>
    </ShopLayout>
</template>

