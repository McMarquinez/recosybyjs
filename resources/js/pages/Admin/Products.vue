<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { webFetch } from '@/lib/web-fetch';
import type { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';

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
    price: string;
    stock: number;
    status: 'draft' | 'active' | 'inactive';
    images: ProductImage[];
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Admin Products', href: '/admin/products' },
];

const products = ref<Product[]>([]);
const loading = ref(true);
const saving = ref(false);
const showModal = ref(false);
const editingId = ref<number | null>(null);
const pageError = ref('');
const formError = ref('');

const form = ref({
    name: '',
    category: '',
    slug: '',
    description: '',
    price: '',
    stock: 0,
    status: 'draft' as Product['status'],
    images: [] as File[],
});

const loadProducts = async () => {
    loading.value = true;
    pageError.value = '';

    try {
        const response = await webFetch('/api/admin/products');

        if (!response.ok) {
            throw new Error('Unable to load products.');
        }

        products.value = (await response.json()) as Product[];
    } catch (error) {
        pageError.value = error instanceof Error ? error.message : 'Unable to load products.';
    } finally {
        loading.value = false;
    }
};

const resetForm = () => {
    form.value = {
        name: '',
        category: '',
        slug: '',
        description: '',
        price: '',
        stock: 0,
        status: 'draft',
        images: [],
    };
    editingId.value = null;
    formError.value = '';
};

const openCreate = () => {
    resetForm();
    showModal.value = true;
};

const openEdit = (product: Product) => {
    form.value = {
        name: product.name,
        category: product.category ?? '',
        slug: product.slug,
        description: product.description ?? '',
        price: product.price,
        stock: product.stock,
        status: product.status,
        images: [],
    };
    editingId.value = product.id;
    formError.value = '';
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    resetForm();
};

const onImagesSelected = (event: Event) => {
    const target = event.target as HTMLInputElement;
    form.value.images = target.files ? Array.from(target.files) : [];
};

const submit = async () => {
    saving.value = true;
    formError.value = '';

    try {
        const payload = new FormData();
        payload.append('name', form.value.name);
        payload.append('category', form.value.category);
        payload.append('slug', form.value.slug);
        payload.append('description', form.value.description);
        payload.append('price', form.value.price);
        payload.append('stock', String(form.value.stock));
        payload.append('status', form.value.status);

        form.value.images.forEach((image) => {
            payload.append('images[]', image);
        });

        let endpoint = '/api/admin/products';
        let method = 'POST';

        if (editingId.value) {
            endpoint = `/api/admin/products/${editingId.value}`;
            payload.append('_method', 'PUT');
        }

        const response = await webFetch(endpoint, {
            method,
            headers: {
            },
            body: payload,
        });

        const data = await response.json();

        if (!response.ok) {
            formError.value = data.message ?? 'Unable to save product.';
            return;
        }

        await loadProducts();
        closeModal();
    } catch (error) {
        formError.value = error instanceof Error ? error.message : 'Unable to save product.';
    } finally {
        saving.value = false;
    }
};

const deleteProduct = async (id: number) => {
    if (!window.confirm('Delete this product?')) {
        return;
    }

    try {
        const response = await webFetch(`/api/admin/products/${id}`, {
            method: 'DELETE',
        });

        if (!response.ok) {
            throw new Error('Unable to delete product.');
        }

        await loadProducts();
    } catch (error) {
        pageError.value = error instanceof Error ? error.message : 'Unable to delete product.';
    }
};

void loadProducts();
</script>

<template>
    <Head title="Admin Products" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="flex items-center justify-between rounded-xl border border-sidebar-border/70 bg-card p-4">
                <div>
                    <h1 class="text-2xl font-semibold">Product Management</h1>
                    <p class="text-sm text-muted-foreground">Create and manage your product catalog.</p>
                </div>
                <button class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:opacity-90" @click="openCreate">
                    Add Product
                </button>
            </div>

            <div v-if="pageError" class="rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                {{ pageError }}
            </div>

            <div class="overflow-hidden rounded-xl border border-sidebar-border/70 bg-card">
                <div v-if="loading" class="p-6 text-sm text-muted-foreground">Loading products...</div>

                <table v-else class="min-w-full text-sm">
                    <thead class="bg-muted/50">
                        <tr class="text-left">
                            <th class="px-4 py-3">Name</th>
                            <th class="px-4 py-3">Category</th>
                            <th class="px-4 py-3">Price</th>
                            <th class="px-4 py-3">Stock</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="product in products" :key="product.id" class="border-t border-sidebar-border/70">
                            <td class="px-4 py-3">
                                <p class="font-medium">{{ product.name }}</p>
                                <p class="text-xs text-muted-foreground">{{ product.slug }}</p>
                            </td>
                            <td class="px-4 py-3">{{ product.category || 'Uncategorized' }}</td>
                            <td class="px-4 py-3">PHP {{ product.price }}</td>
                            <td class="px-4 py-3">{{ product.stock }}</td>
                            <td class="px-4 py-3 capitalize">{{ product.status }}</td>
                            <td class="px-4 py-3 text-right">
                                <button class="mr-2 rounded border px-2 py-1 text-xs hover:bg-accent" @click="openEdit(product)">Edit</button>
                                <button class="rounded border border-red-300 px-2 py-1 text-xs text-red-600 hover:bg-red-50" @click="deleteProduct(product.id)">
                                    Delete
                                </button>
                            </td>
                        </tr>
                        <tr v-if="products.length === 0">
                            <td colspan="6" class="px-4 py-6 text-center text-muted-foreground">No products yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4">
            <div class="w-full max-w-2xl rounded-xl border border-sidebar-border/70 bg-background p-6 shadow-xl">
                <h2 class="text-lg font-semibold">{{ editingId ? 'Edit Product' : 'Add Product' }}</h2>
                <p class="mt-1 text-sm text-muted-foreground">Maintain product details and stock values.</p>

                <div v-if="formError" class="mt-4 rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                    {{ formError }}
                </div>

                <form class="mt-4 grid gap-4" @submit.prevent="submit">
                    <div class="grid gap-2">
                        <label class="text-sm font-medium">Name</label>
                        <input v-model="form.name" required class="rounded-md border border-input bg-background px-3 py-2 text-sm" />
                    </div>

                    <div class="grid gap-2">
                        <label class="text-sm font-medium">Category</label>
                        <input v-model="form.category" placeholder="e.g. Electronics" class="rounded-md border border-input bg-background px-3 py-2 text-sm" />
                    </div>

                    <div class="grid gap-2">
                        <label class="text-sm font-medium">Slug (optional)</label>
                        <input v-model="form.slug" class="rounded-md border border-input bg-background px-3 py-2 text-sm" />
                    </div>

                    <div class="grid gap-2">
                        <label class="text-sm font-medium">Description</label>
                        <textarea v-model="form.description" rows="3" class="rounded-md border border-input bg-background px-3 py-2 text-sm" />
                    </div>

                    <div class="grid gap-4 md:grid-cols-3">
                        <div class="grid gap-2">
                            <label class="text-sm font-medium">Price</label>
                            <input v-model="form.price" required type="number" min="0" step="0.01" class="rounded-md border border-input bg-background px-3 py-2 text-sm" />
                        </div>
                        <div class="grid gap-2">
                            <label class="text-sm font-medium">Stock</label>
                            <input v-model="form.stock" required type="number" min="0" class="rounded-md border border-input bg-background px-3 py-2 text-sm" />
                        </div>
                        <div class="grid gap-2">
                            <label class="text-sm font-medium">Status</label>
                            <select v-model="form.status" class="rounded-md border border-input bg-background px-3 py-2 text-sm">
                                <option value="draft">Draft</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <label class="text-sm font-medium">Product Images</label>
                        <input type="file" multiple accept="image/*" class="rounded-md border border-input bg-background px-3 py-2 text-sm" @change="onImagesSelected" />
                        <p class="text-xs text-muted-foreground">Uploading new images on edit will replace the old set.</p>
                    </div>

                    <div class="mt-2 flex justify-end gap-2">
                        <button type="button" class="rounded-md border border-input px-4 py-2 text-sm hover:bg-accent" @click="closeModal">Cancel</button>
                        <button type="submit" :disabled="saving" class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:opacity-90">
                            {{ saving ? 'Saving...' : editingId ? 'Update Product' : 'Create Product' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
