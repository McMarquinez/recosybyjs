<script setup lang="ts">
import HeroSlider from '@/components/shop/HeroSlider.vue';
import ShopNavbar from '@/components/shop/ShopNavbar.vue';
import { useShopState } from '@/composables/useShopState';
import { computed, onMounted, ref } from 'vue';

const props = withDefaults(
    defineProps<{
        title?: string;
        categories?: string[];
        showHero?: boolean;
    }>(),
    {
        title: '',
        categories: () => [],
        showHero: false,
    },
);

const { cartCount, banners, ensureCart, ensureBanners } = useShopState();
const ready = ref(false);

const navbarCategories = computed(() => props.categories.slice(0, 12));

onMounted(async () => {
    await Promise.all([ensureCart(), ensureBanners()]);
    ready.value = true;
});
</script>

<template>
    <div class="min-h-screen bg-slate-50 text-slate-900">
        <ShopNavbar :cart-count="cartCount" :categories="navbarCategories" />

        <main class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            <div v-if="props.showHero && ready" class="mb-8">
                <HeroSlider :banners="banners" />
            </div>

            <slot />
        </main>

        <footer class="border-t border-slate-200 bg-white">
            <div class="mx-auto w-full max-w-7xl px-4 py-10 text-sm text-slate-500 sm:px-6 lg:px-8">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <p>© {{ new Date().getFullYear() }} Logicbox Shop. All rights reserved.</p>
                    <div class="flex flex-wrap gap-4">
                        <a class="hover:text-slate-700" href="#catalog">Products</a>
                        <a class="hover:text-slate-700" :href="route('shop.cart')">Cart</a>
                        <a class="hover:text-slate-700" :href="route('shop.orders')">Orders</a>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>

