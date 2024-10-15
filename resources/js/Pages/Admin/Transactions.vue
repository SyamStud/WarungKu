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

// Mengimpor fungsi formatRupiah dari composable useFormatRupiah
const { formatRupiah } = useFormatRupiah();

/* TABEL */
// Mendefinisikan kolom-kolom tabel
const columns = [
    { accessorKey: 'transaction_code', header: 'Kode Transaksi' },
    { accessorKey: 'total_price', header: 'Total Belanja' },
    { accessorKey: 'discount', header: 'Diskon' },
    { accessorKey: 'tax', header: 'Pajak' },
    { accessorKey: 'grand_total', header: 'Total Akhir' },
    { accessorKey: 'total_payment', header: 'Total Bayar' },
    { accessorKey: 'total_change', header: 'Kembalian' },
    { accessorKey: 'created_at', header: 'Tanggal Transaksi' },
    { accessorKey: 'user', header: 'Kasir' },
];

// Mendefinisikan data reaktif untuk menyimpan data transaksi
const data = ref([]);
// Mendefinisikan filter global untuk pencarian
const globalFilter = ref('');
// Mendefinisikan data reaktif untuk pagination
const pagination = ref({
    pageIndex: 0,
    pageSize: 10,
    pageCount: 1,
    total: 0,
});

// Mendefinisikan data reaktif untuk sorting
const sorting = ref({ field: 'id', direction: 'asc' });

// Menggunakan composable useVueTable untuk mengatur tabel
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
        fetchData();
    }
});

// Fungsi untuk mengambil data dari API
const fetchData = async () => {
    try {
        const response = await axios.get('/api/transactions', {
            params: {
                search: globalFilter.value,
                page: pagination.value.pageIndex + 1,
                per_page: pagination.value.pageSize,
                sort: sorting.value.field,
                direction: sorting.value.direction,
            }
        });

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

// Fungsi untuk mengambil data dengan debounce
const debouncedFetchData = debounce(fetchData, 300);

// Fungsi untuk mengatur sorting
const sortBy = (field) => {
    if (sorting.value.field === field) {
        sorting.value.direction = sorting.value.direction === 'asc' ? 'desc' : 'asc';
    } else {
        sorting.value.field = field;
        sorting.value.direction = 'asc';
    }
    fetchData();
};

// Mengambil data saat komponen dimount
onMounted(() => {
    fetchData();
})

// Menonton perubahan pada pagination
watch(() => pagination.value, () => { }, { deep: true });

// Fungsi untuk mengubah halaman
const handlePageChange = (newPageIndex) => {
    pagination.value.pageIndex = newPageIndex;
    fetchData();
};
</script>

<style scope src="vue-multiselect/dist/vue-multiselect.css"></style>

<template>

    <Head title="Daftar Transaksi" />

    <AdminLayout>
        <h1 class="text-2xl font-semibold text-gray-900">Daftar Transaksi</h1>
        <div class="flex flex-col md:flex-row justify-end">
            <div class="flex items-center py-4 w-full md:w-72">
                <Input placeholder="Cari Transaksi..." v-model="globalFilter" class="w-full max-w-full md:max-w-sm"
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
                                    v-if="cell.column.id === 'transaction_code' || cell.column.id === 'created_at' || cell.column.id === 'user'">
                                    {{ cell.getValue() }}
                                </template>

                                <template v-else>
                                    {{ formatRupiah(cell.getValue()) }}
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
