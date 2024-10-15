<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import axios from 'axios';
import debounce from 'lodash/debounce';
import { Head } from '@inertiajs/vue3';
import { useVueTable, getCoreRowModel, getPaginationRowModel } from '@tanstack/vue-table';

import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Input } from '@/Components/ui/input/index.js';
import { useToast } from '@/Composables/useToast';
import TableHeaderWrapper from '@/Components/ui/table/TableHeaderWrapper.vue';
import { Table, TableBody, TableCell, TableRow } from '@/Components/ui/table';
import PaginationWrapper from '@/Components/ui/pagination/PaginationWrapper.vue';

const Toast = useToast();

let isLoading = ref(false);

/* TABLE */
// Definisi kolom-kolom tabel
const columns = [
    { accessorKey: 'reference', header: 'Referensi' },
    { accessorKey: 'product', header: 'Nama Produk' },
    { accessorKey: 'variant', header: 'Variasi' },
    { accessorKey: 'quantity', header: 'Kuantitas' },
    { accessorKey: 'type', header: 'Tipe' },
    { accessorKey: 'created_at', header: 'Tanggal' },
];

const data = ref([]); // Data tabel
const globalFilter = ref(''); // Filter global untuk pencarian
const pagination = ref({
    pageIndex: 0, // Indeks halaman saat ini
    pageSize: 10, // Jumlah item per halaman
    pageCount: 1, // Jumlah total halaman
    total: 0, // Jumlah total item
});

const sorting = ref({ field: 'id', direction: 'asc' }); // Pengurutan data

const table = useVueTable({
    get data() { return data.value; }, // Data tabel
    columns, // Kolom-kolom tabel
    getCoreRowModel: getCoreRowModel(), // Model baris inti
    getPaginationRowModel: getPaginationRowModel(), // Model baris paginasi
    state: {
        pagination: computed(() => ({
            pageIndex: pagination.value.pageIndex,
            pageSize: pagination.value.pageSize,
        })),
    },
    manualPagination: true, // Paginasi manual
    pageCount: computed(() => pagination.value.pageCount), // Jumlah total halaman
    onPaginationChange: (updater) => {
        if (typeof updater === 'function') {
            const newPagination = updater(pagination.value);
            pagination.value = { ...pagination.value, ...newPagination };
        } else {
            pagination.value = { ...pagination.value, ...updater };
        }
        fetchData(); // Ambil data baru saat paginasi berubah
    }
});

// Fungsi untuk mengambil data dari API
const fetchData = async () => {
    try {
        const response = await axios.get('/api/stock-movements', {
            params: {
                search: globalFilter.value, // Parameter pencarian
                page: pagination.value.pageIndex + 1, // Halaman saat ini
                per_page: pagination.value.pageSize, // Jumlah item per halaman
                sort: sorting.value.field, // Kolom pengurutan
                direction: sorting.value.direction, // Arah pengurutan
            }
        });

        data.value = response.data.data; // Set data tabel
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

const debouncedFetchData = debounce(fetchData, 300); // Debounce untuk pencarian

// Fungsi untuk mengurutkan data
const sortBy = (field) => {
    if (sorting.value.field === field) {
        sorting.value.direction = sorting.value.direction === 'asc' ? 'desc' : 'asc';
    } else {
        sorting.value.field = field;
        sorting.value.direction = 'asc';
    }
    fetchData(); // Ambil data baru saat pengurutan berubah
};

onMounted(fetchData); // Ambil data saat komponen dimuat

watch(() => pagination.value, () => { }, { deep: true }); // Watcher untuk paginasi

// Fungsi untuk menangani perubahan halaman
const handlePageChange = (newPageIndex) => {
    pagination.value.pageIndex = newPageIndex;
    fetchData(); // Ambil data baru saat halaman berubah
};
</script>


<template>

    <Head title="Riwayat Stok" />

    <AdminLayout>
        <h1 class="text-2xl font-semibold text-gray-900">Riwayat Stok</h1>
        <div class="flex flex-col md:flex-row justify-end">
            <div class="flex items-center py-4 w-full md:w-72">
                <Input placeholder="Cari Riwayat Stok..." v-model="globalFilter" class="w-full max-w-full md:max-w-sm"
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
                                {{ cell.getValue() }}
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
