<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import AutoCarousel from '@/Components/AutoCarousel.vue';
import { useFormatRupiah } from '@/Composables/useFormatRupiah';

const { formatRupiah } = useFormatRupiah();

// Array berisi URL gambar untuk carousel
const carouselImages = [
    'https://via.placeholder.com/800x400/FF6347/FFFFFF?text=Slide+1',
    'https://via.placeholder.com/800x400/4682B4/FFFFFF?text=Slide+2',
    'https://via.placeholder.com/800x400/9ACD32/FFFFFF?text=Slide+3',
];

// const topProducts = ref([
//     { id: 1, name: 'Pepsodent', sold: 150, revenue: 225000 },
//     { id: 2, name: 'Indomie Goreng', sold: 120, revenue: 180000 },
//     { id: 3, name: 'Minyak Goreng', sold: 90, revenue: 270000 },
//     { id: 4, name: 'Sabun Mandi', sold: 80, revenue: 120000 },
//     { id: 5, name: 'Beras 5kg', sold: 60, revenue: 300000 },
// ]);

// Fungsi format untuk mata uang
const formatCurrency = (amount) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(amount);
};

const summary = ref({
    totalTransaction: 0,
    totalPurchase: 0,
    totalhighestTransaction: 0,
    topOneProduct: '',
    topProducts: [],
});

const fetchData = async () => {
    const summaryData = await axios.get('/admin/dashboard/dashboardSummary');
    summary.value.totalTransaction = summaryData.data.total_transaction;
    summary.value.totalPurchase = summaryData.data.total_purchase;
    summary.value.totalhighestTransaction = summaryData.data.highest_transaction;
    summary.value.topOneProduct = summaryData.data.top_one;
    summary.value.topProducts = summaryData.data.top_products;
    summary.value.needRestock = summaryData.data.need_restock;
    console.log('summary', summary.value.needRestock);
};

onMounted(() => {
    fetchData();

});
</script>

<template>

    <Head title="beranda" />

    <AdminLayout>
        <div class="2xl:px-8">
            <AutoCarousel :images="carouselImages" :interval="4000" />

            <div
                class="mt-10 p-6 bg-gradient-to-r from-white to-gray-100 flex items-center mx-auto border-b mb-10 sm:flex-row flex-col rounded-xl border border-gray-200">
                <div class="sm:w-32 sm:h-32 h-20 w-20 sm:mr-10 inline-flex items-center justify-center flex-shrink-0">
                    <img width="100" height="100"
                        src="https://img.icons8.com/external-kmg-design-outline-color-kmg-design/100/external-graph-economy-kmg-design-outline-color-kmg-design.png"
                        alt="external-graph-economy-kmg-design-outline-color-kmg-design" />
                </div>
                <div class="flex-grow sm:text-left text-center mt-6 sm:mt-0">
                    <h1 class="text-black text-xl md:text-3xl title-font font-bold mb-4">Ringkasan Informasi Hari Ini
                    </h1>

                    <div class="md:flex md:gap-3 font-bold text-gray-800">
                        <!-- Penjualan & Pembelian Section -->
                        <div class="w-full md:w-1/2 flex md:flex-row flex-col md:space-x-3 mb-2 md:mb-0">
                            <div
                                class="w-full md:w-1/2 bg-gradient-to-br from-green-100 to-green-200 p-4 rounded-lg shadow-sm">
                                <h2 class="text-green-800 text-sm font-semibold">Penjualan</h2>
                                <p class="text-xl">{{ formatRupiah(summary.totalTransaction) }}</p>
                            </div>

                            <div
                                class="w-full mt-2 md:mt-0 md:w-1/2 bg-gradient-to-br from-red-100 to-red-200 p-4 rounded-lg shadow-sm">
                                <h2 class="text-red-800 text-sm font-semibold">Pembelian</h2>
                                <p class="text-xl">{{ formatRupiah(summary.totalPurchase) }}</p>
                            </div>
                        </div>

                        <!-- Barang Terlaris & Transaksi Tertinggi Section -->
                        <div class="w-full md:w-1/2 flex md:flex-row flex-col md:space-x-3 mb-4 md:mb-0">
                            <div
                                class="w-full md:w-1/2 bg-gradient-to-br from-violet-100 to-violet-200 p-4 rounded-lg shadow-sm">
                                <h2 class="text-violet-800 text-sm font-semibold">Penjualan Tertinggi</h2>
                                <p class="text-xl">{{ formatRupiah(summary.totalhighestTransaction) }}</p>
                            </div>

                            <div
                                class="w-full mt-2 md:mt-0 md:w-1/2 bg-gradient-to-br from-blue-100 to-blue-200 p-4 rounded-lg shadow-sm">
                                <h2 class="text-blue-800 text-sm font-semibold">Barang Terlaris</h2>
                                <p class="text-xl">{{ summary.topOneProduct }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex md:flex-row flex-col gap-5">
                <div class="p-6 w-full mx-auto bg-white rounded-lg border border-gray-200">
                    <h2 class="text-2xl font-semibold text-gray-700 mb-6">Produk Terlaris</h2>
                    <div class="overflow-x-auto">
                        <table class="table-auto w-full bg-white rounded-lg">
                            <thead>
                                <tr class="bg-gray-100 text-gray-600 text-sm leading-normal">
                                    <th class="py-3 px-6 text-left">#</th>
                                    <th class="py-3 px-6 text-left">Nama Produk</th>
                                    <th class="py-3 px-6 text-center">Terjual</th>
                                    <th class="py-3 px-6 text-center">Total Penjualan</th>
                                </tr>
                            </thead>
                            <tbody class="text-gray-600 text-sm font-light">
                                <tr v-for="(product, index) in summary.topProducts" :key="product.id"
                                    class="border-b border-gray-200 hover:bg-gray-50">
                                    <td class="py-3 px-6 text-left">{{ index + 1 }}</td>
                                    <td class="py-3 px-6 text-left font-medium text-gray-900">{{ product.product.name }}
                                    </td>
                                    <td class="py-3 px-6 text-center">{{ product.total_quantity }}</td>
                                    <td class="py-3 px-6 text-center text-green-500">{{
                                        formatRupiah(product.total_quantity * product.total_price) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="p-6 w-full mx-auto bg-white rounded-lg border border-gray-200">
                    <h2 class="text-2xl font-semibold text-gray-700 mb-6">Produk Perlu Restock</h2>
                    <div class="overflow-x-auto">
                        <table class="table-auto w-full bg-white rounded-lg">
                            <thead>
                                <tr class="bg-gray-100 text-gray-600 text-sm leading-normal">
                                    <th class="py-3 px-6 text-left">#</th>
                                    <th class="py-3 px-6 text-left">Nama Produk</th>
                                    <th class="py-3 px-6 text-center">Tersisa</th>
                                </tr>
                            </thead>
                            <tbody class="text-gray-600 text-sm font-light">
                                <tr v-for="(product, index) in summary.needRestock" :key="product.id"
                                    class="border-b border-gray-200 hover:bg-gray-50">
                                    <td class="py-3 px-6 text-left">{{ index + 1 }}</td>
                                    <td class="py-3 px-6 text-left font-medium text-gray-900">{{
                                        product.product_variant.product.name }}</td>
                                    <td class="py-3 px-6 text-center">{{ product.total_quantity }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
