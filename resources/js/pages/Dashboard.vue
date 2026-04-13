<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Boxes, ReceiptText, ShoppingBag } from 'lucide-vue-next';
import { computed } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

const page = usePage<SharedData>();
const isAdmin = computed(() => page.props.auth.user?.role === 'admin');
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
            <div class="rounded-2xl border border-sidebar-border/70 bg-card p-6">
                <p class="text-xs uppercase tracking-[0.25em] text-muted-foreground">Welcome</p>
                <h1 class="mt-2 text-3xl font-semibold">Ecommerce Control Center</h1>
                <p class="mt-2 max-w-3xl text-sm text-muted-foreground">
                    Manage store operations, monitor orders, and keep your product catalog updated.
                </p>
            </div>

            <div v-if="isAdmin" class="grid auto-rows-min gap-4 md:grid-cols-2">
                <div class="rounded-2xl border border-sidebar-border/70 bg-card p-5">
                    <div class="flex items-center justify-between">
                        <div class="rounded-xl bg-secondary p-2">
                            <Boxes class="h-5 w-5" />
                        </div>
                        <span class="text-xs font-medium uppercase text-muted-foreground">Admin Module</span>
                    </div>
                    <h2 class="mt-4 text-xl font-semibold">Product Management</h2>
                    <p class="mt-2 text-sm text-muted-foreground">Create, update, and organize catalog items for your store.</p>
                    <Link href="/admin/products" class="mt-5 inline-flex text-sm font-medium text-primary hover:underline">
                        Open products
                    </Link>
                </div>

                <div class="rounded-2xl border border-sidebar-border/70 bg-card p-5">
                    <div class="flex items-center justify-between">
                        <div class="rounded-xl bg-secondary p-2">
                            <ReceiptText class="h-5 w-5" />
                        </div>
                        <span class="text-xs font-medium uppercase text-muted-foreground">Admin Module</span>
                    </div>
                    <h2 class="mt-4 text-xl font-semibold">Order Management</h2>
                    <p class="mt-2 text-sm text-muted-foreground">Track customer orders and update payment and fulfillment statuses.</p>
                    <Link href="/admin/orders" class="mt-5 inline-flex text-sm font-medium text-primary hover:underline">
                        Open orders
                    </Link>
                </div>
            </div>

            <div class="rounded-2xl border border-sidebar-border/70 bg-card p-5">
                <div class="flex items-center gap-2">
                    <ShoppingBag class="h-5 w-5 text-muted-foreground" />
                    <h2 class="text-lg font-semibold">Store Front</h2>
                </div>
                <p class="mt-2 text-sm text-muted-foreground">
                    Preview the buyer experience and checkout flow from the public shop pages.
                </p>
                <div class="mt-4 flex flex-wrap gap-3">
                    <Link href="/shop" class="inline-flex rounded-md bg-primary px-3 py-2 text-sm font-medium text-primary-foreground hover:opacity-90">
                        Open shop
                    </Link>
                    <Link href="/checkout" class="inline-flex rounded-md border border-input px-3 py-2 text-sm font-medium hover:bg-accent">
                        Open checkout
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
