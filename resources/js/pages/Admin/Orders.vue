<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { webFetch } from '@/lib/web-fetch';
import type { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';

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
    customer_name: string;
    customer_email: string;
    customer_phone: string;
    customer_address: string;
    total: string;
    status: string;
    payment_status: string;
    payment_method: string;
    payment_reference: string | null;
    items: OrderItem[];
    created_at: string;
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Admin Orders', href: '/admin/orders' },
];

const orders = ref<Order[]>([]);
const loading = ref(true);
const saving = ref(false);
const showModal = ref(false);
const pageError = ref('');
const formError = ref('');
const selectedOrderId = ref<number | null>(null);

const form = ref({
    status: 'pending_payment',
    payment_status: 'unpaid',
    payment_reference: '',
    payment_proof: null as File | null,
});

const loadOrders = async () => {
    loading.value = true;
    pageError.value = '';

    try {
        const response = await webFetch('/api/admin/orders');

        if (!response.ok) {
            throw new Error('Unable to load orders.');
        }

        orders.value = (await response.json()) as Order[];
    } catch (error) {
        pageError.value = error instanceof Error ? error.message : 'Unable to load orders.';
    } finally {
        loading.value = false;
    }
};

const openUpdateModal = (order: Order) => {
    selectedOrderId.value = order.id;
    form.value = {
        status: order.status,
        payment_status: order.payment_status,
        payment_reference: order.payment_reference ?? '',
        payment_proof: null,
    };
    formError.value = '';
    showModal.value = true;
};

const closeModal = () => {
    selectedOrderId.value = null;
    showModal.value = false;
    formError.value = '';
};

const onPaymentProofSelected = (event: Event) => {
    const target = event.target as HTMLInputElement;
    form.value.payment_proof = target.files?.[0] ?? null;
};

const submitUpdate = async () => {
    if (!selectedOrderId.value) {
        return;
    }

    saving.value = true;
    formError.value = '';

    try {
        const payload = new FormData();
        payload.append('status', form.value.status);
        payload.append('payment_status', form.value.payment_status);
        payload.append('payment_reference', form.value.payment_reference);

        if (form.value.payment_proof) {
            payload.append('payment_proof', form.value.payment_proof);
        }

        payload.append('_method', 'PUT');

        const response = await webFetch(`/api/admin/orders/${selectedOrderId.value}/status`, {
            method: 'POST',
            body: payload,
        });

        const data = await response.json();

        if (!response.ok) {
            formError.value = data.message ?? 'Unable to update order.';
            return;
        }

        await loadOrders();
        closeModal();
    } catch (error) {
        formError.value = error instanceof Error ? error.message : 'Unable to update order.';
    } finally {
        saving.value = false;
    }
};

void loadOrders();
</script>

<template>
    <Head title="Admin Orders" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="rounded-xl border border-sidebar-border/70 bg-card p-4">
                <h1 class="text-2xl font-semibold">Order Management</h1>
                <p class="text-sm text-muted-foreground">Review order details and update order/payment status.</p>
            </div>

            <div v-if="pageError" class="rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                {{ pageError }}
            </div>

            <div class="overflow-hidden rounded-xl border border-sidebar-border/70 bg-card">
                <div v-if="loading" class="p-6 text-sm text-muted-foreground">Loading orders...</div>

                <table v-else class="min-w-full text-sm">
                    <thead class="bg-muted/50">
                        <tr class="text-left">
                            <th class="px-4 py-3">Order #</th>
                            <th class="px-4 py-3">Customer</th>
                            <th class="px-4 py-3">Phone</th>
                            <th class="px-4 py-3">Total</th>
                            <th class="px-4 py-3">Order Status</th>
                            <th class="px-4 py-3">Payment Status</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="order in orders" :key="order.id" class="border-t border-sidebar-border/70">
                            <td class="px-4 py-3">
                                <p class="font-medium">{{ order.order_number }}</p>
                                <p class="text-xs text-muted-foreground">{{ new Date(order.created_at).toLocaleString() }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <p>{{ order.customer_name }}</p>
                                <p class="text-xs text-muted-foreground">{{ order.customer_email }}</p>
                            </td>
                            <td class="px-4 py-3">{{ order.customer_phone }}</td>
                            <td class="px-4 py-3">PHP {{ order.total }}</td>
                            <td class="px-4 py-3 capitalize">{{ order.status }}</td>
                            <td class="px-4 py-3 capitalize">{{ order.payment_status }}</td>
                            <td class="px-4 py-3 text-right">
                                <button class="rounded border px-2 py-1 text-xs hover:bg-accent" @click="openUpdateModal(order)">Update</button>
                            </td>
                        </tr>
                        <tr v-if="orders.length === 0">
                            <td colspan="7" class="px-4 py-6 text-center text-muted-foreground">No orders found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4">
            <div class="w-full max-w-xl rounded-xl border border-sidebar-border/70 bg-background p-6 shadow-xl">
                <h2 class="text-lg font-semibold">Update Order</h2>
                <p class="mt-1 text-sm text-muted-foreground">Set fulfillment and payment status for this order.</p>

                <div v-if="formError" class="mt-4 rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                    {{ formError }}
                </div>

                <form class="mt-4 grid gap-4" @submit.prevent="submitUpdate">
                    <div class="grid gap-2">
                        <label class="text-sm font-medium">Order Status</label>
                        <select v-model="form.status" class="rounded-md border border-input bg-background px-3 py-2 text-sm">
                            <option value="pending_payment">Pending Payment</option>
                            <option value="processing">Processing</option>
                            <option value="paid">Paid</option>
                            <option value="shipped">Shipped</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>

                    <div class="grid gap-2">
                        <label class="text-sm font-medium">Payment Status</label>
                        <select v-model="form.payment_status" class="rounded-md border border-input bg-background px-3 py-2 text-sm">
                            <option value="unpaid">Unpaid</option>
                            <option value="proof_uploaded">Proof Uploaded</option>
                            <option value="paid">Paid</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>

                    <div class="grid gap-2">
                        <label class="text-sm font-medium">Payment Reference</label>
                        <input v-model="form.payment_reference" class="rounded-md border border-input bg-background px-3 py-2 text-sm" />
                    </div>

                    <div class="grid gap-2">
                        <label class="text-sm font-medium">Payment Proof Image</label>
                        <input type="file" accept="image/*" class="rounded-md border border-input bg-background px-3 py-2 text-sm" @change="onPaymentProofSelected" />
                    </div>

                    <div class="mt-2 flex justify-end gap-2">
                        <button type="button" class="rounded-md border border-input px-4 py-2 text-sm hover:bg-accent" @click="closeModal">Cancel</button>
                        <button type="submit" :disabled="saving" class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:opacity-90">
                            {{ saving ? 'Saving...' : 'Update Order' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
