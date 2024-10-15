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

// Inisialisasi Toast untuk notifikasi
const Toast = useToast();

/* MODAL */
const isDeleteModalOpen = ref(false);
const selectedCart = ref(null);

// Fungsi untuk membuka modal hapus dengan data transaksi yang dipilih
const openDeleteModal = (cart) => {
    selectedCart.value = cart;
    isDeleteModalOpen.value = true;
};

// Fungsi untuk menghapus transaksi
const deleteCart = async () => {
    if (selectedCart.value) {
        try {
            const response = await axios.post(`/admin/carts/${selectedCart.value.id}?_method=DELETE`);
            if (response.data.status === 'error') {
                // Menampilkan pesan error jika gagal
                return Toast.fire({
                    icon: "error",
                    title: response.data.message,
                });
            } else {
                // Menampilkan pesan sukses jika berhasil
                Toast.fire({
                    icon: "success",
                    title: response.data.message,
                });
            }

            // Menutup modal setelah menghapus dan refresh data
            isDeleteModalOpen.value = false;
            fetchData();
        } catch (error) {
            console.error('Error deleting cart:', error);
        }
    }
};

/* TABLE */
const columns = [
    { accessorKey: 'transaction_code', header: 'Kode Transaksi' },
    { accessorKey: 'total_price', header: 'Total Belanja' },
    { accessorKey: 'user', header: 'Kasir' },
];

// State untuk menyimpan data transaksi
const data = ref([]);
// State untuk filter pencarian global
const globalFilter = ref('');
// State untuk pagination (index halaman, jumlah per halaman, total halaman, total data)
const pagination = ref({
    pageIndex: 0,
    pageSize: 10,
    pageCount: 1,
    total: 0,
});

// State untuk menyimpan sorting (kolom yang diurutkan dan arah urutan)
const sorting = ref({ field: 'id', direction: 'asc' });

// Konfigurasi table menggunakan TanStack Vue Table
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
    // Ketika pagination berubah, refresh data
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
        const response = await axios.get('/api/carts', {
            params: {
                search: globalFilter.value,
                page: pagination.value.pageIndex + 1,
                per_page: pagination.value.pageSize,
                sort: sorting.value.field,
                direction: sorting.value.direction,
            }
        });

        // Menyimpan data dan meta data pagination
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

// Membuat fungsi debounce untuk optimasi pencarian
const debouncedFetchData = debounce(fetchData, 300);

// Fungsi untuk sorting berdasarkan kolom yang dipilih
const sortBy = (field) => {
    if (sorting.value.field === field) {
        sorting.value.direction = sorting.value.direction === 'asc' ? 'desc' : 'asc';
    } else {
        sorting.value.field = field;
        sorting.value.direction = 'asc';
    }
    fetchData();
};

// Fetch data pertama kali saat komponen dimuat
onMounted(() => {
    fetchData();
})

// Watcher untuk memantau perubahan pagination
watch(() => pagination.value, () => { }, { deep: true });

// Fungsi untuk mengubah halaman
const handlePageChange = (newPageIndex) => {
    pagination.value.pageIndex = newPageIndex;
    fetchData();
};
</script>

<style scope src="vue-multiselect/dist/vue-multiselect.css"></style>

<template>

    <!-- Mengatur judul halaman -->
    <Head title="Daftar Transaksi Sementara" />

    <AdminLayout>
        <!-- Judul Halaman -->
        <h1 class="text-2xl font-semibold text-gray-900">Daftar Transaksi Sementara</h1>

        <!-- Input Pencarian -->
        <div class="flex flex-col md:flex-row justify-end">
            <div class="flex items-center py-4 w-full md:w-72">
                <Input placeholder="Cari Transaksi Sementara..." v-model="globalFilter"
                    class="w-full max-w-full md:max-w-sm" @input="debouncedFetchData" />
            </div>
        </div>

        <!-- Tabel Data Transaksi -->
        <div>
            <div class="rounded-md border">
                <Table>
                    <!-- Header Tabel dengan opsi sorting -->
                    <TableHeaderWrapper :columns="columns" :sorting="sorting" @sort="sortBy" />

                    <!-- Body Tabel -->
                    <TableBody>
                        <!-- Looping untuk setiap baris data -->
                        <TableRow v-for="(row, index) in table.getRowModel().rows" :key="row.id">
                            <!-- Kolom pertama untuk nomor urut -->
                            <TableCell>
                                {{ (pagination.pageIndex) * 10 + index + 1 }}
                            </TableCell>

                            <!-- Kolom data lainnya -->
                            <TableCell v-for="cell in row.getVisibleCells()" :key="cell.id">
                                {{ cell.getValue() }}
                            </TableCell>

                            <!-- Kolom aksi hapus -->
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
            <DialogWrapper v-model:open="isDeleteModalOpen" title="Hapus Transaksi Sementara"
                desc="Hapus transaksi Sementara">
                <DialogFooter>
                    <Button @click="isDeleteModalOpen = false" variant="outline">Batal</Button>
                    <Button @click="deleteCart" variant="destructive">Hapus</Button>
                </DialogFooter>
            </DialogWrapper>
        </div>
    </AdminLayout>
</template>
