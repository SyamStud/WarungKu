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

/* Kolom tabel yang menampilkan berbagai informasi terkait pembayaran utang */
const columns = [
    { accessorKey: 'payment_code', header: 'Kode Pembayaran' },
    { accessorKey: 'customer', header: 'Nama Terhutang' },
    { accessorKey: 'product', header: 'Nama Produk' },
    { accessorKey: 'product', header: 'Variasi' },
    { accessorKey: 'quantity', header: 'Kuantitas' },
    { accessorKey: 'total_debt', header: 'Total Hutang' },
    { accessorKey: 'paid_amount', header: 'Total Dibayar' },
    { accessorKey: 'debt_remaining', header: 'Total Tersisa' },
    { accessorKey: 'payment_method', header: 'Metode Pembayaran' },
    { accessorKey: 'paid_at', header: 'Tanggal Pembayaran' },
    { accessorKey: 'user', header: 'Kasir' },
];

/* State untuk menyimpan data tabel, filter global, pagination, dan sorting */
const data = ref([]);
const globalFilter = ref('');
const pagination = ref({
    pageIndex: 0,
    pageSize: 10,
    pageCount: 1,
    total: 0,
});

/* Sorting untuk mengatur kolom dan arah sorting */
const sorting = ref({ field: 'id', direction: 'asc' });

/* Inisialisasi dan konfigurasi tabel */
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
        /* Update pagination dan panggil ulang data */
        if (typeof updater === 'function') {
            const newPagination = updater(pagination.value);
            pagination.value = { ...pagination.value, ...newPagination };
        } else {
            pagination.value = { ...pagination.value, ...updater };
        }
        fetchData();
    }
});

/* Fungsi untuk mengambil data dari API berdasarkan filter, pagination, dan sorting */
const fetchData = async () => {
    try {
        const response = await axios.get('/api/debt-payment-history', {
            params: {
                search: globalFilter.value,
                page: pagination.value.pageIndex + 1,
                per_page: pagination.value.pageSize,
                sort: sorting.value.field,
                direction: sorting.value.direction,
            }
        });

        /* Update data dan pagination dari response */
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

/* Debounced fetch untuk mengurangi frekuensi panggilan API */
const debouncedFetchData = debounce(fetchData, 300);

/* Fungsi untuk mengubah sorting kolom */
const sortBy = (field) => {
    if (sorting.value.field === field) {
        sorting.value.direction = sorting.value.direction === 'asc' ? 'desc' : 'asc';
    } else {
        sorting.value.field = field;
        sorting.value.direction = 'asc';
    }
    fetchData(); // Fetch ulang data
};

/* Fetch data pertama kali saat komponen di-mount */
onMounted(() => {
    fetchData();
})

/* Watch perubahan pagination */
watch(() => pagination.value, () => { }, { deep: true });

/* Fungsi untuk menangani perubahan halaman */
const handlePageChange = (newPageIndex) => {
    pagination.value.pageIndex = newPageIndex;
    fetchData();
};
</script>


<style scope src="vue-multiselect/dist/vue-multiselect.css"></style>

<template>
    <!-- Mengatur judul halaman -->

    <Head title="Daftar Riwayat Pembayaran Hutang" />

    <AdminLayout>
        <!-- Judul Halaman -->
        <h1 class="text-2xl font-semibold text-gray-900">Daftar Riwayat Pembayaran Hutang</h1>
        <!-- Input Pencarian -->
        <div class="flex flex-col md:flex-row justify-end">
            <div class="flex items-center py-4 w-full md:w-72">
                <Input placeholder="Cari Riwayat Pembayaran Hutang..." v-model="globalFilter"
                    class="w-full max-w-full md:max-w-sm" @input="debouncedFetchData" />
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
                                    v-if="cell.column.id === 'total_debt' || cell.column.id === 'paid_amount' || cell.column.id === 'debt_remaining'">
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
