<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

type Banner = {
    id: number;
    title: string;
    subtitle: string | null;
    image_path: string | null;
    cta_text: string | null;
    cta_url: string | null;
};

const props = withDefaults(
    defineProps<{
        banners: Banner[];
        autoplayMs?: number;
    }>(),
    {
        autoplayMs: 5000,
    },
);

const activeIndex = ref(0);
const hovering = ref(false);
let timer: number | undefined;

const slides = computed(() => props.banners.filter((b: Banner) => b.title));

const imageUrl = (path?: string | null) => {
    if (!path) {
        return 'https://placehold.co/1600x600/0f172a/e2e8f0?text=Add+a+banner+image';
    }

    return `/storage/${path}`;
};

const go = (index: number) => {
    const count = slides.value.length;
    if (!count) return;
    activeIndex.value = (index + count) % count;
};

const next = () => go(activeIndex.value + 1);
const prev = () => go(activeIndex.value - 1);

const clear = () => {
    if (timer) {
        window.clearInterval(timer);
        timer = undefined;
    }
};

const start = () => {
    clear();
    if (props.autoplayMs <= 0 || slides.value.length <= 1) return;
    timer = window.setInterval(() => {
        if (!hovering.value) next();
    }, props.autoplayMs);
};

watch(
    () => slides.value.length,
    () => {
        activeIndex.value = 0;
        start();
    },
);

onMounted(() => start());
onBeforeUnmount(() => clear());
</script>

<template>
    <div
        class="relative overflow-hidden rounded-2xl bg-slate-950 shadow-[0_40px_120px_-65px_rgba(15,23,42,0.75)]"
        @mouseenter="hovering = true"
        @mouseleave="hovering = false"
    >
        <div class="relative h-[220px] sm:h-[320px] lg:h-[420px]">
            <div
                v-for="(banner, idx) in slides"
                :key="banner.id"
                class="absolute inset-0 transition-opacity duration-700"
                :class="idx === activeIndex ? 'opacity-100' : 'pointer-events-none opacity-0'"
            >
                <img :src="imageUrl(banner.image_path)" :alt="banner.title" class="h-full w-full object-cover opacity-85" />
                <div class="absolute inset-0 bg-gradient-to-r from-slate-950/85 via-slate-950/35 to-slate-950/15" />

                <div class="absolute inset-0 flex items-center">
                    <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
                        <div class="max-w-2xl space-y-4">
                            <p class="inline-flex items-center rounded-full bg-white/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.25em] text-white/90">
                                Official store
                            </p>
                            <h1 class="text-3xl font-semibold tracking-tight text-white sm:text-4xl lg:text-5xl">
                                {{ banner.title }}
                            </h1>
                            <p v-if="banner.subtitle" class="max-w-xl text-sm leading-6 text-slate-200 sm:text-base">
                                {{ banner.subtitle }}
                            </p>
                            <div class="flex flex-wrap gap-3 pt-1">
                                <a
                                    v-if="banner.cta_text && banner.cta_url"
                                    :href="banner.cta_url"
                                    class="inline-flex items-center rounded-xl bg-amber-400 px-5 py-3 text-sm font-semibold text-slate-950 transition hover:bg-amber-300"
                                >
                                    {{ banner.cta_text }}
                                </a>
                                <a href="#catalog" class="inline-flex items-center rounded-xl bg-white/10 px-5 py-3 text-sm font-semibold text-white transition hover:bg-white/15">
                                    Browse products
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="slides.length > 1" class="absolute inset-y-0 left-0 right-0 flex items-center justify-between px-3">
            <Button
                variant="outline"
                class="h-10 w-10 rounded-full border-white/15 bg-white/10 p-0 text-white hover:bg-white/15"
                @click="prev"
            >
                <ChevronLeft class="h-5 w-5" />
            </Button>
            <Button
                variant="outline"
                class="h-10 w-10 rounded-full border-white/15 bg-white/10 p-0 text-white hover:bg-white/15"
                @click="next"
            >
                <ChevronRight class="h-5 w-5" />
            </Button>
        </div>

        <div v-if="slides.length > 1" class="absolute bottom-4 left-0 right-0 flex items-center justify-center gap-2">
            <button
                v-for="(banner, idx) in slides"
                :key="banner.id"
                type="button"
                :class="
                    cn(
                        'h-2.5 w-2.5 rounded-full border border-white/35 transition',
                        idx === activeIndex ? 'bg-white' : 'bg-white/30 hover:bg-white/50',
                    )
                "
                @click="go(idx)"
            />
        </div>
    </div>
</template>

