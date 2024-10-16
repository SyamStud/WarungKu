<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import axios from 'axios';
import debounce from 'lodash/debounce';
import * as z from 'zod';
import { useForm } from 'vee-validate';
import { toTypedSchema } from '@vee-validate/zod';
import { Head, Link } from '@inertiajs/vue3';
import { useVueTable, getCoreRowModel, getPaginationRowModel } from '@tanstack/vue-table';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Input } from '@/Components/ui/input/index.js';
import { useToast } from '@/Composables/useToast';
import TableHeaderWrapper from '@/Components/ui/table/TableHeaderWrapper.vue';
import { DialogFooter } from '@/Components/ui/dialog';
import DialogWrapper from '@/Components/ui/dialog/DialogWrapper.vue';
import FormInput from '@/Components/ui/form/FormInput.vue';
import { Table, TableBody, TableCell, TableRow } from '@/Components/ui/table';
import PaginationWrapper from '@/Components/ui/pagination/PaginationWrapper.vue';
import { FormField } from '@/Components/ui/form';
import FormItem from '@/Components/ui/form/FormItem.vue';
import FormLabel from '@/Components/ui/form/FormLabel.vue';
import FormControl from '@/Components/ui/form/FormControl.vue';
import Textarea from '@/Components/ui/textarea/Textarea.vue';
import FormMessage from '@/Components/ui/form/FormMessage.vue';
import Button from '@/components/ui/button/Button.vue';
import { useFormatRupiah } from '@/Composables/useFormatRupiah';

const Toast = useToast();
const { formatRupiah } = useFormatRupiah();

/* MODAL */
const isEditModalOpen = ref(false);
const isDeleteModalOpen = ref(false);
const selectedPurchase = ref(null);
const isEdit = ref(false);

// Fungsi untuk membuka modal edit dan mengisi form dengan data purchase yang dipilih
const openEditModal = (purchase) => {
    isEdit.value = true;
    selectedPurchase.value = purchase;
    form.resetForm();
    form.setValues({
        quantity: purchase.quantity,
        price: purchase.price,
        note: purchase.note,
    });
    isEditModalOpen.value = true;
};

// Fungsi untuk membuka modal delete dan menyimpan data purchase yang dipilih
const openDeleteModal = (purchase) => {
    selectedPurchase.value = purchase;
    isDeleteModalOpen.value = true;
};

// Skema validasi untuk form edit menggunakan zod dan vee-validate
const editFormSchema = toTypedSchema(z.object({
    quantity: z.any(),
    price: z.any(),
    note: z.any(),
}));

// Inisialisasi form dengan vee-validate
const form = useForm({
    validationSchema: computed(() => editFormSchema),
});

let isLoading = ref(false); // State untuk loading

// Fungsi untuk submit form edit
const onSubmit = form.handleSubmit(async (values) => {
    try {
        isLoading.value = true;

        let response = await axios.post(`/admin/purchases/${selectedPurchase.value.id}?_method=PUT`, values);

        if (response.data.status === 'error') {
            isLoading.value = false;

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

        isEditModalOpen.value = false;
        fetchData();
        isLoading.value = false;
    } catch (error) {
        console.error('Error submitting form:', error);
        isLoading.value = false;
    }
});

// Fungsi untuk menghapus purchase
const deletePurchase = async () => {
    if (selectedPurchase.value) {
        try {
            const response = await axios.post(`/admin/purchases/${selectedPurchase.value.id}?_method=DELETE`);
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

            isDeleteModalOpen.value = false;
            fetchData();
        } catch (error) {
            console.error('Error deleting purchase:', error);
        }
    }
};

/* TABLE */
const columns = [
    { accessorKey: 'product', header: 'Nama Produk' },
    { accessorKey: 'supplier', header: 'Nama Supplier' },
    { accessorKey: 'quantity', header: 'Kuantitas' },
    { accessorKey: 'price', header: 'Total Harga' },
    { accessorKey: 'note', header: 'Catatan' },
    { accessorKey: 'created_at', header: 'Tanggal Pencatatan' },
    { accessorKey: 'user_id', header: 'Dicatat Oleh' },
];

const data = ref([]); // Data untuk tabel
const globalFilter = ref(''); // Filter global untuk pencarian
const pagination = ref({
    pageIndex: 0,
    pageSize: 10,
    pageCount: 1,
    total: 0,
});

const sorting = ref({ field: 'id', direction: 'asc' }); // State untuk sorting

// Inisialisasi tabel dengan vue-table
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
        const response = await axios.get('/api/purchases', {
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

const debouncedFetchData = debounce(fetchData, 300); // Fungsi untuk debounce pencarian

// Fungsi untuk sorting kolom tabel
const sortBy = (field) => {
    if (sorting.value.field === field) {
        sorting.value.direction = sorting.value.direction === 'asc' ? 'desc' : 'asc';
    } else {
        sorting.value.field = field;
        sorting.value.direction = 'asc';
    }
    fetchData();
};

onMounted(fetchData); // Ambil data saat komponen dimount

watch(() => pagination.value, () => { }, { deep: true }); // Watcher untuk pagination

// Fungsi untuk mengubah halaman
const handlePageChange = (newPageIndex) => {
    pagination.value.pageIndex = newPageIndex;
    fetchData();
};
</script>


<template>

    <Head title="Daftar Purchase" />

    <AdminLayout>
        <h1 class="text-2xl font-semibold text-gray-900">Riwayat Purchase Produk Supplier</h1>
        <div class="flex flex-col md:flex-row justify-between">
            <Link href="/admin/purchases/create">
            <Button class="w-full md:w-max mt-4 bg-green-700 hover:bg-green-800">Purchase Produk Supplier</Button>
            </Link>
            <div class="flex items-center py-4 w-full md:w-72">
                <Input placeholder="Cari Purchase..." v-model="globalFilter" class="w-full max-w-full md:max-w-sm"
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
                                <template v-if="cell.column.id === 'price'">
                                    {{ formatRupiah(cell.getValue()) }}
                                </template>

                                <template v-else>
                                    {{ cell.getValue() }}
                                </template>
                            </TableCell>

                            <!-- Kolom untuk aksi -->
                            <TableCell>
                                <div class="flex gap-2">
                                    <Button @click="() => openEditModal(row.original)">Ubah</Button>
                                    <Button @click="() => openDeleteModal(row.original)">Hapus</Button>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <!-- Pagination -->
            <PaginationWrapper :pagination="pagination" :onPageChange="handlePageChange" />


            <!-- Edit Modal -->
            <DialogWrapper v-model:open="isEditModalOpen" title="Ubah Data Purchase" desc="Ubah data purchase">
                <form @submit="onSubmit" enctype="multipart/form-data" class="space-y-4">
                    <div class="flex gap-4">
                        <div class="w-full">
                            <FormInput name="quantity" label="Kuantitas" type="text" />
                        </div>
                        <div class="w-full">
                            <FormInput name="price" label="Total Harga" type="text" />
                        </div>
                    </div>

                    <FormField v-slot="{ componentField }" name="note">
                        <FormItem>
                            <FormLabel>Catatan</FormLabel>
                            <FormControl>
                                <Textarea placeholder="Masukkan catatan" class="resize-none" v-bind="componentField" />
                            </FormControl>
                            <FormMessage />
                        </FormItem>
                    </FormField>

                    <DialogFooter>
                        <Button type="submit" :class="{ 'bg-slate-500': isLoading }" :disabled="isLoading">
                            {{ isLoading ? 'Mohon tunggu ...' : 'Tambah Purchase' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogWrapper>

            <!-- Delete Modal -->
            <DialogWrapper v-model:open="isDeleteModalOpen" title="Hapus Purchase" desc="Hapus purchase">
                <DialogFooter>
                    <Button @click="isDeleteModalOpen = false" variant="outline">Batal</Button>
                    <Button @click="deletePurchase" variant="destructive">Hapus</Button>
                </DialogFooter>
            </DialogWrapper>
        </div>
    </AdminLayout>
</template>
