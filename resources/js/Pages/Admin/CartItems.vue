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

// Inisialisasi Toast untuk notifikasi
const Toast = useToast();
const { formatRupiah } = useFormatRupiah();

/* MODAL */
const isDeleteModalOpen = ref(false);
const selectedCartItem = ref(null);

// Fungsi untuk membuka modal hapus produk
const openDeleteModal = (cartItem) => {
    selectedCartItem.value = cartItem;
    isDeleteModalOpen.value = true;
};

// Fungsi untuk menghapus produk yang dipilih
const deleteCartItem = async () => {
    if (selectedCartItem.value) {
        try {
            // Mengirim permintaan ke server untuk menghapus item
            const response = await axios.post(`/admin/cart-items/${selectedCartItem.value.id}?_method=DELETE`);

            // Menampilkan notifikasi berdasarkan respons
            if (response.data.status === 'error') {
                return Toast.fire({
                    icon: "error",
                    title: response.data.message,
                });
            } else {
                Toast.fire({
                    icon: "success",
                    title: response.data.message,
                });
            }

            // Menutup modal dan refresh data setelah penghapusan
            isDeleteModalOpen.value = false;
            fetchData();
        } catch (error) {
            console.error('Error deleting cartItem:', error);
        }
    }
};

/* TABLE SETUP */
// Definisi kolom-kolom untuk tabel
const columns = [
    { accessorKey: 'cart', header: 'Kode Transaksi' },
    { accessorKey: 'product', header: 'Produk' },
    { accessorKey: 'variant', header: 'Variasi' },
    { accessorKey: 'quantity', header: 'Jumlah' },
    { accessorKey: 'price', header: 'Harga Satuan' },
    { accessorKey: 'total_price', header: 'Total Harga' },
    { accessorKey: 'discount', header: 'Diskon' },
    { accessorKey: 'discounted_total_price', header: 'Total Akhir' },
];

// State data untuk tabel
const data = ref([]);
const globalFilter = ref('');
const pagination = ref({
    pageIndex: 0,
    pageSize: 10,
    pageCount: 1,
    total: 0,
});

// Mengatur sorting default
const sorting = ref({ field: 'id', direction: 'asc' });

// Inisialisasi tabel menggunakan Vue Table
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
        const response = await axios.get('/api/cart-items', {
            params: {
                search: globalFilter.value,
                page: pagination.value.pageIndex + 1,
                per_page: pagination.value.pageSize,
                sort: sorting.value.field,
                direction: sorting.value.direction,
            }
        });

        // Memperbarui data dan informasi pagination
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

// Fungsi untuk debouncing pencarian
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

// Memanggil data ketika komponen dimuat pertama kali
onMounted(() => {
    fetchData();
});

// Mengawasi perubahan pada pagination
watch(() => pagination.value, () => { }, { deep: true });

// Fungsi untuk mengubah halaman pada pagination
const handlePageChange = (newPageIndex) => {
    pagination.value.pageIndex = newPageIndex;
    fetchData();
};
</script>

<style scope src="vue-multiselect/dist/vue-multiselect.css"></style>

<template>
    <!-- Mengatur judul halaman -->

    <Head title="Daftar Item Transaksi Sementara" />

    <AdminLayout>
        <!-- Judul Halaman -->
        <h1 class="text-2xl font-semibold text-gray-900">Daftar Item Transaksi Sementara</h1>

        <!-- Input pencarian -->
        <div class="flex flex-col md:flex-row justify-end">
            <div class="flex items-center py-4 w-full md:w-72">
                <Input placeholder="Cari Item Transaksi Sementara..." v-model="globalFilter"
                    class="w-full max-w-full md:max-w-sm" @input="debouncedFetchData" />
            </div>
        </div>

        <div>
            <!-- Tabel -->
            <div class="rounded-md border">
                <Table>
                    <TableHeaderWrapper :columns="columns" :sorting="sorting" @sort="sortBy" />

                    <TableBody>
                        <TableRow v-for="(row, index) in table.getRowModel().rows" :key="row.id">
                            <!-- Kolom untuk nomor urut -->
                            <TableCell>
                                {{ (pagination.pageIndex) * 10 + index + 1 }}
                            </TableCell>

                            <!-- Kolom data dari tabel -->
                            <TableCell v-for="cell in row.getVisibleCells()" :key="cell.id">
                                <template v-if="cell.column.id === 'price' || cell.column.id === 'total_price' || cell.column.id === 'discount'
                                    || cell.column.id === 'discounted_total_price'">
                                    {{ formatRupiah(cell.getValue()) }}
                                </template>

                                <template v-else>
                                    {{ cell.getValue() }}
                                </template>
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

            <!-- Modal hapus -->
            <DialogWrapper v-model:open="isDeleteModalOpen" title="Hapus Produk" desc="Hapus produk">
                <DialogFooter>
                    <Button @click="isDeleteModalOpen = false" variant="outline">Batal</Button>
                    <Button @click="deleteCartItem" variant="destructive">Hapus</Button>
                </DialogFooter>
            </DialogWrapper>
        </div>
    </AdminLayout>
</template>
