<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { webFetch } from '@/lib/web-fetch';
import type { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';

type Banner = {
    id: number;
    title: string;
    subtitle: string | null;
    image_path: string | null;
    cta_text: string | null;
    cta_url: string | null;
    sort_order: number;
    is_active: boolean;
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Admin Banners', href: '/admin/banners' },
];

const banners = ref<Banner[]>([]);
const loading = ref(true);
const saving = ref(false);
const showModal = ref(false);
const editingId = ref<number | null>(null);
const pageError = ref('');
const formError = ref('');

const form = ref({
    title: '',
    subtitle: '',
    cta_text: '',
    cta_url: '',
    sort_order: 0,
    is_active: true,
    image: null as File | null,
});

const imagePreview = ref<string>('');

const getImageUrl = (path?: string | null) => {
    if (!path) return '';
    return `/storage/${path}`;
};

const loadBanners = async () => {
    loading.value = true;
    pageError.value = '';

    try {
        const response = await webFetch('/api/admin/banners');
        if (!response.ok) throw new Error('Unable to load banners.');
        banners.value = (await response.json()) as Banner[];
    } catch (error) {
        pageError.value = error instanceof Error ? error.message : 'Unable to load banners.';
    } finally {
        loading.value = false;
    }
};

const resetForm = () => {
    form.value = {
        title: '',
        subtitle: '',
        cta_text: '',
        cta_url: '',
        sort_order: 0,
        is_active: true,
        image: null,
    };
    imagePreview.value = '';
    editingId.value = null;
    formError.value = '';
};

const openCreate = () => {
    resetForm();
    showModal.value = true;
};

const openEdit = (banner: Banner) => {
    form.value = {
        title: banner.title,
        subtitle: banner.subtitle ?? '',
        cta_text: banner.cta_text ?? '',
        cta_url: banner.cta_url ?? '',
        sort_order: banner.sort_order ?? 0,
        is_active: banner.is_active,
        image: null,
    };
    imagePreview.value = getImageUrl(banner.image_path);
    editingId.value = banner.id;
    formError.value = '';
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    resetForm();
};

const onImageSelected = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0] ?? null;
    form.value.image = file;
    imagePreview.value = file ? URL.createObjectURL(file) : imagePreview.value;
};

const submit = async () => {
    saving.value = true;
    formError.value = '';

    try {
        const payload = new FormData();
        payload.append('title', form.value.title);
        payload.append('subtitle', form.value.subtitle);
        payload.append('cta_text', form.value.cta_text);
        payload.append('cta_url', form.value.cta_url);
        payload.append('sort_order', String(form.value.sort_order));
        payload.append('is_active', form.value.is_active ? '1' : '0');

        if (form.value.image) {
            payload.append('image', form.value.image);
        }

        let endpoint = '/api/admin/banners';
        if (editingId.value) {
            endpoint = `/api/admin/banners/${editingId.value}`;
            payload.append('_method', 'PUT');
        }

        const response = await webFetch(endpoint, {
            method: 'POST',
            body: payload,
        });

        const data = await response.json();
        if (!response.ok) {
            formError.value = data.message ?? 'Unable to save banner.';
            return;
        }

        await loadBanners();
        closeModal();
    } catch (error) {
        formError.value = error instanceof Error ? error.message : 'Unable to save banner.';
    } finally {
        saving.value = false;
    }
};

const deleteBanner = async (id: number) => {
    if (!window.confirm('Delete this banner?')) return;

    try {
        const response = await webFetch(`/api/admin/banners/${id}`, { method: 'DELETE' });
        if (!response.ok) throw new Error('Unable to delete banner.');
        await loadBanners();
    } catch (error) {
        pageError.value = error instanceof Error ? error.message : 'Unable to delete banner.';
    }
};

void loadBanners();
</script>

<template>
    <Head title="Admin Banners" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="flex items-center justify-between rounded-xl border border-sidebar-border/70 bg-card p-4">
                <div>
                    <h1 class="text-2xl font-semibold">Banner Management</h1>
                    <p class="text-sm text-muted-foreground">Add hero/marketing banners for the shop homepage.</p>
                </div>
                <button class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:opacity-90" @click="openCreate">
                    Add Banner
                </button>
            </div>

            <div v-if="pageError" class="rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                {{ pageError }}
            </div>

            <div class="overflow-hidden rounded-xl border border-sidebar-border/70 bg-card">
                <div v-if="loading" class="p-6 text-sm text-muted-foreground">Loading banners...</div>

                <table v-else class="min-w-full text-sm">
                    <thead class="bg-muted/50">
                        <tr class="text-left">
                            <th class="px-4 py-3">Title</th>
                            <th class="px-4 py-3">Active</th>
                            <th class="px-4 py-3">Order</th>
                            <th class="px-4 py-3">CTA</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="banner in banners" :key="banner.id" class="border-t border-sidebar-border/70">
                            <td class="px-4 py-3">
                                <p class="font-medium">{{ banner.title }}</p>
                                <p class="text-xs text-muted-foreground">{{ banner.subtitle || '—' }}</p>
                            </td>
                            <td class="px-4 py-3">{{ banner.is_active ? 'Yes' : 'No' }}</td>
                            <td class="px-4 py-3">{{ banner.sort_order }}</td>
                            <td class="px-4 py-3">
                                <span v-if="banner.cta_text" class="rounded-full bg-muted px-3 py-1 text-xs">{{ banner.cta_text }}</span>
                                <span v-else class="text-muted-foreground">—</span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button class="mr-2 rounded border px-2 py-1 text-xs hover:bg-accent" @click="openEdit(banner)">Edit</button>
                                <button class="rounded border border-red-300 px-2 py-1 text-xs text-red-600 hover:bg-red-50" @click="deleteBanner(banner.id)">
                                    Delete
                                </button>
                            </td>
                        </tr>
                        <tr v-if="banners.length === 0">
                            <td colspan="5" class="px-4 py-6 text-center text-muted-foreground">No banners yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4">
            <div class="w-full max-w-2xl rounded-xl border border-sidebar-border/70 bg-background p-6 shadow-xl">
                <h2 class="text-lg font-semibold">{{ editingId ? 'Edit Banner' : 'Add Banner' }}</h2>
                <p class="mt-1 text-sm text-muted-foreground">Banners appear on the shop homepage.</p>

                <div v-if="formError" class="mt-4 rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                    {{ formError }}
                </div>

                <form class="mt-4 grid gap-4" @submit.prevent="submit">
                    <div class="grid gap-2">
                        <label class="text-sm font-medium">Title</label>
                        <input v-model="form.title" required class="rounded-md border border-input bg-background px-3 py-2 text-sm" />
                    </div>

                    <div class="grid gap-2">
                        <label class="text-sm font-medium">Subtitle</label>
                        <input v-model="form.subtitle" class="rounded-md border border-input bg-background px-3 py-2 text-sm" />
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="grid gap-2">
                            <label class="text-sm font-medium">CTA Text</label>
                            <input v-model="form.cta_text" class="rounded-md border border-input bg-background px-3 py-2 text-sm" />
                        </div>
                        <div class="grid gap-2">
                            <label class="text-sm font-medium">CTA URL</label>
                            <input v-model="form.cta_url" class="rounded-md border border-input bg-background px-3 py-2 text-sm" />
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="grid gap-2">
                            <label class="text-sm font-medium">Sort Order</label>
                            <input v-model="form.sort_order" type="number" min="0" class="rounded-md border border-input bg-background px-3 py-2 text-sm" />
                        </div>
                        <div class="grid gap-2">
                            <label class="text-sm font-medium">Active</label>
                            <select v-model="form.is_active" class="rounded-md border border-input bg-background px-3 py-2 text-sm">
                                <option :value="true">Yes</option>
                                <option :value="false">No</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <label class="text-sm font-medium">Banner Image</label>
                        <input type="file" accept="image/*" class="rounded-md border border-input bg-background px-3 py-2 text-sm" @change="onImageSelected" />
                        <div v-if="imagePreview" class="mt-2 overflow-hidden rounded-xl border border-sidebar-border/70">
                            <img :src="imagePreview" alt="Banner preview" class="h-40 w-full object-cover" />
                        </div>
                    </div>

                    <div class="mt-2 flex justify-end gap-2">
                        <button type="button" class="rounded-md border border-input px-4 py-2 text-sm hover:bg-accent" @click="closeModal">Cancel</button>
                        <button type="submit" :disabled="saving" class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:opacity-90">
                            {{ saving ? 'Saving...' : editingId ? 'Update Banner' : 'Create Banner' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

