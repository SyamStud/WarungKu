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
import { DialogFooter } from '@/Components/ui/dialog';
import DialogWrapper from '@/Components/ui/dialog/DialogWrapper.vue';
import { Table, TableBody, TableCell, TableRow } from '@/Components/ui/table';
import PaginationWrapper from '@/Components/ui/pagination/PaginationWrapper.vue';
import Button from '@/components/ui/button/Button.vue';
import { useFormatRupiah } from '@/Composables/useFormatRupiah';

/* Inisialisasi Toast untuk menampilkan notifikasi */
const Toast = useToast();
const { formatRupiah } = useFormatRupiah();  // Fungsi untuk format Rupiah

/* MODAL */
const isDeleteModalOpen = ref(false);  // Mengontrol apakah modal hapus terbuka atau tidak
const selectedDebt = ref(null);        // Menyimpan data utang yang dipilih untuk dihapus

/* Fungsi untuk membuka modal hapus dan menyimpan data utang yang dipilih */
const openDeleteModal = (debt) => {
    selectedDebt.value = debt;
    isDeleteModalOpen.value = true;
};

let isLoading = ref(false);  // Menunjukkan status loading saat penghapusan utang

/* Fungsi untuk menghapus data utang yang dipilih */
const deleteDebt = async () => {
    if (selectedDebt.value) {
        try {
            console.log('Deleting debt:', selectedDebt.value);  // Log data utang yang akan dihapus
            // Melakukan request penghapusan data ke API
            const response = await axios.post(`/admin/debts/delete`, {
                customer_id: selectedDebt.value.id,
            });
            console.log('Delete response:', response.data);  // Log response untuk debugging

            // Jika ada error dari server, tampilkan pesan error melalui Toast
            if (response.data.status === 'error') {
                return Toast.fire({
                    icon: "error",
                    title: response.data.message,
                });
            } else {
                // Tampilkan pesan sukses jika berhasil dihapus
                Toast.fire({
                    icon: "success",
                    title: response.data.message,
                });
            }

            // Tutup modal setelah penghapusan
            isDeleteModalOpen.value = false;
            fetchData();  // Refresh data setelah penghapusan
        } catch (error) {
            console.error('Error deleting debt:', error);  // Log jika ada kesalahan
        }
    }
};

/* TABLE */
/* Konfigurasi kolom tabel untuk menampilkan informasi pelanggan terkait utang */
const columns = [
    { accessorKey: 'name', header: 'Nama Terhutang' },
    { accessorKey: 'phone', header: 'Nomor Telepon' },
    { accessorKey: 'address', header: 'Alamat' },
    { accessorKey: 'total_debt', header: 'Total Hutang' },
    { accessorKey: 'paid_amount', header: 'Total Dibayar' },
    { accessorKey: 'remaining_debt', header: 'Hutang Tersisa' },
];

const data = ref([]);  // Menyimpan data tabel
const globalFilter = ref('');  // Filter global untuk pencarian
const pagination = ref({
    pageIndex: 0,    // Indeks halaman saat ini
    pageSize: 10,    // Jumlah item per halaman
    pageCount: 1,    // Total halaman
    total: 0,        // Total item
});

/* Sorting data berdasarkan kolom tertentu dan arah (ascending/descending) */
const sorting = ref({ field: 'id', direction: 'asc' });

/* Inisialisasi dan konfigurasi tabel */
const table = useVueTable({
    get data() { return data.value; }, // Mengambil data untuk ditampilkan
    columns,
    getCoreRowModel: getCoreRowModel(),  // Model data inti
    getPaginationRowModel: getPaginationRowModel(),  // Model pagination
    state: {
        pagination: computed(() => ({
            pageIndex: pagination.value.pageIndex,
            pageSize: pagination.value.pageSize,
        })),
    },
    manualPagination: true,  // Pagination diatur secara manual oleh backend
    pageCount: computed(() => pagination.value.pageCount),
    /* Saat pagination berubah, update data tabel */
    onPaginationChange: (updater) => {
        if (typeof updater === 'function') {
            const newPagination = updater(pagination.value);
            pagination.value = { ...pagination.value, ...newPagination };
        } else {
            pagination.value = { ...pagination.value, ...updater };
        }
        fetchData();  // Ambil data terbaru saat pagination berubah
    }
});

/* Fungsi untuk mengambil data dari API berdasarkan filter, pagination, dan sorting */
const fetchData = async () => {
    try {
        // Panggil API untuk mengambil data pelanggan dengan pagination, filter, dan sorting
        const response = await axios.get('/api/customers', {
            params: {
                search: globalFilter.value,
                page: pagination.value.pageIndex + 1,  // Backend page mulai dari 1
                per_page: pagination.value.pageSize,
                sort: sorting.value.field,
                direction: sorting.value.direction,
            }
        });

        data.value = response.data.data;  // Update data yang ditampilkan di tabel
        console.log('Data:', data.value);  // Log data untuk debugging

        // Update pagination berdasarkan hasil dari response API
        pagination.value = {
            pageIndex: response.data.meta.current_page - 1,
            pageSize: response.data.meta.per_page,
            pageCount: response.data.meta.last_page,
            total: response.data.meta.total,
        };
    } catch (error) {
        console.error('Error fetching data:', error);  // Log jika terjadi kesalahan
    }
};

/* Mengurangi frekuensi panggilan API dengan debounce */
const debouncedFetchData = debounce(fetchData, 300);

/* Fungsi untuk mengubah sorting berdasarkan kolom yang dipilih */
const sortBy = (field) => {
    if (sorting.value.field === field) {
        sorting.value.direction = sorting.value.direction === 'asc' ? 'desc' : 'asc';  // Toggle sorting
    } else {
        sorting.value.field = field;
        sorting.value.direction = 'asc';  // Set sorting ascending jika kolom berubah
    }
    fetchData();  // Fetch ulang data
};

/* Fetch data pertama kali saat komponen di-mount */
onMounted(fetchData);

/* Watch perubahan pagination dan fetch data ulang saat ada perubahan */
watch(() => pagination.value, () => { }, { deep: true });

/* Fungsi untuk menangani perubahan halaman */
const handlePageChange = (newPageIndex) => {
    pagination.value.pageIndex = newPageIndex;
    fetchData();  // Ambil data sesuai halaman yang baru
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
    <!-- Mengatur judul halaman -->

    <Head title="Daftar Hutang" />

    <AdminLayout>
        <!-- Judul Halaman -->
        <h1 class="text-2xl font-semibold text-gray-900">Daftar Hutang</h1>
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
                <Input placeholder="Cari Hutang..." v-model="globalFilter" class="w-full max-w-full md:max-w-sm"
                    @input="debouncedFetchData" />
            </div>
        </div>

        <div>
            <!-- Table  -->
            <div class="rounded-md border">
                <Table>
                    <TableHeaderWrapper :columns="columns" :sorting="sorting" @sort="sortBy" />

                    <TableBody>
                        <TableRow v-for="(row, index) in table.getRowModel().rows" :key="row.id">
                            <!-- Kolom pertama untuk nomor urut -->
                            <TableCell>
                                {{ (pagination.pageIndex) * 10 + index + 1 }}
                            </TableCell>

                            <!-- Kolom-kolom lainnya -->
                            <TableCell v-for="cell in row.getVisibleCells()" :key="cell.id">
                                <template
                                    v-if="cell.column.id === 'total_debt' || cell.column.id === 'paid_amount' || cell.column.id === 'remaining_debt'">
                                    {{ formatRupiah(cell.getValue()) }}
                                </template>

                                <template v-else>
                                    {{ cell.getValue() }}
                                </template>
                            </TableCell>

                            <!-- Kolom untuk aksi -->
                            <TableCell>
                                <div class="flex gap-2">
                                    <Button @click="() => openDeleteModal(row.original)">Hapus</Button>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <!-- Pagination -->
            <PaginationWrapper :pagination="pagination" :onPageChange="handlePageChange" />

            <!-- Delete Modal -->
            <DialogWrapper v-model:open="isDeleteModalOpen" title="Hapus Hutang" desc="">
                <p>Menghapus hutang artinya <b>melunasi semua hutang</b> yang dimiliki pelanggan. <span
                        class="font-extrabold">Anda
                        yakin semua hutang pelanggan telah lunas?</span></p>

                <DialogFooter>
                    <Button @click="isDeleteModalOpen = false" variant="outline">Batal</Button>
                    <Button @click="deleteDebt" variant="destructive">Ya, Hapus</Button>
                </DialogFooter>
            </DialogWrapper>
        </div>
    </AdminLayout>
</template>
