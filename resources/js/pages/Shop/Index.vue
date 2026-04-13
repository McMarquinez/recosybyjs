<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import ShopLayout from '@/layouts/ShopLayout.vue';
import { webFetch } from '@/lib/web-fetch';
import { Head, Link } from '@inertiajs/vue3';
import { Minus, Plus, ShoppingBag, ShoppingCart, Store } from 'lucide-vue-next';
import { computed, onMounted, reactive, ref } from 'vue';

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

type CartItem = {
    product_id: number;
    name: string;
    price: number;
    quantity: number;
    line_total: number;
};

type CartSummary = {
    items: CartItem[];
    total: number;
};

type Banner = {
    id: number;
    title: string;
    subtitle: string | null;
    image_path: string | null;
    cta_text: string | null;
    cta_url: string | null;
};

const products = ref<Product[]>([]);
const cart = ref<CartSummary>({ items: [], total: 0 });
const banners = ref<Banner[]>([]);
const loading = ref(true);
const cartLoading = ref(false);
const pageError = ref('');
const actionError = ref('');
const successMessage = ref('');
const quantities = reactive<Record<number, number>>({});
const searchQuery = ref('');

const currency = new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
});

const featuredProducts = computed(() => products.value.slice(0, 3));
const cartCount = computed(() => cart.value.items.reduce((sum: number, item: CartItem) => sum + item.quantity, 0));

const categories = computed(() => {
    const set = new Set<string>();
    products.value.forEach((product: Product) => {
        if (product.category) {
            set.add(product.category);
        }
    });
    return Array.from(set).sort((a, b) => a.localeCompare(b)).slice(0, 10);
});

const getImageUrl = (path?: string | null) => {
    if (!path) {
        return 'https://placehold.co/800x800/f4efe6/7a4b2d?text=No+Image';
    }

    return `/storage/${path}`;
};

const getBannerImageUrl = (path?: string | null) => {
    if (!path) {
        return 'https://placehold.co/1600x900/0f172a/e2e8f0?text=Banner';
    }

    return `/storage/${path}`;
};

const displayPrice = (value: number | string) => currency.format(Number(value));

const cartQuantityFor = (productId: number) => cart.value.items.find((item: CartItem) => item.product_id === productId)?.quantity ?? 0;

const loadProducts = async () => {
    const response = await webFetch('/api/products');

    if (!response.ok) {
        throw new Error('Unable to load products right now.');
    }

    const data = (await response.json()) as Product[];
    products.value = data;

    data.forEach((product: Product) => {
        quantities[product.id] = quantities[product.id] || 1;
    });
};

const visibleProducts = computed(() => {
    const value = searchQuery.value.trim().toLowerCase();
    if (!value) return products.value;

    return products.value.filter((product: Product) => {
        const haystack = `${product.name} ${product.slug} ${product.category ?? ''} ${product.description ?? ''}`.toLowerCase();
        return haystack.includes(value);
    });
});

const loadCart = async () => {
    const response = await webFetch('/api/cart');

    if (!response.ok) {
        throw new Error('Unable to load cart right now.');
    }

    cart.value = (await response.json()) as CartSummary;
};

const loadBanners = async () => {
    const response = await webFetch('/api/banners');
    if (!response.ok) {
        return;
    }

    banners.value = (await response.json()) as Banner[];
};

const syncPage = async () => {
    loading.value = true;
    pageError.value = '';

    try {
        await Promise.all([loadProducts(), loadCart(), loadBanners()]);
    } catch (error) {
        pageError.value = error instanceof Error ? error.message : 'Something went wrong while loading the shop.';
    } finally {
        loading.value = false;
    }
};

const addToCart = async (product: Product) => {
    cartLoading.value = true;
    actionError.value = '';
    successMessage.value = '';

    try {
        const response = await webFetch('/api/cart/add', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                product_id: product.id,
                quantity: quantities[product.id] || 1,
            }),
        });

        const data = await response.json();

        if (!response.ok) {
            actionError.value = data.message ?? 'Unable to add item to cart.';
            return;
        }

        cart.value = data as CartSummary;
        successMessage.value = `${product.name} added to cart.`;
    } catch (error) {
        actionError.value = error instanceof Error ? error.message : 'Unable to add item to cart.';
    } finally {
        cartLoading.value = false;
    }
};

const changeQuantity = (productId: number, amount: number) => {
    const nextQuantity = Number(quantities[productId] || 1) + amount;
    quantities[productId] = Math.max(1, nextQuantity);
};

onMounted(() => {
    window.addEventListener('shop:search', ((event: Event) => {
        const detail = (event as CustomEvent).detail as { query?: string } | undefined;
        searchQuery.value = detail?.query ?? '';
    }) as EventListener);

    void syncPage();
});
</script>

<template>
    <Head title="Shop" />

    <ShopLayout :categories="categories" :show-hero="true">
        <div class="space-y-8">
            <section v-if="pageError" class="rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-700">
                {{ pageError }}
            </section>

            <section v-else-if="loading" class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <div v-for="index in 9" :key="index" class="h-[20rem] animate-pulse rounded-2xl bg-slate-200/70" />
            </section>

            <template v-else>
                <section class="grid gap-4 rounded-2xl bg-white p-4 shadow-sm sm:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-xl bg-slate-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-500">Cart</p>
                        <p class="mt-2 text-2xl font-semibold text-slate-900">{{ cartCount }} item{{ cartCount === 1 ? '' : 's' }}</p>
                        <p class="mt-1 text-sm text-slate-500">Total {{ displayPrice(cart.total) }}</p>
                        <div class="mt-4 flex gap-2">
                            <Link :href="route('shop.cart')" class="block">
                                <Button variant="outline" class="rounded-xl border-slate-300 bg-white">View</Button>
                            </Link>
                            <Link :href="route('shop.checkout')" class="block">
                                <Button class="rounded-xl bg-slate-950 text-white hover:bg-slate-800">Checkout</Button>
                            </Link>
                        </div>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-500">Buyer help</p>
                        <p class="mt-2 text-base font-semibold text-slate-900">Login required at checkout</p>
                        <p class="mt-1 text-sm text-slate-500">Guests can add-to-cart anytime.</p>
                        <div class="mt-4">
                            <Link :href="route('shop.orders')">
                                <Button variant="outline" class="rounded-xl border-slate-300 bg-white">Track orders</Button>
                            </Link>
                        </div>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-4 lg:col-span-2">
                        <p class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-500">Quick search</p>
                        <div class="mt-3 flex flex-col gap-2 sm:flex-row sm:items-center">
                            <Input v-model="searchQuery" class="h-10 rounded-xl border-slate-300 bg-white" placeholder="Type to filter products..." />
                            <Button variant="outline" class="h-10 rounded-xl border-slate-300 bg-white" @click="searchQuery = ''">Clear</Button>
                        </div>
                        <p class="mt-2 text-sm text-slate-500">Showing {{ visibleProducts.length }} of {{ products.length }} products</p>
                    </div>
                </section>

                <section v-if="categories.length" class="rounded-2xl bg-white p-6 shadow-sm">
                    <div class="flex items-end justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-500">Categories</p>
                            <h2 class="mt-2 text-2xl font-semibold text-slate-900">Shop by category</h2>
                        </div>
                        <p class="text-sm text-slate-500">Popular</p>
                    </div>
                    <div class="mt-5 grid gap-3 sm:grid-cols-3 lg:grid-cols-6">
                        <a
                            v-for="category in categories"
                            :key="category"
                            :href="`#category-${encodeURIComponent(category)}`"
                            class="group rounded-xl border border-slate-200 bg-white p-4 transition hover:border-slate-300 hover:shadow-sm"
                        >
                            <p class="text-sm font-semibold text-slate-900 group-hover:text-slate-950">{{ category }}</p>
                            <p class="mt-1 text-xs text-slate-500">Browse deals</p>
                        </a>
                    </div>
                </section>

                <section class="rounded-2xl bg-white p-6 shadow-sm">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-500">Featured</p>
                            <h2 class="mt-2 text-2xl font-semibold text-slate-900">Top picks for you</h2>
                        </div>
                        <p class="text-sm text-slate-500">Limited stock items first</p>
                    </div>

                    <div class="mt-5 grid gap-4 lg:grid-cols-3">
                        <Card
                            v-for="product in featuredProducts"
                            :key="`featured-${product.id}`"
                            class="overflow-hidden border-slate-200 bg-white"
                        >
                            <CardContent class="space-y-5 p-5">
                                <div class="overflow-hidden rounded-[1.5rem] bg-stone-100">
                                    <img
                                        :src="getImageUrl(product.images.find((image) => image.is_primary)?.image_path ?? product.images[0]?.image_path)"
                                        :alt="product.name"
                                        class="h-60 w-full object-cover transition duration-500 hover:scale-105"
                                    />
                                </div>

                                <div class="space-y-2">
                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <h3 class="text-xl font-semibold text-stone-900">{{ product.name }}</h3>
                                            <p class="mt-1 text-sm text-stone-500">{{ product.category || 'Uncategorized' }} • SKU {{ product.slug }}</p>
                                        </div>
                                        <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-900">
                                            {{ displayPrice(product.price) }}
                                        </span>
                                    </div>
                                    <p class="min-h-12 text-sm leading-6 text-stone-600">
                                        {{ product.description || 'No description available yet.' }}
                                    </p>
                                </div>

                                <div class="rounded-[1.25rem] bg-stone-50 p-4">
                                    <div class="flex items-center justify-between text-sm text-stone-600">
                                        <span>Available stock</span>
                                        <span class="font-semibold text-stone-900">{{ product.stock }}</span>
                                    </div>
                                    <div class="mt-2 flex items-center justify-between text-sm text-stone-600">
                                        <span>Already in cart</span>
                                        <span class="font-semibold text-stone-900">{{ cartQuantityFor(product.id) }}</span>
                                    </div>
                                </div>

                                <div class="grid gap-3 sm:grid-cols-[1fr_auto]">
                                    <div class="flex items-center gap-2 rounded-2xl border border-stone-200 bg-stone-50 p-2">
                                        <button
                                            type="button"
                                            class="rounded-xl bg-white p-2 text-stone-700 shadow-sm transition hover:bg-stone-100"
                                            @click="changeQuantity(product.id, -1)"
                                        >
                                            <Minus class="h-4 w-4" />
                                        </button>
                                        <Input v-model="quantities[product.id]" type="number" min="1" :max="product.stock" class="border-0 bg-transparent text-center text-base" />
                                        <button
                                            type="button"
                                            class="rounded-xl bg-white p-2 text-stone-700 shadow-sm transition hover:bg-stone-100"
                                            @click="changeQuantity(product.id, 1)"
                                        >
                                            <Plus class="h-4 w-4" />
                                        </button>
                                    </div>

                                    <Button
                                        class="h-full rounded-2xl bg-stone-950 px-6 text-white hover:bg-stone-800"
                                        :disabled="cartLoading || product.stock < 1"
                                        @click="addToCart(product)"
                                    >
                                        <ShoppingBag class="mr-2 h-4 w-4" />
                                        Add
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <div v-if="actionError" class="mt-4 rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-700">
                        {{ actionError }}
                    </div>
                    <div v-if="successMessage" class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-700">
                        {{ successMessage }}
                    </div>
                </section>

                <section id="catalog" class="rounded-2xl bg-white p-6 shadow-sm">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-500">Catalog</p>
                            <h2 class="mt-2 text-2xl font-semibold text-slate-900">All products</h2>
                        </div>
                        <p class="text-sm text-slate-500">{{ visibleProducts.length }} result{{ visibleProducts.length === 1 ? '' : 's' }}</p>
                    </div>

                    <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        <Card
                            v-for="product in visibleProducts"
                            :key="product.id"
                            class="overflow-hidden border-slate-200 bg-white transition hover:shadow-sm"
                        >
                            <CardContent class="space-y-5 p-5">
                                <div class="overflow-hidden rounded-2xl bg-slate-100">
                                    <img
                                        :src="getImageUrl(product.images.find((image) => image.is_primary)?.image_path ?? product.images[0]?.image_path)"
                                        :alt="product.name"
                                        class="h-56 w-full object-cover transition duration-500 hover:scale-105"
                                    />
                                </div>

                                <div class="space-y-1">
                                    <p class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-500">
                                        {{ product.category || 'Uncategorized' }}
                                    </p>
                                    <h3 class="text-lg font-semibold text-slate-900">{{ product.name }}</h3>
                                    <p class="text-sm text-slate-500">SKU {{ product.slug }}</p>
                                </div>

                                <div class="flex items-center justify-between">
                                    <span class="text-lg font-semibold text-slate-950">{{ displayPrice(product.price) }}</span>
                                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">Stock {{ product.stock }}</span>
                                </div>

                                <div class="grid gap-3 sm:grid-cols-[1fr_auto]">
                                    <div class="flex items-center gap-2 rounded-2xl border border-slate-200 bg-slate-50 p-2">
                                        <button
                                            type="button"
                                            class="rounded-xl bg-white p-2 text-slate-700 shadow-sm transition hover:bg-slate-100"
                                            @click="changeQuantity(product.id, -1)"
                                        >
                                            <Minus class="h-4 w-4" />
                                        </button>
                                        <Input v-model="quantities[product.id]" type="number" min="1" :max="product.stock" class="border-0 bg-transparent text-center text-base" />
                                        <button
                                            type="button"
                                            class="rounded-xl bg-white p-2 text-slate-700 shadow-sm transition hover:bg-slate-100"
                                            @click="changeQuantity(product.id, 1)"
                                        >
                                            <Plus class="h-4 w-4" />
                                        </button>
                                    </div>

                                    <Button class="h-full rounded-2xl bg-slate-950 px-6 text-white hover:bg-slate-800" :disabled="cartLoading || product.stock < 1" @click="addToCart(product)">
                                        <ShoppingBag class="mr-2 h-4 w-4" />
                                        Add
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </section>
            </template>
        </div>
    </ShopLayout>
</template>
