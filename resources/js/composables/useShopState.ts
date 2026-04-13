import { webFetch } from '@/lib/web-fetch';
import { computed, ref } from 'vue';

type CartItem = {
    product_id: number;
    quantity: number;
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

const cart = ref<CartSummary>({ items: [], total: 0 });
const cartLoaded = ref(false);

const banners = ref<Banner[]>([]);
const bannersLoaded = ref(false);

export function useShopState() {
    const cartCount = computed(() => cart.value.items.reduce((sum, item) => sum + item.quantity, 0));

    const loadCart = async () => {
        const response = await webFetch('/api/cart');
        if (!response.ok) {
            return;
        }

        cart.value = (await response.json()) as CartSummary;
        cartLoaded.value = true;
    };

    const loadBanners = async () => {
        const response = await webFetch('/api/banners');
        if (!response.ok) {
            return;
        }

        banners.value = (await response.json()) as Banner[];
        bannersLoaded.value = true;
    };

    const ensureCart = async () => {
        if (cartLoaded.value) return;
        await loadCart();
    };

    const ensureBanners = async () => {
        if (bannersLoaded.value) return;
        await loadBanners();
    };

    return {
        cart,
        cartCount,
        banners,
        ensureCart,
        ensureBanners,
        loadCart,
        loadBanners,
    };
}

