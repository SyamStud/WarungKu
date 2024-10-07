<template>
    <div class="flex flex-col w-full">
        <div class="flex flex-col w-full fixed top-0 z-50">
            <!-- Navigation Tabs -->
            <nav class="border-b bg-[#2A629A] shadow-md flex justify-between items-center">
                <ul class="flex">
                    <li class="relative">
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
                </ul>
            </nav>

            <div class="relative">
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
                <div v-if="path !== '/admin/dashboard'"
                    class="absolute right-0 bottom-0 transform translate-y-full">
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
import { ref, computed, onMounted, watch } from 'vue';
import { Bold, Italic, Underline, AlignLeft, AlignCenter, AlignRight, List, ListOrdered, Save } from 'lucide-vue-next';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();

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

const contentMarginClass = computed(() => {
    if (isToolbarOpen.value) {
        return isToolbarOpen.value ? 'duration-300 mt-28' : 'mt-20';
    }
    return 'duration-300 delay-200 mt-20';
});

const tabs = [
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
            { link: "/admin/carts", name: 'Transaksi Sementara', imageSrc: 'https://img.icons8.com/?size=100&id=18982&format=png&color=000000' },
            { link: "/admin/cart-items", name: 'Item Transaksi Sementara', imageSrc: 'https://img.icons8.com/?size=100&id=12034&format=png&color=000000' },
            { link: "/admin/transactions", name: 'Transaksi', imageSrc: 'https://img.icons8.com/?size=100&id=63724&format=png&color=000000' },
            { link: "/admin/transaction-items", name: 'Item Transaksi', imageSrc: 'https://img.icons8.com/?size=100&id=13123&format=png&color=000000' },
            { link: "/admin/debts", name: 'Hutang', imageSrc: 'https://img.icons8.com/?size=100&id=tcIzvG8b1BjB&format=png&color=000000' },
            { link: "/admin/debt-items", name: 'Item Hutang', imageSrc: 'https://img.icons8.com/?size=100&id=13229&format=png&color=000000' },
            { link: "/admin/debt-payment-history", name: 'Riwayat Pembayaran Hutang', imageSrc: 'https://img.icons8.com/?size=100&id=NBus9BZohddY&format=png&color=000000' },
        ]
    },
    {
        name: 'Pembelian',
        imageSrc: 'https://img.icons8.com/?size=100&id=BfXx00KJSKHH&format=png&color=000000',
        tools: [
            { link: "/admin/restocks", name: 'Restock Produk Supplier', imageSrc: 'https://img.icons8.com/?size=100&id=LbZI1V6lICp2&format=png&color=000000' },
            { link: "/admin/restock-lists", name: 'List Belanja', imageSrc: 'https://img.icons8.com/?size=100&id=ZODLIcapQpqg&format=png&color=000000' },
            { link: "/admin/suppliers", name: 'Supplier', imageSrc: 'https://img.icons8.com/?size=100&id=38216&format=png&color=000000' },
        ]
    },
    {
        name: 'Produk',
        imageSrc: 'https://img.icons8.com/?size=100&id=12034&format=png&color=000000',
        tools: [
            { link: '/admin/products', name: 'Produk', imageSrc: 'https://img.icons8.com/?size=100&id=12034&format=png&color=000000' },
            { link: '/admin/stocks', name: 'Stok', imageSrc: 'https://img.icons8.com/?size=100&id=VWrzCw0rvxVx&format=png&color=000000' },
            { link: '/admin/stock-movements', name: 'Riwayat Stok', imageSrc: 'https://img.icons8.com/?size=100&id=18971&format=png&color=000000' },
        ]
    },
    {
        name: 'Pengguna',
        imageSrc: 'https://img.icons8.com/?size=100&id=13042&format=png&color=000000',
        tools: [
            { link: '/admin/customers', name: 'Pelanggan', imageSrc: 'https://img.icons8.com/?size=100&id=23301&format=png&color=000000' },
            { link: '/admin/users', name: 'Admin & Kasir', imageSrc: 'https://img.icons8.com/?size=100&id=108294&format=png&color=000000' },
        ]
    },
    {
        name: 'Laporan',
        imageSrc: 'https://img.icons8.com/?size=100&id=13532&format=png&color=000000',
        tools: [
            { link: "/admin/reports/transaction", name: 'Laporan Penjualan', imageSrc: 'https://img.icons8.com/?size=100&id=103978&format=png&color=000000' },
            { link: "/admin/reports/purchase", name: 'Laporan Pembelian', imageSrc: 'https://img.icons8.com/?size=100&id=103978&format=png&color=000000' },
        ]
    },
    {
        name: 'Pengaturan',
        imageSrc: 'https://img.icons8.com/?size=100&id=12784&format=png&color=000000',
        tools: [
            { name: 'About', imageSrc: 'https://img.icons8.com/color/48/info.png' },
        ]
    }
];

const activeTab = ref('Beranda');

const setActiveTab = (tabName) => {
    activeTab.value = tabName;
    localStorage.setItem('activeTab', tabName);
};

const activeTabTools = computed(() => {
    const currentTab = tabs.find(tab => tab.name === activeTab.value);
    return currentTab ? currentTab.tools : [];
});

const handleToolClick = (tool) => {
    const parentTab = tabs.find(tab => tab.tools.some(t => t.name === tool.name));
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
    console.log(path.value);
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
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.5s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.slide-fade-enter-active,
.slide-fade-leave-active {
    transition: all 0.5s ease;
}

.slide-fade-enter-from,
.slide-fade-leave-to {
    transform: translateY(-10px);
    opacity: 0;
}

.fade-slide-enter-active,
.fade-slide-leave-active {
    transition: all 0.5s ease;
}

.fade-slide-enter-from,
.fade-slide-leave-to {
    transform: translateX(-10px);
    opacity: 0;
}
</style>
