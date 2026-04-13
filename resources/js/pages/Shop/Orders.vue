<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import ShopLayout from '@/layouts/ShopLayout.vue';
import { webFetch } from '@/lib/web-fetch';
import { Head, Link } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

type OrderItem = {
    id: number;
    product_name: string;
    price: string;
    quantity: number;
    line_total: string;
};

type Order = {
    id: number;
    order_number: string;
    total: string;
    status: string;
    payment_status: string;
    payment_method: string;
    created_at: string;
    items: OrderItem[];
};

const orders = ref<Order[]>([]);
const loading = ref(true);
const pageError = ref('');

const loadOrders = async () => {
    loading.value = true;
    pageError.value = '';

    try {
        const response = await webFetch('/api/orders');
        if (!response.ok) {
            throw new Error('Unable to load your orders right now.');
        }

        orders.value = (await response.json()) as Order[];
    } catch (error) {
        pageError.value = error instanceof Error ? error.message : 'Unable to load your orders right now.';
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    void loadOrders();
});
</script>

<template>
    <Head title="My Orders" />

    <ShopLayout>
        <div class="mb-6 rounded-2xl bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-500">Orders</p>
                    <h1 class="mt-2 text-2xl font-semibold text-slate-900">Track your orders</h1>
                    <p class="mt-1 text-sm text-slate-500">See order status and item details.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Link :href="route('shop.cart')"><Button variant="outline" class="rounded-xl border-slate-300 bg-white">Cart</Button></Link>
                    <Link :href="route('home')"><Button class="rounded-xl bg-slate-950 text-white hover:bg-slate-800">Back to shop</Button></Link>
                </div>
            </div>
        </div>

            <div v-if="pageError" class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-700">
                {{ pageError }}
            </div>

            <div v-if="loading" class="grid gap-4">
                <div v-for="index in 4" :key="index" class="h-28 animate-pulse rounded-2xl bg-stone-200/80" />
            </div>

            <div v-else class="space-y-4">
                <Card v-for="order in orders" :key="order.id" class="border-stone-200 bg-white/90">
                    <CardContent class="space-y-4 p-6">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.25em] text-stone-500">Order</p>
                                <p class="mt-1 text-2xl font-semibold text-stone-900">{{ order.order_number }}</p>
                                <p class="mt-2 text-sm text-stone-600">Placed {{ new Date(order.created_at).toLocaleString() }}</p>
                            </div>

                            <div class="grid gap-2 rounded-2xl bg-stone-50 p-4 text-sm">
                                <div class="flex items-center justify-between gap-6">
                                    <span class="text-stone-500">Status</span>
                                    <span class="font-semibold text-stone-900">{{ order.status }}</span>
                                </div>
                                <div class="flex items-center justify-between gap-6">
                                    <span class="text-stone-500">Payment</span>
                                    <span class="font-semibold text-stone-900">{{ order.payment_status }}</span>
                                </div>
                                <div class="flex items-center justify-between gap-6">
                                    <span class="text-stone-500">Total</span>
                                    <span class="font-semibold text-stone-900">PHP {{ order.total }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-stone-200 bg-white p-4">
                            <p class="text-sm font-semibold text-stone-900">Items</p>
                            <div class="mt-3 space-y-2">
                                <div v-for="item in order.items" :key="item.id" class="flex items-start justify-between gap-4 text-sm">
                                    <div class="min-w-0">
                                        <p class="truncate font-medium text-stone-900">{{ item.product_name }}</p>
                                        <p class="text-stone-500">{{ item.quantity }} × PHP {{ item.price }}</p>
                                    </div>
                                    <p class="font-semibold text-stone-900">PHP {{ item.line_total }}</p>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <div v-if="orders.length === 0" class="rounded-2xl border border-stone-200 bg-white/90 px-6 py-10 text-center text-sm text-stone-600">
                    No orders yet.
                </div>
            </div>
    </ShopLayout>
</template>

