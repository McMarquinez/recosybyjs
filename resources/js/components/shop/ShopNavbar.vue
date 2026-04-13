<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { cn } from '@/lib/utils';
import type { SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { ChevronDown, PackageSearch, ShoppingCart, User } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<{
    cartCount: number;
    categories: string[];
}>();

const page = usePage<SharedData>();
const user = computed(() => page.props.auth.user);

const query = ref('');

const goSearch = () => {
    const value = query.value.trim();
    if (!value) return;

    const el = document.getElementById('catalog');
    if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });

    window.dispatchEvent(new CustomEvent('shop:search', { detail: { query: value } }));
};
</script>

<template>
    <div class="sticky top-0 z-40 border-b border-slate-200 bg-white/85 backdrop-blur">
        <div class="mx-auto w-full max-w-7xl px-4 py-3 sm:px-6 lg:px-8">
            <div class="flex items-center gap-3">
                <Link :href="route('home')" class="flex items-center gap-2">
                    <div class="grid h-9 w-9 place-items-center rounded-xl bg-slate-950 text-white">
                        <PackageSearch class="h-5 w-5" />
                    </div>
                    <div class="hidden leading-tight sm:block">
                        <p class="text-sm font-semibold text-slate-900">Logicbox</p>
                        <p class="text-xs text-slate-500">Shop</p>
                    </div>
                </Link>

                <div class="hidden lg:block">
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button variant="outline" class="rounded-xl border-slate-300 bg-white">
                                Categories
                                <ChevronDown class="ml-2 h-4 w-4" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent class="w-56">
                            <DropdownMenuLabel>Browse</DropdownMenuLabel>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem v-if="!props.categories.length" disabled>No categories yet</DropdownMenuItem>
                            <DropdownMenuItem v-for="category in props.categories" :key="category" as-child>
                                <a :href="`#category-${encodeURIComponent(category)}`">{{ category }}</a>
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>

                <div class="flex min-w-0 flex-1 items-center gap-2">
                    <div class="relative w-full">
                        <Input
                            v-model="query"
                            class="h-10 rounded-xl border-slate-300 bg-white pl-10"
                            placeholder="Search products (name, description, SKU)"
                            @keydown.enter.prevent="goSearch"
                        />
                        <PackageSearch class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" />
                    </div>
                    <Button class="hidden h-10 rounded-xl bg-slate-950 text-white hover:bg-slate-800 sm:inline-flex" @click="goSearch">Search</Button>
                </div>

                <div class="flex items-center gap-2">
                    <Link :href="route('shop.cart')" class="relative">
                        <Button variant="outline" class="h-10 rounded-xl border-slate-300 bg-white">
                            <ShoppingCart class="mr-2 h-4 w-4" />
                            <span class="hidden sm:inline">Cart</span>
                        </Button>
                        <span
                            v-if="props.cartCount > 0"
                            :class="cn('absolute -right-1 -top-1 grid h-5 min-w-5 place-items-center rounded-full bg-amber-400 px-1 text-xs font-semibold text-slate-950')"
                        >
                            {{ props.cartCount > 99 ? '99+' : props.cartCount }}
                        </span>
                    </Link>

                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button variant="outline" class="h-10 rounded-xl border-slate-300 bg-white">
                                <User class="mr-2 h-4 w-4" />
                                <span class="hidden sm:inline">{{ user ? 'Account' : 'Login' }}</span>
                                <ChevronDown class="ml-2 h-4 w-4" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent class="w-56">
                            <template v-if="user">
                                <DropdownMenuLabel class="truncate">{{ user.name }}</DropdownMenuLabel>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem as-child>
                                    <Link :href="route('shop.orders')">My Orders</Link>
                                </DropdownMenuItem>
                                <DropdownMenuItem as-child>
                                    <Link :href="route('dashboard')">Dashboard</Link>
                                </DropdownMenuItem>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem as-child>
                                    <Link :href="route('logout')" method="post" as="button" class="w-full text-left">Logout</Link>
                                </DropdownMenuItem>
                            </template>
                            <template v-else>
                                <DropdownMenuLabel>Welcome</DropdownMenuLabel>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem as-child>
                                    <Link :href="route('login')">Login</Link>
                                </DropdownMenuItem>
                                <DropdownMenuItem as-child>
                                    <Link :href="route('register')">Create account</Link>
                                </DropdownMenuItem>
                            </template>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </div>
        </div>
    </div>
</template>

