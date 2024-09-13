<template>
    <div class="min-h-screen bg-gray-100">
        <!-- Navbar -->
        <NavAdmin @toggle-sidebar="openSidebar" />

        <!-- Sidebar -->
        <div class="flex">
            <!-- MOBILE  -->
            <Transition name="slide-fade">
                <div v-if="isSidebarOpen" class="fixed inset-0 flex z-40 md:hidden" role="dialog" aria-modal="true">
                    <Transition name="fade">
                        <div v-if="isBackgroundVisible" class="fixed inset-0 bg-gray-600 bg-opacity-75"
                            aria-hidden="true" @click="closeSidebar"></div>
                    </Transition>
                    <div class="relative flex-1 flex flex-col max-w-xs w-full pt-5 pb-4 bg-white">
                        <div class="absolute top-0 right-0 -mr-12 pt-2">
                            <button @click="closeSidebar"
                                class="ml-1 flex items-center justify-center h-10 w-10 rounded-full focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white">
                                <span class="sr-only">Close sidebar</span>
                                <svg class="h-6 w-6 text-white" xmlns="http://www.w3.org/2000/svg" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                        <div class="flex-shrink-0 flex items-center px-4">
                            <img class="h-8 w-auto" src="/assets/logo-2.svg" alt="Workflow">
                        </div>
                        <div class="mt-5 flex-1 h-0 overflow-y-auto">
                            <nav class="px-2 space-y-1">
                                <Link href="/dashboard"
                                    :class="[windowLocation == '/dashboard' ? 'ps-4 bg-gray-100 text-gray-900' : 'ps-4 text-gray-600 hover:bg-gray-50 hover:text-gray-900', 'gap-3 group flex items-center px-2 py-2 text-sm font-medium rounded-md']">
                                <svg xmlns="http://www.w3.org/2000/svg" width="1.7em" height="1.7em"
                                    viewBox="0 0 24 24">
                                    <path fill="none" stroke="currentColor" stroke-linecap="round"
                                        stroke-linejoin="round" stroke-width="2"
                                        d="M5 4h4a1 1 0 0 1 1 1v6a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1m0 12h4a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-2a1 1 0 0 1 1-1m10-4h4a1 1 0 0 1 1 1v6a1 1 0 0 1-1 1h-4a1 1 0 0 1-1-1v-6a1 1 0 0 1 1-1m0-8h4a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1h-4a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1" />
                                </svg>
                                Dashboard
                                </Link>
                                <div class="py-2 px-2 flex items-center">
                                    <p class="text-sm font-semibold text-gray-500">Tagihan</p>
                                    <hr class="mt-1 flex-grow ml-2 border-gray-300">
                                </div>
                                <Link v-for="item in currentNavigationItems1" :key="item.name" :href="item.href"
                                    :class="[item.current ? 'ps-4 bg-gray-100 text-gray-900' : 'ps-4 text-gray-600 hover:bg-gray-50 hover:text-gray-900', 'group flex items-center px-2 py-2 text-sm font-medium rounded-md']">
                                <Icon :icon="item.icon" :class="[
                                    item.current ? 'text-gray-500' : 'text-gray-400 group-hover:text-gray-500',
                                    'mr-3 flex-shrink-0 h-6 w-6'
                                ]" />
                                {{ item.name }}
                                </Link>
                                <div class="py-2 px-2 flex items-center">
                                    <p class="text-sm font-semibold text-gray-500">Data Master</p>
                                    <hr class="mt-1 flex-grow ml-2 border-gray-300">
                                </div>

                                <template v-for="item in currentNavigationItems2" :key="item.name">
                                    <Link v-if="item.type !== 'dropdown'" :href="item.href"
                                        :class="[item.current ? 'ps-4 bg-gray-100 text-gray-900' : 'ps-4 text-gray-600 hover:bg-gray-50 hover:text-gray-900', 'group flex items-center px-2 py-2 text-sm font-medium rounded-md']">
                                    <Icon :icon="item.icon"
                                        :class="[item.current ? 'text-gray-500' : 'text-gray-400 group-hover:text-gray-500', 'mr-3 flex-shrink-0 h-6 w-6']" />
                                    {{ item.name }}
                                    </Link>

                                    <DropdownMenu v-else>
                                        <DropdownMenuTrigger class="w-full">
                                            <Button
                                                :class="[item.current ? 'ps-4 bg-gray-100 hover:bg-gray-100 text-gray-900' : 'bg-white ps-4 text-gray-600 hover:bg-gray-50 hover:text-gray-900', 'w-full justify-start group flex items-center px-2 py-2 text-sm font-medium rounded-md']">
                                                <Icon :icon="item.icon"
                                                    :class="[item.current ? 'text-gray-500 group-hover:text-gray-500' : 'text-gray-400 group-hover:text-gray-500', 'mr-3 flex-shrink-0 h-6 w-6']" />
                                                {{ item.name }}
                                            </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent class="w-72 md:w-56">
                                            <DropdownMenuLabel>{{ item.name }}</DropdownMenuLabel>
                                            <DropdownMenuSeparator />
                                            <DropdownMenuItem v-for="subItem in item.children" :key="subItem.name"
                                                class="p-0">
                                                <Link class="w-full h-full p-2" :href="subItem.href">{{ subItem.name }}
                                                </Link>
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </template>
                            </nav>
                        </div>
                    </div>
                </div>
            </Transition>




            <div v-if="isSidebarOpen" class="hidden md:flex md:flex-shrink-0 bg-white min-h-screen fixed top-16">
                <div class="flex flex-col w-64">
                    <div class="flex flex-col flex-grow pb-4 overflow-y-auto">
                        <div class="md:hidden flex items-center flex-shrink-0 px-4">
                            <img class="h-8 w-auto" src="/assets/logo-2.svg" alt="Workflow">
                        </div>
                        <div class="mt-5 flex-1 flex flex-col">
                            <nav class="flex-1 px-2 space-y-1">
                                <Link href="/dashboard"
                                    :class="[windowLocation == '/dashboard' ? 'ps-4 bg-gray-100 text-gray-900' : 'ps-4 text-gray-600 hover:bg-gray-50 hover:text-gray-900', 'gap-3 group flex items-center px-2 py-2 text-sm font-medium rounded-md']">
                                <svg xmlns="http://www.w3.org/2000/svg" width="1.7em" height="1.7em"
                                    viewBox="0 0 24 24">
                                    <path fill="none" stroke="currentColor" stroke-linecap="round"
                                        stroke-linejoin="round" stroke-width="2"
                                        d="M5 4h4a1 1 0 0 1 1 1v6a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1m0 12h4a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-2a1 1 0 0 1 1-1m10-4h4a1 1 0 0 1 1 1v6a1 1 0 0 1-1 1h-4a1 1 0 0 1-1-1v-6a1 1 0 0 1 1-1m0-8h4a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1h-4a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1" />
                                </svg>
                                Dashboard
                                </Link>
                                <div class="py-2 px-2 flex items-center">
                                    <p class="text-sm font-semibold text-gray-500">Transaksi</p>
                                    <hr class="mt-1 flex-grow ml-2 border-gray-300">
                                </div>
                                <template v-for="item in currentNavigationItems1" :key="item.name">
                                    <Link v-if="item.type !== 'dropdown'" :href="item.href"
                                        :class="[item.current ? 'ps-4 bg-gray-100 text-gray-900' : 'ps-4 text-gray-600 hover:bg-gray-50 hover:text-gray-900', 'group flex items-center px-2 py-2 text-sm font-medium rounded-md']">
                                    <Icon :icon="item.icon"
                                        :class="[item.current ? 'text-gray-500' : 'text-gray-400 group-hover:text-gray-500', 'mr-3 flex-shrink-0 h-6 w-6']" />
                                    {{ item.name }}
                                    </Link>

                                    <DropdownMenu v-else>
                                        <DropdownMenuTrigger class="w-full">
                                            <Button
                                                :class="[item.current ? 'ps-4 bg-gray-100 hover:bg-gray-100 text-gray-900' : 'bg-white ps-4 text-gray-600 hover:bg-gray-50 hover:text-gray-900', 'w-full justify-start group flex items-center px-2 py-2 text-sm font-medium rounded-md']">
                                                <Icon :icon="item.icon"
                                                    :class="[item.current ? 'text-gray-500 group-hover:text-gray-500' : 'text-gray-400 group-hover:text-gray-500', 'mr-3 flex-shrink-0 h-6 w-6']" />
                                                {{ item.name }}
                                            </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent class="w-72 md:w-56">
                                            <DropdownMenuLabel>{{ item.name }}</DropdownMenuLabel>
                                            <DropdownMenuSeparator />
                                            <DropdownMenuItem v-for="subItem in item.children" :key="subItem.name"
                                                class="p-0">
                                                <Link class="w-full h-full p-2" :href="subItem.href">{{ subItem.name }}
                                                </Link>
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </template>
                                <div class="py-2 px-2 flex items-center">
                                    <p class="text-sm font-semibold text-gray-500">Data Master</p>
                                    <hr class="mt-1 flex-grow ml-2 border-gray-300">
                                </div>

                                <template v-for="item in currentNavigationItems2" :key="item.name">
                                    <Link v-if="item.type !== 'dropdown'" :href="item.href"
                                        :class="[item.current ? 'ps-4 bg-gray-100 text-gray-900' : 'ps-4 text-gray-600 hover:bg-gray-50 hover:text-gray-900', 'group flex items-center px-2 py-2 text-sm font-medium rounded-md']">
                                    <Icon :icon="item.icon"
                                        :class="[item.current ? 'text-gray-500' : 'text-gray-400 group-hover:text-gray-500', 'mr-3 flex-shrink-0 h-6 w-6']" />
                                    {{ item.name }}
                                    </Link>

                                    <DropdownMenu v-else>
                                        <DropdownMenuTrigger class="w-full">
                                            <Button
                                                :class="[item.current ? 'ps-4 bg-gray-100 hover:bg-gray-100 text-gray-900' : 'bg-white ps-4 text-gray-600 hover:bg-gray-50 hover:text-gray-900', 'w-full justify-start group flex items-center px-2 py-2 text-sm font-medium rounded-md']">
                                                <Icon :icon="item.icon"
                                                    :class="[item.current ? 'text-gray-500 group-hover:text-gray-500' : 'text-gray-400 group-hover:text-gray-500', 'mr-3 flex-shrink-0 h-6 w-6']" />
                                                {{ item.name }}
                                            </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent class="w-72 md:w-56">
                                            <DropdownMenuLabel>{{ item.name }}</DropdownMenuLabel>
                                            <DropdownMenuSeparator />
                                            <DropdownMenuItem v-for="subItem in item.children" :key="subItem.name"
                                                class="p-0">
                                                <Link class="w-full h-full p-2" :href="subItem.href">{{ subItem.name }}
                                                </Link>
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </template>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main content -->
            <div class="flex flex-col w-0 flex-1 overflow-hidden md:mt-16"
                :class="{ 'md:ms-64': isSidebarOpen, 'md:ms-0': !isSidebarOpen }">
                <main class="flex-1 relative overflow-y-auto focus:outline-none">
                    <div class="py-6">
                        <div class="mx-auto px-4 sm:px-6 md:px-8">
                            <slot />
                        </div>
                    </div>
                </main>
            </div>
        </div>
    </div>
</template>

<style scoped>
div[data-radix-popper-content-wrapper] {
    width: 100%;
}
</style>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import NavAdmin from './NavAdmin.vue';
import { Link } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu'
import Button from '@/Components/ui/button/Button.vue';

const isSidebarOpen = ref(window.innerWidth < 768 ? false : true);
const isProfileMenuOpen = ref(false);
const isBackgroundVisible = ref(false);
const windowLocation = ref('');

const toggleSidebar = () => {
    isSidebarOpen.value = !isSidebarOpen.value;
};

const closeSidebar = () => {
    isBackgroundVisible.value = false;
    setTimeout(() => {
        isSidebarOpen.value = false;
    }, 100);
};

const openSidebar = () => {
    isSidebarOpen.value = !isSidebarOpen.value;
    setTimeout(() => {
        isBackgroundVisible.value = true;
    }, 160);
};

defineExpose({ openSidebar });

watch(isSidebarOpen, (newValue) => {
    if (!newValue) {
        isBackgroundVisible.value = false;
    }
});

const toggleProfileMenu = () => {
    isProfileMenuOpen.value = !isProfileMenuOpen.value;
};

const currentPath = ref('');

// const windowLocation = window.location.pathname;

const navigationItems1 = [
    {
        name: 'Transaksi Sementara',
        type: 'dropdown',
        icon: 'tabler:shopping-cart-pause',
        children: [
            { name: 'Daftar transaksi Sementara', href: '/admin/carts' },
            { name: 'Item transaksi Sementara', href: '/admin/cart-items' },
        ]
    },
    {
        name: 'Transaksi',
        type: 'dropdown',
        icon: 'tabler:shopping-cart-copy',
        children: [
            { name: 'Daftar Transaksi', href: '/admin/transactions' },
            { name: 'Item Transaksi', href: '/admin/transaction-items' },
        ]
    },
];

const currentNavigationItems1 = computed(() => {
    return navigationItems1.map(item => ({
        ...item,
        current: currentPath.value === item.href
    }));
});

const navigationItems2 = [
    { name: 'Kategori', href: '/admin/categories', icon: 'tabler:category-plus' },
    { name: 'Produk', href: '/admin/products', icon: 'icon-park-outline:ad-product' },
    {
        name: 'Stok',
        type: 'dropdown',
        icon: 'gridicons:product',
        children: [
            { name: 'Daftar Stok', href: '/admin/stocks' },
            { name: 'Riwayat Stok', href: '/admin/stock-movements' }
        ]
    },
    { name: 'Pengguna', href: '/admin/users', icon: 'tdesign:user' },
    { name: 'Laporan', href: '/admin/laporan', icon: 'mdi:report-box-outline' },
];

const currentNavigationItems2 = computed(() => {
    return navigationItems2.map(item => ({
        ...item,
        current: item.type === 'dropdown'
            ? item.children.some(child => windowLocation.value === child.href)
            : windowLocation.value === item.href,
        children: item.children?.map(child => ({
            ...child,
            current: windowLocation.value === child.href
        }))
    }));
});

onMounted(() => {
    currentPath.value = window.location.pathname;
    windowLocation.value = window.location.pathname;
});

// Watch for changes in the URL (if user navigates without a full page reload)
window.addEventListener('popstate', () => {
    currentPath.value = window.location.pathname;
});
</script>

<style scoped>
.slide-fade-enter-active,
.slide-fade-leave-active {
    transition: all 0.2s ease-out;
}

.slide-fade-enter-from,
.slide-fade-leave-to {
    transform: translateX(-100%);
    opacity: 0;
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>