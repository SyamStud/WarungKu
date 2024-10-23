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
import Button from '@/components/ui/button/Button.vue';

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

import { RangeCalendar } from '@/Components/ui/range-calendar'
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover'
import { cn } from '@/lib/utils';

import {
    CalendarDate,
    DateFormatter,
    getLocalTimeZone,
} from '@internationalized/date'
import Label from '@/components/ui/label/Label.vue';

// Formatter untuk tanggal
const df = new DateFormatter('en-US', {
    dateStyle: 'medium',
})

// Menggunakan ref tanpa tipe DateValue (JavaScript tidak membutuhkan tipe)
const value = ref({
    start: new CalendarDate(new Date().getFullYear(), new Date().getMonth() + 1, new Date().getDate()),
    end: new CalendarDate(new Date().getFullYear(), new Date().getMonth() + 1, new Date().getDate()),
})

const formatDate = (date) => {
    // Format the date as YYYY-MM-DD
    return date.getFullYear() + '-' +
        ('0' + (date.getMonth() + 1)).slice(-2) + '-' +
        ('0' + date.getDate()).slice(-2);
}

const exportExcel = async () => {
    const startDateObj = value.value.start.toDate(getLocalTimeZone());
    const endDateObj = value.value.end.toDate(getLocalTimeZone());

    const startDate = formatDate(startDateObj);
    const endDate = formatDate(endDateObj);
    console.log(startDate, endDate);

    window.location.href = `/admin/stock-movements/export-excel?start_date=${startDate}&end_date=${endDate}`;
}
</script>

<style scope src="vue-multiselect/dist/vue-multiselect.css"></style>

<template>
    <!-- Mengatur judul halaman -->

    <Head title="Daftar Item Hutang" />

    <AdminLayout>
        <!-- Judul Halaman -->
        <h1 class="text-2xl font-semibold text-gray-900">Daftar Item Hutang</h1>

        <!-- Input Pencarian -->
        <div class="flex flex-col md:flex-row justify-between">
            <div class="flex gap-2 items-center">
                <div class="flex gap-2 items-center">
                    <Label>Pilih Rentang Export : </Label>
                    <Popover>
                        <PopoverTrigger as-child>
                            <Button variant="outline" :class="cn(
                                'w-[280px] justify-start text-left font-normal',
                                !value && 'text-muted-foreground',
                            )">
                                <img class="me-2" width="20" height="20"
                                    src="https://img.icons8.com/color/48/calendar--v1.png" alt="calendar--v1" />
                                <template v-if="value.start">
                                    <template v-if="value.end">
                                        {{ df.format(value.start.toDate(getLocalTimeZone())) }} - {{
                                            df.format(value.end.toDate(getLocalTimeZone())) }}
                                    </template>

                                    <template v-else>
                                        {{ df.format(value.start.toDate(getLocalTimeZone())) }}
                                    </template>
                                </template>
                                <template v-else>
                                    Pick a date
                                </template>
                            </Button>
                        </PopoverTrigger>
                        <PopoverContent class="w-auto p-0">
                            <RangeCalendar v-model="value" initial-focus :number-of-months="2"
                                @update:start-value="(startDate) => value.start = startDate" />
                        </PopoverContent>
                    </Popover>
                </div>

                <Button @click="exportExcel"
                    class="w-full md:w-max bg-green-700 hover:bg-green-700 flex items-center gap-3">
                    <img class="w-5" src="https://img.icons8.com/?size=100&id=11594&format=png&color=FFFFFF" alt="">
                    Export Excel
                </Button>

            </div>
            
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
