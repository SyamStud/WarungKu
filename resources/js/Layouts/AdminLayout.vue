<template>
    <div class="flex flex-col w-full">
        <!-- NAV DESKTOP -->
        <div class="hidden xl:flex flex-col w-full fixed top-0 z-50">
            <!-- Navigation Tabs -->
            <nav class="border-b bg-[#2A629A] shadow-md flex justify-between items-center">
                <ul class="flex">
                    <li v-if="!isInputStaff" class="relative">
                        <Link href="/pos">
                        <button :class="[
                            'text-sm px-6 py-3 transition-all duration-150 ease-in-out focus:outline-none hover:bg-gray-200 hover:text-gray-900 flex gap-2 items-center',
                            activeTab === 'POS' ? 'bg-gray-100 !text-black' : 'text-white'
                        ]">
                            <img width="20" height="20" src="https://img.icons8.com/color/48/print.png" alt="print" />
                            Halaman Kasir
                        </button>
                        </Link>
                    </li>

                    <li v-for="tab in tabs" :key="tab.name" class="relative">
                        <button @click="setActiveTab(tab.name)" :class="[
                            'text-sm px-6 py-3 transition-all duration-150 ease-in-out focus:outline-none hover:bg-gray-200 hover:text-gray-900 flex gap-2 items-center',
                            activeTab === tab.name ? 'bg-gray-100 !text-black' : 'text-white'
                        ]">
                            <template v-if="tab.imageSrc">
                                <img :src="tab.imageSrc" :alt="tab.name" class="h-5 w-5 group-hover:opacity-80" />
                            </template>
                            {{ tab.name }}
                        </button>
                    </li>

                    <li class="relative">
                        <Link href="/logout" method="POST">
                        <button :class="[
                            'text-sm px-6 py-3 transition-all duration-150 ease-in-out focus:outline-none hover:bg-gray-200 hover:text-gray-900 flex gap-2 items-center',
                            activeTab === 'POS' ? 'bg-gray-100 !text-black' : 'text-white'
                        ]">
                            <img width="20" height="20"
                                src="https://img.icons8.com/?size=100&id=13925&format=png&color=000000" alt="print" />
                            Logout
                        </button>
                        </Link>
                    </li>
                </ul>
            </nav>
            <div class="relative hidden 2xl:block">
                <!-- Tools Bar -->
                <Transition name="slide-fade">
                    <div :class="[
                        'toolbar-container flex justify-between items-center space-x-4 py-4 px-6 border-b bg-gray-100 shadow-sm transition-all duration-500 ease-in-out',
                        isToolbarOpen ? 'max-h-40 opacity-100' : 'max-h-10 opacity-100 overflow-hidden'
                    ]">
                        <div class="flex items-center space-x-4 flex-wrap">
                            <Link v-for="tool in activeTabTools" :key="tool.name" :href="tool.link"
                                @click="handleToolClick(tool)"
                                class="flex items-center px-3 py-2 transition-all duration-300 ease-in-out text-gray-600 hover:bg-gray-300 hover:text-gray-900 rounded-md group">
                            <template v-if="tool.imageSrc">
                                <img :src="tool.imageSrc" :alt="tool.name" class="h-6 w-6 group-hover:opacity-80" />
                            </template>
                            <template v-else>
                                <component :is="tool.icon" class="h-5 w-5 group-hover:text-gray-900" />
                            </template>
                            <Transition name="fade-slide">
                                <span v-if="isToolbarName" class="ml-2 hidden md:inline-block text-sm">{{ tool.name
                                    }}</span>
                            </Transition>
                            </Link>
                        </div>
                    </div>
                </Transition>

                <!-- Toggle Button with Curve -->
                <div v-if="path !== '/admin/dashboard'" class="absolute right-0 bottom-0 transform translate-y-full">
                    <div class="bg-[#2A629A] rounded-b-lg shadow-md w-20 text-center cursor-pointer"
                        @click="toggleToolbar">
                        <button class="p-2 focus:outline-none">
                            <svg :class="['w-4 h-3 transition-transform duration-300', isToolbarOpen ? 'transform rotate-180' : '']"
                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="white">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- NAV MOBILE -->
        <div class="2xl:hidden flex flex-col w-full fixed top-0 z-50">
            <nav class="border-b bg-[#2A629A] shadow-md flex justify-between items-center p-4">
                <div class="text-white font-bold text-lg">
                    <Link href="/pos">POS</Link>
                </div>

                <button @click="toggleMenu" class="2xl:hidden">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16m-7 6h7"></path>
                    </svg>
                </button>

                <ul :class="['flex flex-col 2xl:flex-row items-start 2xl:items-center w-full 2xl:w-auto 2xl:space-x-4 bg-[#2A629A] 2xl:bg-transparent 2xl:static', isMenuOpen ? 'block' : 'hidden 2xl:flex']"
                    class="absolute 2xl:relative left-0 2xl:left-auto top-14 2xl:top-auto z-10 2xl:z-auto space-y-2 2xl:space-y-0 w-full 2xl:w-auto">

                    <!-- Mobile Toggle Tabs -->
                    <li v-for="tab in tabs" :key="tab.name" class="relative w-full 2xl:w-auto">
                        <button @click="toggleActiveTab(tab.name)"
                            :class="['w-full text-sm px-6 py-3 focus:outline-none hover:bg-gray-200 hover:text-gray-900 flex gap-2 items-center 2xl:inline-block', activeMobileTab === tab.name ? 'bg-gray-100 text-black' : 'text-white']">
                            <template v-if="tab.imageSrc">
                                <img :src="tab.imageSrc" :alt="tab.name" class="h-5 w-5 group-hover:opacity-80" />
                            </template>
                            {{ tab.name }}
                        </button>

                        <!-- Show Tools when Tab is Active in Mobile -->
                        <div v-show="activeMobileTab === tab.name && isMenuOpen" class="bg-gray-300 p-2">
                            <ul class="space-y-5">
                                <li v-for="tool in tab.tools" :key="tool.name" class="py-1">
                                    <Link :href="tool.link"
                                        class="text-gray-700 hover:text-gray-900 ps-12 text-sm flex items-center gap-2">
                                    <template v-if="tool.imageSrc">
                                        <img :src="tool.imageSrc" :alt="tool.name"
                                            class="h-5 w-5 group-hover:opacity-80" />
                                    </template>
                                    {{ tool.name }}
                                    </Link>
                                </li>
                            </ul>
                        </div>
                    </li>

                    <li class="relative w-full 2xl:w-auto">
                        <Link href="/logout" method="POST">
                        <button :class="[
                            'w-full text-sm px-6 py-3 transition-all duration-150 ease-in-out focus:outline-none hover:bg-gray-200 hover:text-gray-900 flex gap-2 items-center',
                            activeTab === 'POS' ? 'bg-gray-100 !text-black' : 'text-white'
                        ]">
                            <img width="20" height="20"
                                src="https://img.icons8.com/?size=100&id=13925&format=png&color=000000" alt="print" />
                            Logout
                        </button>
                        </Link>
                    </li>
                </ul>
            </nav>
        </div>

        <!-- Content Section -->
        <Transition name="fade">
            <div :class="['p-8 mb-10 transition-all ease-in-out', contentMarginClass]">
                <slot />
            </div>
        </Transition>
    </div>

    <!-- Footer Section -->
    <footer class="bg-gray-100 border-t fixed bottom-0 w-full shadow-md">
        <div class="flex justify-center items-center p-2 w-full text-center space-x-1">
            <p class="text-xs text-gray-500">
                © 2024 - <span class="font-semibold">Product Activated</span> -
            </p>
            <a href="https://icons8.com/" class="text-xs text-blue-500 hover:underline hover:text-blue-600">
                Icons by Icons8
            </a>
        </div>
    </footer>

</template>

<script setup>
import { ref, computed, onMounted, watch, reactive } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();

const { props } = usePage();
const user = reactive(props.auth.user);

const isAdmin = ref(false);
const isInputStaff = ref(false);

const isToolbarOpen = ref(true);
const isToolbarName = ref(true);

const toggleToolbar = () => {
    if (!isToolbarOpen.value) {
        isToolbarOpen.value = !isToolbarOpen.value;
        setTimeout(() => {
            isToolbarName.value = true;
        }, 200);
    } else {
        isToolbarName.value = !isToolbarName.value;
        setTimeout(() => {
            isToolbarOpen.value = !isToolbarOpen.value;
        }, 300);
    }
};

const isMenuOpen = ref(false); // State for mobile menu
const activeMobileTab = ref(''); // State for active mobile tab

const toggleMenu = () => {
    isMenuOpen.value = !isMenuOpen.value;
};

const toggleActiveTab = (tabName) => {
    if (activeMobileTab.value === tabName) {
        activeMobileTab.value = ''; // Close tab if already open
    } else {
        activeMobileTab.value = tabName; // Open the selected tab
    }
};

const contentMarginClass = computed(() => {
    if (isToolbarOpen.value) {
        return isToolbarOpen.value ? 'duration-300 xl:mt-28 mt-16' : 'xl:mt-20 mt-16';
    }
    return 'duration-300 delay-200 xl:mt-20 mt-16';
});

const tabs = computed(() => {
    if (isInputStaff.value) {
        return [
            {
                name: 'Produk',
                imageSrc: 'https://img.icons8.com/?size=100&id=12034&format=png&color=000000',
                tools: [
                    { link: '/admin/products', name: 'Produk', imageSrc: 'https://img.icons8.com/?size=100&id=12034&format=png&color=000000' },
                    { link: '/admin/product-variants', name: 'Variasi Produk', imageSrc: 'https://img.icons8.com/?size=100&id=12034&format=png&color=000000' },
                    { link: '/admin/categories', name: 'Kategori', imageSrc: 'https://img.icons8.com/?size=100&id=XnHBz2LnhELw&format=png&color=000000' },
                    { link: '/admin/units', name: 'Unit', imageSrc: 'https://img.icons8.com/?size=100&id=12927&format=png&color=000000' },
                ]
            }
        ];
    }
    return [
        {
            name: 'Dashboard',
            imageSrc: 'https://img.icons8.com/?size=100&id=PuBdQTrFqm0K&format=png&color=000000',
            tools: [
                { link: "/admin/dashboard", name: 'Dashboard', imageSrc: 'https://img.icons8.com/?size=100&id=PuBdQTrFqm0K&format=png&color=000000' },
            ]
        },
        {
            name: 'Transaksi',
            imageSrc: 'https://img.icons8.com/?size=100&id=13009&format=png&color=000000',
            tools: [
                // { link: "/admin/carts", name: 'Transaksi Sementara', imageSrc: 'https://img.icons8.com/?size=100&id=18982&format=png&color=000000' },
                // { link: "/admin/cart-items", name: 'Item Transaksi Sementara', imageSrc: 'https://img.icons8.com/?size=100&id=12034&format=png&color=000000' },
                { link: "/admin/transactions", name: 'Transaksi', imageSrc: 'https://img.icons8.com/?size=100&id=63724&format=png&color=000000' },
                { link: "/admin/transaction-items", name: 'Item Transaksi', imageSrc: 'https://img.icons8.com/?size=100&id=13123&format=png&color=000000' },
                { link: "/admin/debts", name: 'Hutang', imageSrc: 'https://img.icons8.com/?size=100&id=tcIzvG8b1BjB&format=png&color=000000' },
                { link: "/admin/debt-items", name: 'Item Hutang', imageSrc: 'https://img.icons8.com/?size=100&id=13229&format=png&color=000000' },
                { link: "/admin/debt-payment-history", name: 'Riwayat Pembayaran Hutang', imageSrc: 'https://img.icons8.com/?size=100&id=NBus9BZohddY&format=png&color=000000' },
            ]
        },
        {
            name: 'Produk',
            imageSrc: 'https://img.icons8.com/?size=100&id=12034&format=png&color=000000',
            tools: [
                { link: '/admin/products', name: 'Produk', imageSrc: 'https://img.icons8.com/?size=100&id=12034&format=png&color=000000' },
                { link: '/admin/product-variants', name: 'Variasi Produk', imageSrc: 'https://img.icons8.com/?size=100&id=12034&format=png&color=000000' },
                { link: '/admin/restocks', name: 'Restock', imageSrc: 'https://img.icons8.com/?size=100&id=VWrzCw0rvxVx&format=png&color=000000' },
                { link: '/admin/stock-movements', name: 'Riwayat Stok', imageSrc: 'https://img.icons8.com/?size=100&id=18971&format=png&color=000000' },
                { link: '/admin/categories', name: 'Kategori', imageSrc: 'https://img.icons8.com/?size=100&id=XnHBz2LnhELw&format=png&color=000000' },
                { link: '/admin/units', name: 'Unit', imageSrc: 'https://img.icons8.com/?size=100&id=12927&format=png&color=000000' },
                { link: '/admin/discounts', name: 'Diskon', imageSrc: 'https://img.icons8.com/?size=100&id=63761&format=png&color=000000' },
                { link: '/admin/discount-products', name: 'Produk Diskon', imageSrc: 'https://img.icons8.com/?size=100&id=yasXRs9W9T8i&format=png&color=000000' },
            ]
        },
        {
            name: 'Pengguna',
            imageSrc: 'https://img.icons8.com/?size=100&id=13042&format=png&color=000000',
            tools: [
                { link: '/admin/customers', name: 'Pelanggan', imageSrc: 'https://img.icons8.com/?size=100&id=23301&format=png&color=000000' },
                { link: '/admin/users', name: 'Admin & Pegawai', imageSrc: 'https://img.icons8.com/?size=100&id=108294&format=png&color=000000' },
            ]
        },
        {
            name: 'Laporan',
            imageSrc: 'https://img.icons8.com/?size=100&id=13532&format=png&color=000000',
            tools: [
                { link: "/admin/reports/transaction", name: 'Laporan Penjualan', imageSrc: 'https://img.icons8.com/?size=100&id=103978&format=png&color=000000' },
                // { link: "/admin/reports/purchase", name: 'Laporan Pembelian', imageSrc: 'https://img.icons8.com/?size=100&id=103978&format=png&color=000000' },
            ]
        },
        {
            name: 'Pengaturan',
            imageSrc: 'https://img.icons8.com/?size=100&id=12784&format=png&color=000000',
            tools: [
                { link: "/admin/store-settings", name: 'Pengaturan Toko', imageSrc: 'https://img.icons8.com/?size=100&id=18901&format=png&color=000000' },
                { link: "/settings", name: 'Pengaturan Pengguna', imageSrc: 'https://img.icons8.com/?size=100&id=13042&format=png&color=000000' },
            ]
        }
    ];
});

const activeTab = ref('Beranda');

const setActiveTab = (tabName) => {
    activeTab.value = tabName;
    localStorage.setItem('activeTab', tabName);
};

const activeTabTools = computed(() => {
    const currentTab = tabs.value.find(tab => tab.name === activeTab.value);
    return currentTab ? currentTab.tools : [];
});

const handleToolClick = (tool) => {
    const parentTab = tabs.value.find(tab => tab.tools.some(t => t.name === tool.name));
    if (parentTab) {
        setActiveTab(parentTab.name);
    }
};

const path = ref('');

onMounted(() => {
    const savedTab = localStorage.getItem('activeTab');
    if (savedTab) {
        activeTab.value = savedTab;
    }

    path.value = page.url;

    if (user.roles[0].name === 'admin') {
        isAdmin.value = true;
    } else {
        isAdmin.value = false;
    }

    if (user.roles[0].name === 'input-staff') {
        isInputStaff.value = true;
    } else {
        isInputStaff.value = false;
    }

    if (isInputStaff.value) {
        activeTab.value = 'Produk';
    }
});

watch(() => page.url, () => {
    const savedTab = localStorage.getItem('activeTab');
    if (savedTab) {
        activeTab.value = savedTab;
    }
}, { immediate: true });

</script>

<style scoped>
button:focus {
    outline: none;
}

img {
    transition: opacity 2s ease-in-out;
}
</style>

<style scoped>
button:focus {
    outline: none;
}

img {
    transition: opacity 0.3s ease-in-out;
    /* Faster for better user experience */
}

.toolbar-container {
    border-radius: 8px;
    /* Rounded corners for a softer look */
    background: linear-gradient(to bottom, #e0e7ff, #f8fafc);
    /* Gradient background */
}

/* Updated Transition Styles */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
    /* Smoother transition */
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.slide-fade-enter-active,
.slide-fade-leave-active {
    transition: all 0.3s ease;
    /* Reduced duration for smoother effect */
}

.slide-fade-enter-from,
.slide-fade-leave-to {
    transform: translateY(-10px);
    opacity: 0;
}

.fade-slide-enter-active,
.fade-slide-leave-active {
    transition: all 0.3s ease;
    /* Consistent duration */
}

.fade-slide-enter-from,
.fade-slide-leave-to {
    transform: translateX(-10px);
    opacity: 0;
}
</style>
