<script setup>
import { computed, ref } from "vue";
import { Link, usePage, useForm } from "@inertiajs/vue3";

const showMobileMenu = ref(false);
const showUserMenu = ref(false);

const page = usePage();
const user = page.props.auth?.user;
const orderTypes = [
    { label: "Digitizing", value: "digitizing" },
    { label: "Vector", value: "vector" },
    { label: "Patch", value: "patch" },
];
const queryParams = computed(() => {
    const parts = page.url?.split("?") ?? [];
    const params = new URLSearchParams(parts[1] ?? "");
    return {
        type: params.get("type"),
        quote: params.get("quote") === "1",
    };
});
const currentType = computed(() => {
    if (queryParams.value.quote) {
        return null;
    }
    const type = queryParams.value.type;
    return type && type !== "all" ? type : null;
});
const isQuoteContext = computed(() => queryParams.value.quote);

const logoutForm = useForm({});

const logout = () => {
    logoutForm.post(route("logout"));
};
</script>

<template>
    <div class="min-h-screen bg-gradient-to-b from-[#f8fafc] via-white to-[#edf2f7] text-slate-900">
        <div class="flex min-h-screen flex-col">
            <!-- Navigation -->
            <nav class="sticky top-0 z-30 border-b border-slate-200 bg-white/90 shadow-sm backdrop-blur">
                <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
                    <div class="flex items-center gap-8">
                        <Link :href="route('dashboard')" class="text-xl font-semibold tracking-tight text-slate-900">
                            Digitizing Workflow
                        </Link>

                        <div class="hidden items-center gap-1 text-sm font-medium sm:flex">
                            <Link
                                :href="route('dashboard')"
                                class="inline-flex items-center rounded-full px-3 py-1.5 transition"
                                :class="
                                    route().current('dashboard')
                                        ? 'bg-indigo-100 text-indigo-700'
                                        : 'text-slate-500 hover:bg-slate-100 hover:text-slate-900'
                                "
                            >
                                Dashboard
                            </Link>

                            <template v-if="user?.is_admin || user?.is_manager || user?.is_designer">
                                <Link
                                    :href="route('orders.index')"
                                    class="inline-flex items-center rounded-full px-3 py-1.5 transition"
                                    :class="
                                        route().current('orders.index') && !currentType && !isQuoteContext
                                            ? 'bg-indigo-100 text-indigo-700'
                                            : 'text-slate-500 hover:bg-slate-100 hover:text-slate-900'
                                    "
                                >
                                    All Orders
                                </Link>
                                <Link
                                    :href="route('orders.index', { quote: 1 })"
                                    class="inline-flex items-center rounded-full px-3 py-1.5 transition"
                                    :class="
                                        route().current('orders.index') && isQuoteContext
                                            ? 'bg-indigo-100 text-indigo-700'
                                            : 'text-slate-500 hover:bg-slate-100 hover:text-slate-900'
                                    "
                                >
                                    Quotes
                                </Link>
                                <Link
                                    v-for="type in orderTypes"
                                    :key="'desktop-orders-' + type.value"
                                    :href="route('orders.index', { type: type.value })"
                                    class="inline-flex items-center rounded-full px-3 py-1.5 capitalize transition"
                                    :class="
                                        route().current('orders.index') && !isQuoteContext && currentType === type.value
                                            ? 'bg-indigo-100 text-indigo-700'
                                            : 'text-slate-500 hover:bg-slate-100 hover:text-slate-900'
                                    "
                                >
                                    {{ type.label }}
                                </Link>
                            </template>
                            <Link
                                v-if="user?.is_designer"
                                :href="route('designer.dashboard')"
                                class="inline-flex items-center rounded-full px-3 py-1.5 transition"
                                :class="
                                    route().current('designer.dashboard')
                                        ? 'bg-indigo-100 text-indigo-700'
                                        : 'text-slate-500 hover:bg-slate-100 hover:text-slate-900'
                                "
                            >
                                My Work
                            </Link>
                            <Link
                                v-if="user?.is_admin || user?.is_manager"
                                :href="route('clients.index')"
                                class="inline-flex items-center rounded-full px-3 py-1.5 transition"
                                :class="
                                    route().current('clients.*')
                                        ? 'bg-indigo-100 text-indigo-700'
                                        : 'text-slate-500 hover:bg-slate-100 hover:text-slate-900'
                                "
                            >
                                Clients
                            </Link>
                            <Link
                                v-if="user?.is_admin"
                                :href="route('users.index')"
                                class="inline-flex items-center rounded-full px-3 py-1.5 transition"
                                :class="
                                    route().current('users.*')
                                        ? 'bg-indigo-100 text-indigo-700'
                                        : 'text-slate-500 hover:bg-slate-100 hover:text-slate-900'
                                "
                            >
                                Users
                            </Link>
                            <Link
                                v-if="user?.is_admin"
                                :href="route('settings.edit')"
                                class="inline-flex items-center rounded-full px-3 py-1.5 transition"
                                :class="
                                    route().current('settings.*')
                                        ? 'bg-indigo-100 text-indigo-700'
                                        : 'text-slate-500 hover:bg-slate-100 hover:text-slate-900'
                                "
                            >
                                Settings
                            </Link>
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="hidden sm:flex sm:items-center sm:gap-3">
                            <div class="text-right">
                                <p class="text-sm font-semibold text-slate-900">{{ user?.name }}</p>
                                <p class="text-xs text-slate-500">{{ user?.email }}</p>
                            </div>
                            <div class="relative">
                                <button
                                    @click="showUserMenu = !showUserMenu"
                                    class="flex items-center rounded-full border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:border-indigo-200 hover:text-indigo-600"
                                >
                                    Menu
                                    <svg class="ml-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>

                                <div
                                    v-show="showUserMenu"
                                    @click="showUserMenu = false"
                                    class="fixed inset-0 z-10"
                                ></div>

                                <div
                                    v-show="showUserMenu"
                                    class="absolute right-0 z-20 mt-2 w-52 rounded-2xl border border-slate-100 bg-white py-2 shadow-xl"
                                >
                                    <button
                                        @click="logout"
                                        class="block w-full px-4 py-2 text-left text-sm text-slate-600 transition hover:bg-slate-50"
                                    >
                                        Logout
                                    </button>
                                </div>
                            </div>
                        </div>

                        <button
                            @click="showMobileMenu = !showMobileMenu"
                            class="inline-flex items-center justify-center rounded-full border border-slate-200 p-2 text-slate-500 transition hover:border-indigo-200 hover:text-indigo-500 sm:hidden"
                        >
                            <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                <path
                                    :class="{ hidden: showMobileMenu, 'inline-flex': !showMobileMenu }"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M4 6h16M4 12h16M4 18h16"
                                />
                                <path
                                    :class="{ hidden: !showMobileMenu, 'inline-flex': showMobileMenu }"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Mobile menu -->
                <div :class="{ block: showMobileMenu, hidden: !showMobileMenu }" class="border-t border-slate-200 sm:hidden">
                    <div class="space-y-1 px-4 py-4">
                        <Link
                            :href="route('dashboard')"
                            class="block rounded-lg px-4 py-2 text-base font-medium"
                            :class="
                                route().current('dashboard')
                                    ? 'bg-indigo-50 text-indigo-700'
                                    : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
                            "
                        >
                            Dashboard
                        </Link>
                        <div v-if="user?.is_admin || user?.is_manager || user?.is_designer" class="space-y-1 border-t border-slate-200 pt-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Orders</p>
                            <Link
                                :href="route('orders.index')"
                                class="block rounded-lg px-4 py-2 text-base font-medium"
                                :class="
                                    route().current('orders.index') && !currentType && !isQuoteContext
                                        ? 'bg-indigo-50 text-indigo-700'
                                        : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
                                "
                            >
                                All orders
                            </Link>
                            <Link
                                :href="route('orders.index', { quote: 1 })"
                                class="block rounded-lg px-4 py-2 text-base font-medium"
                                :class="
                                    route().current('orders.index') && isQuoteContext
                                        ? 'bg-indigo-50 text-indigo-700'
                                        : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
                                "
                            >
                                Quotes
                            </Link>
                            <Link
                                v-for="type in orderTypes"
                                :key="'mobile-filter-' + type.value"
                                :href="route('orders.index', { type: type.value })"
                                class="block rounded-lg px-4 py-2 text-base font-medium capitalize"
                                :class="
                                    route().current('orders.index') && !isQuoteContext && currentType === type.value
                                        ? 'bg-indigo-50 text-indigo-700'
                                        : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
                                "
                            >
                                {{ type.label }} orders
                            </Link>
                            <div class="pt-4 text-xs font-semibold uppercase tracking-wide text-slate-400">Create</div>
                            <Link
                                v-for="type in orderTypes"
                                :key="'mobile-create-' + type.value"
                                :href="route('orders.create', { type: type.value, quote: isQuoteContext ? 1 : 0 })"
                                class="block rounded-lg px-4 py-2 text-base font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-900"
                            >
                                New {{ type.label }} {{ isQuoteContext ? "quote" : "order" }}
                            </Link>
                        </div>
                        <Link
                            v-if="user?.is_designer"
                            :href="route('designer.dashboard')"
                            class="block rounded-lg px-4 py-2 text-base font-medium"
                            :class="
                                route().current('designer.dashboard')
                                    ? 'bg-indigo-50 text-indigo-700'
                                    : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
                            "
                        >
                            My Work
                        </Link>
                        <Link
                            v-if="user?.is_admin || user?.is_manager"
                            :href="route('clients.index')"
                            class="block rounded-lg px-4 py-2 text-base font-medium"
                            :class="
                                route().current('clients.*')
                                    ? 'bg-indigo-50 text-indigo-700'
                                    : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
                            "
                        >
                            Clients
                        </Link>
                        <Link
                            v-if="user?.is_admin"
                            :href="route('users.index')"
                            class="block rounded-lg px-4 py-2 text-base font-medium"
                            :class="
                                route().current('users.*')
                                    ? 'bg-indigo-50 text-indigo-700'
                                    : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
                            "
                        >
                            Users
                        </Link>
                        <Link
                            v-if="user?.is_admin"
                            :href="route('settings.edit')"
                            class="block rounded-lg px-4 py-2 text-base font-medium"
                            :class="
                                route().current('settings.*')
                                    ? 'bg-indigo-50 text-indigo-700'
                                    : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
                            "
                        >
                            Settings
                        </Link>
                    </div>
                    <div class="border-t border-slate-200 px-4 py-4 text-sm">
                        <p class="font-medium text-slate-900">{{ user?.name }}</p>
                        <p class="text-slate-500">{{ user?.email }}</p>
                        <button
                            @click="logout"
                            class="mt-3 inline-flex w-full items-center justify-center rounded-xl border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 transition hover:border-indigo-200 hover:text-indigo-600"
                        >
                            Logout
                        </button>
                    </div>
                </div>
            </nav>

            <!-- Page Heading -->
            <header v-if="$slots.header" class="border-b border-slate-200 bg-white/70">
                <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 px-4 py-8 sm:px-6 lg:px-10">
                <slot />
            </main>
        </div>
    </div>
</template>
