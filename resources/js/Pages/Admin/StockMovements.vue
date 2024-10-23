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
import Button from '@/components/ui/button/Button.vue';

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


<template>

    <Head title="Riwayat Stok" />

    <AdminLayout>
        <h1 class="text-2xl font-semibold text-gray-900">Riwayat Stok</h1>
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
                                <template v-if="cell.column.id === 'quantity'">
                                    {{ cell.column.id === 'quantity' && row.getValue('type') === 'out' ?
                                        -cell.getValue() : cell.getValue() }}
                                </template>

                                <template v-else-if="cell.column.id === 'type'">
                                    <span :class="{
                                        'flex gap-1 items-center': true,
                                        'text-green-500': cell.getValue() === 'in',
                                        'text-red-500': cell.getValue() === 'out'
                                    }">
                                        <img class="w-5"
                                            :src="cell.getValue() === 'in' ? 'https://img.icons8.com/?size=100&id=26210&format=png&color=40C057' : 'https://img.icons8.com/?size=100&id=26209&format=png&color=FA5252'"
                                            alt="">

                                        {{ cell.getValue() === 'in' ? 'Masuk' : 'Keluar' }}
                                    </span>
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
