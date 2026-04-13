<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import ShopLayout from '@/layouts/ShopLayout.vue';
import { webFetch } from '@/lib/web-fetch';
import { Head, Link } from '@inertiajs/vue3';
import { CreditCard, ImageUp, LoaderCircle, ShoppingCart } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

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

type CheckoutResponse = {
    order_number: string;
    status: string;
    payment_status: string;
};

const cart = ref<CartSummary>({ items: [], total: 0 });
const loading = ref(true);
const submitting = ref(false);
const pageError = ref('');
const successOrder = ref<CheckoutResponse | null>(null);
const proofName = ref('');

const form = ref({
    name: '',
    email: '',
    customer_phone: '',
    address: '',
    payment_reference: '',
    payment_proof: null as File | null,
});

const errors = ref<Record<string, string>>({});

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
            throw new Error('Unable to load your cart right now.');
        }

        cart.value = (await response.json()) as CartSummary;
    } catch (error) {
        pageError.value = error instanceof Error ? error.message : 'Unable to load your cart right now.';
    } finally {
        loading.value = false;
    }
};

const submitCheckout = async () => {
    submitting.value = true;
    errors.value = {};
    pageError.value = '';
    successOrder.value = null;

    try {
        const payload = new FormData();
        payload.append('name', form.value.name);
        payload.append('email', form.value.email);
        payload.append('customer_phone', form.value.customer_phone);
        payload.append('address', form.value.address);

        if (form.value.payment_reference) {
            payload.append('payment_reference', form.value.payment_reference);
        }

        if (form.value.payment_proof) {
            payload.append('payment_proof', form.value.payment_proof);
        }

        const response = await webFetch('/api/checkout', {
            method: 'POST',
            body: payload,
        });

        const data = await response.json();

        if (response.status === 422) {
            errors.value = data.errors ?? {};
            pageError.value = data.message ?? 'Please check the form and try again.';
            return;
        }

        if (!response.ok) {
            pageError.value = data.message ?? 'Unable to complete checkout.';
            return;
        }

        successOrder.value = data as CheckoutResponse;
        cart.value = { items: [], total: 0 };
        form.value = {
            name: '',
            email: '',
            customer_phone: '',
            address: '',
            payment_reference: '',
            payment_proof: null,
        };
        proofName.value = '';
    } catch (error) {
        pageError.value = error instanceof Error ? error.message : 'Unable to complete checkout.';
    } finally {
        submitting.value = false;
    }
};

const onProofSelected = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0] ?? null;
    form.value.payment_proof = file;
    proofName.value = file?.name ?? '';
};

onMounted(() => {
    void loadCart();
});
</script>

<template>
    <Head title="Checkout" />

    <ShopLayout>
        <div class="mb-6 rounded-2xl bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-500">Checkout</p>
                    <h1 class="mt-2 text-2xl font-semibold text-slate-900">Complete your order</h1>
                    <p class="mt-1 text-sm text-slate-500">
                        Enter delivery details and submit your order as <span class="font-semibold">pending payment</span>.
                    </p>
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

            <div v-if="successOrder" class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-800">
                Order <span class="font-semibold">{{ successOrder.order_number }}</span> created successfully. Status: {{ successOrder.status }}. Payment status:
                {{ successOrder.payment_status }}.
            </div>

            <div class="grid gap-6 lg:grid-cols-[1.05fr_0.95fr]">
                <Card class="border-stone-200 bg-white/90">
                    <CardContent class="space-y-6 p-6">
                        <div class="flex items-center gap-3">
                            <div class="rounded-2xl bg-stone-950 p-3 text-white">
                                <CreditCard class="h-5 w-5" />
                            </div>
                            <div>
                                <h2 class="text-2xl font-semibold text-stone-900">Checkout form</h2>
                                <p class="text-sm text-stone-500">MVP fields only: name, email, address, GCash reference, and proof image.</p>
                            </div>
                        </div>

                        <form class="grid gap-5" @submit.prevent="submitCheckout">
                            <div class="grid gap-2">
                                <Label for="name">Full name</Label>
                                <Input id="name" v-model="form.name" placeholder="Juan Dela Cruz" />
                                <InputError :message="errors.name" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="email">Email</Label>
                                <Input id="email" v-model="form.email" type="email" placeholder="customer@example.com" />
                                <InputError :message="errors.email" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="customer_phone">Customer number</Label>
                                <Input id="customer_phone" v-model="form.customer_phone" type="tel" placeholder="+63 9XX XXX XXXX" />
                                <InputError :message="errors.customer_phone" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="address">Delivery address</Label>
                                <textarea
                                    id="address"
                                    v-model="form.address"
                                    rows="4"
                                    class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                                    placeholder="House number, street, barangay, city, province"
                                />
                                <InputError :message="errors.address" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="payment_reference">GCash reference number</Label>
                                <Input id="payment_reference" v-model="form.payment_reference" placeholder="Optional for now" />
                                <InputError :message="errors.payment_reference" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="payment_proof">Payment proof image</Label>
                                <label
                                    for="payment_proof"
                                    class="flex cursor-pointer items-center justify-between gap-4 rounded-2xl border border-dashed border-stone-300 bg-stone-50 px-4 py-4 text-sm text-stone-600 transition hover:border-stone-400 hover:bg-stone-100"
                                >
                                    <div class="flex items-center gap-3">
                                        <div class="rounded-xl bg-white p-3 shadow-sm">
                                            <ImageUp class="h-5 w-5 text-stone-700" />
                                        </div>
                                        <div>
                                            <p class="font-medium text-stone-900">{{ proofName || 'Choose image file' }}</p>
                                            <p class="text-xs text-stone-500">JPG or PNG up to 5MB</p>
                                        </div>
                                    </div>
                                    <span class="rounded-full bg-stone-900 px-3 py-1 text-xs font-semibold text-white">Browse</span>
                                </label>
                                <input id="payment_proof" type="file" accept="image/*" class="hidden" @change="onProofSelected" />
                                <InputError :message="errors.payment_proof" />
                            </div>

                            <Button
                                type="submit"
                                class="mt-2 h-12 rounded-2xl bg-stone-950 text-white hover:bg-stone-800"
                                :disabled="submitting || cart.items.length === 0"
                            >
                                <LoaderCircle v-if="submitting" class="mr-2 h-4 w-4 animate-spin" />
                                Place order
                            </Button>
                        </form>
                    </CardContent>
                </Card>

                <div class="space-y-6">
                    <Card class="border-none bg-stone-950 text-stone-50 shadow-[0_25px_80px_-45px_rgba(15,23,42,0.75)]">
                        <CardContent class="space-y-5 p-6">
                            <div class="flex items-center gap-3">
                                <div class="rounded-2xl bg-white/10 p-3">
                                    <ShoppingCart class="h-5 w-5" />
                                </div>
                                <div>
                                    <h2 class="text-2xl font-semibold">Order summary</h2>
                                    <p class="text-sm text-stone-400">{{ itemCount }} item{{ itemCount === 1 ? '' : 's' }} in cart</p>
                                </div>
                            </div>

                            <div v-if="loading" class="space-y-3">
                                <div v-for="index in 3" :key="index" class="h-20 animate-pulse rounded-2xl bg-white/10" />
                            </div>

                            <div v-else-if="cart.items.length === 0" class="rounded-2xl bg-white/5 p-5 text-sm text-stone-300">
                                Your cart is empty. Add products first before checking out.
                            </div>

                            <div v-else class="space-y-3">
                                <div v-for="item in cart.items" :key="item.product_id" class="rounded-2xl bg-white/5 p-4">
                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <p class="font-medium text-white">{{ item.name }}</p>
                                            <p class="mt-1 text-sm text-stone-400">{{ item.quantity }} x {{ currency.format(item.price) }}</p>
                                        </div>
                                        <p class="font-semibold text-white">{{ currency.format(item.line_total) }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-2xl border border-white/10 bg-white/5 p-5">
                                <div class="flex items-center justify-between text-sm text-stone-300">
                                    <span>Payment method</span>
                                    <span class="font-medium text-white">GCash Manual</span>
                                </div>
                                <div class="mt-3 flex items-center justify-between text-sm text-stone-300">
                                    <span>Order status after submit</span>
                                    <span class="font-medium text-white">pending_payment</span>
                                </div>
                                <div class="mt-3 flex items-center justify-between text-lg font-semibold text-white">
                                    <span>Total</span>
                                    <span>{{ currency.format(cart.total) }}</span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card class="border-stone-200 bg-white/90">
                        <CardContent class="space-y-3 p-6 text-sm leading-6 text-stone-600">
                            <h3 class="text-lg font-semibold text-stone-900">Manual payment reminder</h3>
                            <p>Use the GCash reference field if the customer already paid. If not, the order still gets created and stays unpaid for admin review.</p>
                            <p>Uploading proof is optional, but the backend will store it immediately when included.</p>
                        </CardContent>
                    </Card>
                </div>
            </div>
    </ShopLayout>
</template>
