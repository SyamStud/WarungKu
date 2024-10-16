<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import axios from 'axios';
import debounce from 'lodash/debounce';
import * as z from 'zod';
import { useForm } from 'vee-validate';
import { toTypedSchema } from '@vee-validate/zod';
import { Head } from '@inertiajs/vue3';
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

// Inisialisasi Toast untuk notifikasi
const Toast = useToast();

const isLoading = ref(false);

/* MODAL */
// State untuk modal tambah, edit, dan hapus pelanggan
const isAddModalOpen = ref(false);
const isEditModalOpen = ref(false);
const isDeleteModalOpen = ref(false);
const selectedCustomer = ref(null);
const isEdit = ref(false);

// Fungsi untuk membuka modal tambah pelanggan
const openAddModal = () => {
    form.setValues({ name: '', phone: '', address: '' });
    isEdit.value = false;
    isAddModalOpen.value = true;
};

// Fungsi untuk membuka modal edit pelanggan
const openEditModal = (customer) => {
    isEdit.value = true;
    selectedCustomer.value = customer;
    form.resetForm();
    form.setValues({
        name: customer.name,
        phone: customer.phone,
        address: customer.address,
    });
    isEditModalOpen.value = true;
};

// Fungsi untuk membuka modal hapus pelanggan
const openDeleteModal = (customer) => {
    selectedCustomer.value = customer;
    isDeleteModalOpen.value = true;
};

// Validasi form tambah/edit menggunakan Zod
const addFormSchema = toTypedSchema(z.object({
    name: z.string().min(2).max(50),
    phone: z.any().optional(),
    address: z.string().optional(),
}));

const editFormSchema = toTypedSchema(z.object({
    name: z.string().min(2).max(50),
    phone: z.any().optional(),
    address: z.string().optional(),
}));

const form = useForm({
    validationSchema: computed(() => isEdit.value ? editFormSchema : addFormSchema),
});

// Fungsi submit form untuk tambah/edit pelanggan
const onSubmit = form.handleSubmit(async (values) => {
    try {
        isLoading.value = true;
        let response;
        // Cek apakah mode edit atau tambah
        if (isEdit.value) {
            response = await axios.post(`/admin/customers/${selectedCustomer.value.id}?_method=PUT`, values);
        } else {
            response = await axios.post('/admin/customers', values);
        }

        // Menampilkan notifikasi sukses atau error
        if (response.data.status === 'error') {
            isLoading.value = false;
            return Toast.fire({ icon: "error", title: response.data.message });
        } else {
            Toast.fire({ icon: "success", title: response.data.message });
        }

        isEdit.value ? (isEditModalOpen.value = false) : (isAddModalOpen.value = false);
        fetchData();
        isLoading.value = false;
    } catch (error) {
        console.error('Error submitting form:', error);
        isLoading.value = false;
    }
});

// Fungsi untuk menghapus pelanggan
const deleteCustomer = async () => {
    if (selectedCustomer.value) {
        try {
            const response = await axios.post(`/admin/customers/${selectedCustomer.value.id}?_method=DELETE`);
            if (response.data.status === 'error') {
                return Toast.fire({ icon: "error", title: response.data.message });
            } else {
                Toast.fire({ icon: "success", title: response.data.message });
            }
            isDeleteModalOpen.value = false;
            fetchData();
        } catch (error) {
            console.error('Error deleting customer:', error);
        }
    }
};

/* TABLE */
// Definisi kolom tabel
const columns = [
    { accessorKey: 'name', header: 'Nama Pelanggan' },
    { accessorKey: 'phone', header: 'Nomor Telepon' },
    { accessorKey: 'address', header: 'Alamat' },
];

const data = ref([]);
const globalFilter = ref('');  // Filter pencarian
const pagination = ref({
    pageIndex: 0,
    pageSize: 10,
    pageCount: 1,
    total: 0,
});

// Sorting untuk tabel
const sorting = ref({ field: 'id', direction: 'asc' });

// Inisialisasi table dengan pagination manual
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
        pagination.value = typeof updater === 'function' ? updater(pagination.value) : updater;
        fetchData();
    }
});

// Fungsi untuk mengambil data pelanggan dari server
const fetchData = async () => {
    try {
        const response = await axios.get('/api/customers', {
            params: {
                search: globalFilter.value,
                page: pagination.value.pageIndex + 1,
                per_page: pagination.value.pageSize,
                sort: sorting.value.field,
                direction: sorting.value.direction,
            }
        });

        // Menyimpan data dan informasi pagination
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

// Fungsi debounce untuk optimisasi pengambilan data
const debouncedFetchData = debounce(fetchData, 300);

// Fungsi sorting untuk tabel
const sortBy = (field) => {
    sorting.value = {
        field,
        direction: sorting.value.field === field && sorting.value.direction === 'asc' ? 'desc' : 'asc',
    };
    fetchData();
};

// Watch pagination untuk memantau perubahan
watch(() => pagination.value, () => { }, { deep: true });

// Mengambil data awal saat komponen dimount
onMounted(fetchData);

// Fungsi untuk mengubah halaman tabel
const handlePageChange = (newPageIndex) => {
    pagination.value.pageIndex = newPageIndex;
    fetchData();
};
</script>


<template>
    <!-- Mengatur judul halaman -->
    <Head title="Daftar Pelanggan" />

    <AdminLayout>
        <!-- Judul Halaman -->
        <h1 class="text-2xl font-semibold text-gray-900">Daftar Pelanggan</h1>

        <!-- Button Tambah Kategori dan Input Pencarian -->
        <div class="flex flex-col md:flex-row justify-between">
            <Button @click="openAddModal()" class="w-full md:w-max mt-4 bg-green-700 hover:bg-green-800">Tambah
                Pelanggan</Button>
            <div class="flex items-center py-4 w-full md:w-72">
                <Input placeholder="Cari Pelanggan..." v-model="globalFilter" class="w-full max-w-full md:max-w-sm"
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
                                {{ cell.getValue() ? cell.getValue() : '-' }}
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

            <!-- Add Modal -->
            <DialogWrapper v-model:open="isAddModalOpen" title="Tambah Pelanggan" desc="Tambah pelanggan">
                <form @submit="onSubmit" enctype="multipart/form-data" class="space-y-4">
                    <FormInput name="name" label="Nama" type="text" />
                    <FormInput name="phone" label="Nomor Telepon" type="text" />

                    <FormField v-slot="{ componentField }" name="address">
                        <FormItem>
                            <FormLabel>Alamat</FormLabel>
                            <FormControl>
                                <Textarea placeholder="Masukkan alamat" class="resize-none" v-bind="componentField" />
                            </FormControl>
                            <FormMessage />
                        </FormItem>
                    </FormField>

                    <DialogFooter>
                        <Button type="submit" :class="{ 'bg-slate-500': isLoading }" :disabled="isLoading">
                            {{ isLoading ? 'Mohon tunggu ...' : 'Tambah Pelanggan' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogWrapper>

            <!-- Edit Modal -->
            <DialogWrapper v-model:open="isEditModalOpen" title="Ubah Pelanggan" desc="Ubah pelanggan">
                <form @submit="onSubmit" enctype="multipart/form-data" class="space-y-4">
                    <FormInput name="name" label="Nama" type="text" />
                    <FormInput name="phone" label="Nomor Telepon" type="text" />

                    <FormField v-slot="{ componentField }" name="address">
                        <FormItem>
                            <FormLabel>Alamat</FormLabel>
                            <FormControl>
                                <Textarea placeholder="Masukkan alamat" class="resize-none" v-bind="componentField" />
                            </FormControl>
                            <FormMessage />
                        </FormItem>
                    </FormField>

                    <DialogFooter>
                        <Button type="submit" :class="{ 'bg-slate-500': isLoading }" :disabled="isLoading">
                            {{ isLoading ? 'Mohon tunggu ...' : 'Tambah Pelanggan' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogWrapper>

            <!-- Delete Modal -->
            <DialogWrapper v-model:open="isDeleteModalOpen" title="Hapus Pelanggan" desc="Hapus pelanggan">
                <DialogFooter>
                    <Button @click="isDeleteModalOpen = false" variant="outline">Batal</Button>
                    <Button @click="deleteCustomer" variant="destructive">Hapus</Button>
                </DialogFooter>
            </DialogWrapper>
        </div>
    </AdminLayout>
</template>
