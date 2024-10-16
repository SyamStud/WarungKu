<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import axios from 'axios';
import debounce from 'lodash/debounce';
import { Head } from '@inertiajs/vue3';
import { useVueTable, getCoreRowModel, getPaginationRowModel } from '@tanstack/vue-table';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Input } from '@/Components/ui/input/index.js';
import TableHeaderWrapper from '@/Components/ui/table/TableHeaderWrapper.vue';
import { Table, TableBody, TableCell, TableRow } from '@/Components/ui/table';
import PaginationWrapper from '@/Components/ui/pagination/PaginationWrapper.vue';
import { useFormatRupiah } from '@/Composables/useFormatRupiah';

const { formatRupiah } = useFormatRupiah();

/* Definisi Kolom Tabel */
const columns = [
    { accessorKey: 'transaction', header: 'Kode Transaksi' },
    { accessorKey: 'customer', header: 'Nama Terhutang' },
    { accessorKey: 'product', header: 'Nama Produk' },
    { accessorKey: 'quantity', header: 'Kuantitas' },
    { accessorKey: 'total_amount', header: 'Total Hutang' },
    { accessorKey: 'paid_amount', header: 'Total Dibayar' },
    { accessorKey: 'remaining_amount', header: 'Total Tersisa' },
    { accessorKey: 'last_payment_at', header: 'Pembayaran Terakhir' },
    { accessorKey: 'settled_at', header: 'Tanggal Lunas' },
];

/* State Data Tabel dan Filter */
const data = ref([]);
const globalFilter = ref('');

/* State Pagination */
const pagination = ref({
    pageIndex: 0,
    pageSize: 10,
    pageCount: 1,
    total: 0,
});

/* State Sorting */
const sorting = ref({ field: 'id', direction: 'asc' });

/* Inisialisasi Tabel dengan Pagination dan Sorting Manual */
const table = useVueTable({
    get data() { return data.value; },
    columns,
    getCoreRowModel: getCoreRowModel(),
    getPaginationRowModel: getPaginationRowModel(),
    state: {
        pagination: computed(() => ({
            pageIndex: pagination.value.pageIndex,
            pageSize: pagination.value.pageSize,
        })),
    },
    manualPagination: true,
    pageCount: computed(() => pagination.value.pageCount),
    onPaginationChange: (updater) => {
        if (typeof updater === 'function') {
            const newPagination = updater(pagination.value);
            pagination.value = { ...pagination.value, ...newPagination };
        } else {
            pagination.value = { ...pagination.value, ...updater };
        }
        fetchData(); // Memanggil ulang data ketika pagination berubah
    }
});

/* Fungsi untuk Mengubah Sorting */
const sortBy = (field) => {
    if (sorting.value.field === field) {
        sorting.value.direction = sorting.value.direction === 'asc' ? 'desc' : 'asc';
    } else {
        sorting.value.field = field;
        sorting.value.direction = 'asc';
    }
    fetchData(); // Memuat ulang data dengan sorting baru
};

/* Fungsi untuk Memuat Data dari API */
const fetchData = async () => {
    try {
        const response = await axios.get('/api/debt-items', {
            params: {
                search: globalFilter.value,
                page: pagination.value.pageIndex + 1,
                per_page: pagination.value.pageSize,
                sort: sorting.value.field,
                direction: sorting.value.direction,
            }
        });

        /* Memperbarui Data dan Pagination */
        data.value = response.data.data;
        pagination.value = {
            pageIndex: response.data.meta.current_page - 1,
            pageSize: response.data.meta.per_page,
            pageCount: response.data.meta.last_page,
            total: response.data.meta.total,
        };
    } catch (error) {
        console.error('Error fetching data:', error);
    }
};

/* Menggunakan Debounce untuk Mengurangi Panggilan API Terlalu Sering */
const debouncedFetchData = debounce(fetchData, 300);

/* Memuat Data Pertama Kali Ketika Komponen Dimuat */
onMounted(() => {
    fetchData();
});

/* Watcher untuk Pagination (jika ada perubahan dalam pagination) */
watch(() => pagination.value, () => { }, { deep: true });

/* Fungsi untuk Mengubah Halaman */
const handlePageChange = (newPageIndex) => {
    pagination.value.pageIndex = newPageIndex;
    fetchData(); // Panggil ulang data untuk halaman baru
};
</script>

<style scope src="vue-multiselect/dist/vue-multiselect.css"></style>

<template>
    <!-- Mengatur judul halaman -->

    <Head title="Daftar Item Hutang" />

    <AdminLayout>
        <!-- Judul Halaman -->
        <h1 class="text-2xl font-semibold text-gray-900">Daftar Item Hutang</h1>

        <!-- Input Pencarian -->
        <div class="flex flex-col md:flex-row justify-end">
            <div class="flex items-center py-4 w-full md:w-72">
                <Input placeholder="Cari Item Hutang..." v-model="globalFilter" class="w-full max-w-full md:max-w-sm"
                    @input="debouncedFetchData" />
            </div>
        </div>

        <div>
            <!-- Table  -->
            <div class="rounded-md border">
                <Table>
                    <TableHeaderWrapper :columns="columns" :sorting="sorting" @sort="sortBy" :showActions="false" />

                    <TableBody>
                        <TableRow v-for="(row, index) in table.getRowModel().rows" :key="row.id">
                            <!-- Kolom pertama untuk nomor urut -->
                            <TableCell>
                                {{ (pagination.pageIndex) * 10 + index + 1 }}
                            </TableCell>

                            <!-- Kolom-kolom lainnya -->
                            <TableCell v-for="cell in row.getVisibleCells()" :key="cell.id">
                                <template
                                    v-if="cell.column.id === 'total_amount' || cell.column.id === 'paid_amount' || cell.column.id === 'remaining_amount'">
                                    {{ formatRupiah(cell.getValue()) }}
                                </template>

                                <template v-else>
                                    {{ cell.getValue() }}
                                </template>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <!-- Pagination -->
            <PaginationWrapper :pagination="pagination" :onPageChange="handlePageChange" />
        </div>
    </AdminLayout>
</template>
