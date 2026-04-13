<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import ShopLayout from '@/layouts/ShopLayout.vue';
import { webFetch } from '@/lib/web-fetch';
import { Head, Link } from '@inertiajs/vue3';
import { LoaderCircle, Minus, Plus, Trash2 } from 'lucide-vue-next';
import { computed, onMounted, reactive, ref } from 'vue';

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

const cart = ref<CartSummary>({ items: [], total: 0 });
const loading = ref(true);
const updating = ref(false);
const pageError = ref('');
const quantities = reactive<Record<number, number>>({});

const currency = new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
});

const itemCount = computed(() => cart.value.items.reduce((sum: number, item: CartItem) => sum + item.quantity, 0));

const loadCart = async () => {
    loading.value = true;
    pageError.value = '';

    try {
        const response = await webFetch('/api/cart');
        if (!response.ok) {
            throw new Error('Unable to load cart right now.');
        }

        cart.value = (await response.json()) as CartSummary;
        cart.value.items.forEach((item: CartItem) => {
            quantities[item.product_id] = item.quantity;
        });
    } catch (error) {
        pageError.value = error instanceof Error ? error.message : 'Unable to load cart right now.';
    } finally {
        loading.value = false;
    }
};

const updateItem = async (productId: number, quantity: number) => {
    updating.value = true;
    pageError.value = '';

    try {
        const response = await webFetch(`/api/cart/items/${productId}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ quantity }),
        });

        const data = await response.json();
        if (!response.ok) {
            pageError.value = data.message ?? 'Unable to update cart item.';
            return;
        }

        cart.value = data as CartSummary;
        cart.value.items.forEach((item: CartItem) => {
            quantities[item.product_id] = item.quantity;
        });
    } catch (error) {
        pageError.value = error instanceof Error ? error.message : 'Unable to update cart item.';
    } finally {
        updating.value = false;
    }
};

const removeItem = async (productId: number) => {
    updating.value = true;
    pageError.value = '';

    try {
        const response = await webFetch(`/api/cart/items/${productId}`, { method: 'DELETE' });
        const data = await response.json();

        if (!response.ok) {
            pageError.value = data.message ?? 'Unable to remove cart item.';
            return;
        }

        cart.value = data as CartSummary;
    } catch (error) {
        pageError.value = error instanceof Error ? error.message : 'Unable to remove cart item.';
    } finally {
        updating.value = false;
    }
};

const bump = (productId: number, amount: number) => {
    const next = Math.max(0, Number(quantities[productId] ?? 0) + amount);
    quantities[productId] = next;
    void updateItem(productId, next);
};

onMounted(() => {
    void loadCart();
});
</script>

<template>
    <Head title="Your Cart" />

    <ShopLayout>
        <div class="mb-6 rounded-2xl bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-500">Cart</p>
                    <h1 class="mt-2 text-2xl font-semibold text-slate-900">Review your items</h1>
                    <p class="mt-1 text-sm text-slate-500">Update quantities, remove items, then proceed to checkout.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Link :href="route('home')"><Button variant="outline" class="rounded-xl border-slate-300 bg-white">Continue shopping</Button></Link>
                    <Link :href="route('shop.checkout')"><Button class="rounded-xl bg-slate-950 text-white hover:bg-slate-800">Checkout</Button></Link>
                </div>
            </div>
        </div>

            <div v-if="pageError" class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-700">
                {{ pageError }}
            </div>

            <div v-if="loading" class="grid gap-4">
                <div v-for="index in 3" :key="index" class="h-24 animate-pulse rounded-2xl bg-stone-200/80" />
            </div>

            <div v-else class="grid gap-6 lg:grid-cols-[1.2fr_0.8fr]">
                <Card class="border-stone-200 bg-white/90">
                    <CardContent class="p-6">
                        <div v-if="cart.items.length === 0" class="rounded-2xl bg-stone-50 p-6 text-sm text-stone-600">
                            Your cart is empty.
                        </div>

                        <div v-else class="space-y-4">
                            <div
                                v-for="item in cart.items"
                                :key="item.product_id"
                                class="flex flex-col gap-4 rounded-2xl border border-stone-200 bg-white p-4 sm:flex-row sm:items-center sm:justify-between"
                            >
                                <div class="min-w-0">
                                    <p class="truncate text-base font-semibold text-stone-900">{{ item.name }}</p>
                                    <p class="mt-1 text-sm text-stone-500">{{ currency.format(item.price) }} each</p>
                                </div>

                                <div class="flex flex-wrap items-center gap-3 sm:justify-end">
                                    <div class="flex items-center gap-2 rounded-2xl border border-stone-200 bg-stone-50 p-2">
                                        <button
                                            type="button"
                                            class="rounded-xl bg-white p-2 text-stone-700 shadow-sm transition hover:bg-stone-100"
                                            :disabled="updating"
                                            @click="bump(item.product_id, -1)"
                                        >
                                            <Minus class="h-4 w-4" />
                                        </button>
                                        <Input
                                            v-model="quantities[item.product_id]"
                                            type="number"
                                            min="0"
                                            class="w-20 border-0 bg-transparent text-center text-base"
                                            :disabled="updating"
                                            @change="updateItem(item.product_id, Number(quantities[item.product_id] ?? 0))"
                                        />
                                        <button
                                            type="button"
                                            class="rounded-xl bg-white p-2 text-stone-700 shadow-sm transition hover:bg-stone-100"
                                            :disabled="updating"
                                            @click="bump(item.product_id, 1)"
                                        >
                                            <Plus class="h-4 w-4" />
                                        </button>
                                    </div>

                                    <div class="text-right">
                                        <p class="text-sm text-stone-500">Line total</p>
                                        <p class="text-base font-semibold text-stone-900">{{ currency.format(item.line_total) }}</p>
                                    </div>

                                    <Button
                                        variant="outline"
                                        class="rounded-2xl border-stone-300 bg-white"
                                        :disabled="updating"
                                        @click="removeItem(item.product_id)"
                                    >
                                        <Trash2 class="mr-2 h-4 w-4" />
                                        Remove
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-none bg-stone-950 text-stone-50 shadow-[0_25px_80px_-45px_rgba(15,23,42,0.75)]">
                    <CardContent class="space-y-4 p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs uppercase tracking-[0.3em] text-stone-400">Summary</p>
                                <p class="mt-2 text-2xl font-semibold">{{ itemCount }} item{{ itemCount === 1 ? '' : 's' }}</p>
                            </div>
                            <LoaderCircle v-if="updating" class="h-5 w-5 animate-spin text-stone-300" />
                        </div>

                        <div class="rounded-2xl border border-white/10 bg-white/5 p-5">
                            <div class="flex items-center justify-between text-lg font-semibold text-white">
                                <span>Total</span>
                                <span>{{ currency.format(cart.total) }}</span>
                            </div>
                            <p class="mt-2 text-sm text-stone-400">Checkout requires login.</p>
                        </div>

                        <Link :href="route('shop.checkout')" class="block">
                            <Button class="w-full rounded-2xl bg-amber-400 text-stone-950 hover:bg-amber-300">Proceed to checkout</Button>
                        </Link>
                    </CardContent>
                </Card>
            </div>
    </ShopLayout>
</template>

