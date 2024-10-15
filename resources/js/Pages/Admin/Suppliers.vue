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
import Select from '@/Components/ui/select/Select.vue';
import SelectTrigger from '@/Components/ui/select/SelectTrigger.vue';
import SelectValue from '@/Components/ui/select/SelectValue.vue';
import SelectContent from '@/Components/ui/select/SelectContent.vue';
import SelectGroup from '@/Components/ui/select/SelectGroup.vue';
import SelectItem from '@/Components/ui/select/SelectItem.vue';
import FormMessage from '@/Components/ui/form/FormMessage.vue';
import Button from '@/components/ui/button/Button.vue';

const Toast = useToast();

/* MODAL */
const isAddModalOpen = ref(false); // State untuk modal tambah supplier
const isEditModalOpen = ref(false); // State untuk modal edit supplier
const isDeleteModalOpen = ref(false); // State untuk modal hapus supplier
const selectedSupplier = ref(null); // State untuk supplier yang dipilih
const isEdit = ref(false); // State untuk menentukan mode edit atau tambah

// Fungsi untuk membuka modal tambah supplier
const openAddModal = () => {
    form.setValues({
        name: '',
        status: '',
        address: '',
        contact_name: '',
        contact_phone: '',
        contact_email: '',
        contact_position: '',
    });
    isEdit.value = false;
    isAddModalOpen.value = true; // Buka modal tambah supplier
};

// Fungsi untuk membuka modal edit supplier
const openEditModal = (supplier) => {
    isEdit.value = true;
    selectedSupplier.value = supplier;
    form.resetForm();
    form.setValues({
        name: supplier.name,
        status: supplier.status,
        address: supplier.address,
        contact_name: supplier.contact_name,
        contact_phone: supplier.contact_phone,
        contact_email: supplier.contact_email,
        contact_position: supplier.contact_position,
    });
    isEditModalOpen.value = true; // Buka modal edit supplier
};

// Fungsi untuk membuka modal hapus supplier
const openDeleteModal = (supplier) => {
    selectedSupplier.value = supplier;
    isDeleteModalOpen.value = true; // Buka modal hapus supplier
};

// VALIDASI FORM FRONT END
const addFormSchema = toTypedSchema(z.object({
    name: z.string().min(2).max(50),
    status: z.string().min(2).max(50),
    address: z.string().min(2).max(50),
    contact_name: z.string().min(2).max(50),
    contact_phone: z.string().min(2).max(50),
    contact_email: z.any(),
    contact_position: z.any(),
}));

const editFormSchema = toTypedSchema(z.object({
    name: z.string().min(2).max(50),
    status: z.string().min(2).max(50),
    address: z.string().min(2).max(50),
    contact_name: z.string().min(2).max(50),
    contact_phone: z.string().min(2).max(50),
    contact_email: z.any(),
    contact_position: z.any(),
}));

const form = useForm({
    validationSchema: computed(() => isEdit.value ? editFormSchema : addFormSchema),
});

let isLoading = ref(false);

// AKSI FORM 
const onSubmit = form.handleSubmit(async (values) => {
    try {
        isLoading.value = true;
        let response;
        if (isEdit.value) {
            console.log(values);
            response = await axios.post(`/admin/suppliers/${selectedSupplier.value.id}?_method=PUT`, values);
        } else {
            response = await axios.post('/admin/suppliers', values);
        }

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

        isEdit.value ? (isEditModalOpen.value = false) : (isAddModalOpen.value = false);
        fetchData();
        isLoading.value = false;
    } catch (error) {
        console.error('Error submitting form:', error);
        isLoading.value = false;
    }
});

// Fungsi untuk menghapus supplier
const deleteSupplier = async () => {
    if (selectedSupplier.value) {
        try {
            const response = await axios.post(`/admin/suppliers/${selectedSupplier.value.id}?_method=DELETE`);
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

            isAddModalOpen.value = false;
            isDeleteModalOpen.value = false;
            fetchData();
        } catch (error) {
            console.error('Error deleting supplier:', error);
        }
    }
};

/* TABEL */
const columns = [
    { accessorKey: 'name', header: 'Nama' },
    { accessorKey: 'status', header: 'Status' },
    { accessorKey: 'address', header: 'Alamat' },
    { accessorKey: 'contact_name', header: 'Nama Narahubung' },
    { accessorKey: 'contact_phone', header: 'Telepon Narahubung' },
    { accessorKey: 'contact_email', header: 'Email Narahubung' },
    { accessorKey: 'contact_position', header: 'Jabatan Narahubung' },
];

const data = ref([]);
const globalFilter = ref('');
const pagination = ref({
    pageIndex: 0,
    pageSize: 10,
    pageCount: 1,
    total: 0,
});

const sorting = ref({ field: 'id', direction: 'asc' });

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
        const response = await axios.get('/api/suppliers', {
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

// Fungsi untuk mengurutkan data
const sortBy = (field) => {
    if (sorting.value.field === field) {
        sorting.value.direction = sorting.value.direction === 'asc' ? 'desc' : 'asc';
    } else {
        sorting.value.field = field;
        sorting.value.direction = 'asc';
    }
    fetchData();
};

// Memanggil fetchData saat komponen dimuat
onMounted(fetchData);

// Menonton perubahan pada pagination
watch(() => pagination.value, () => { }, { deep: true });

// Fungsi untuk menangani perubahan halaman
const handlePageChange = (newPageIndex) => {
    pagination.value.pageIndex = newPageIndex;
    fetchData();
};
</script>


<template>

    <Head title="Daftar Supplier" />

    <AdminLayout>
        <h1 class="text-2xl font-semibold text-gray-900">Daftar Supplier</h1>
        <div class="flex flex-col md:flex-row justify-between">
            <Button @click="openAddModal()" class="w-full md:w-max mt-4 bg-green-700 hover:bg-green-800">Tambah
                Supplier</Button>
            <div class="flex items-center py-4 w-full md:w-72">
                <Input placeholder="Cari Supplier..." v-model="globalFilter" class="w-full max-w-full md:max-w-sm"
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
                                {{ cell.getValue() }}
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
            <DialogWrapper style="width: 70rem;" v-model:open="isAddModalOpen" title="Tambah Supplier"
                desc="Tambah supplier">
                <form @submit="onSubmit" enctype="multipart/form-data" class="space-y-4">
                    <div class="flex gap-4">
                        <div class="w-full">
                            <FormInput name="name" label="Nama" type="text" />
                        </div>
                        <div class="w-full">
                            <FormField v-slot="{ componentField }" name="status">
                                <FormItem>
                                    <FormLabel>Status</FormLabel>
                                    <Select v-bind="componentField">
                                        <FormControl>
                                            <SelectTrigger>
                                                <SelectValue placeholder="Pilih Status" />
                                            </SelectTrigger>
                                        </FormControl>
                                        <SelectContent>
                                            <SelectGroup>
                                                <SelectItem value="active">
                                                    Aktif
                                                </SelectItem>
                                                <SelectItem value="inactive">
                                                    Tidak Aktif
                                                </SelectItem>
                                                <SelectItem value="draft">
                                                    Draft
                                                </SelectItem>
                                            </SelectGroup>
                                        </SelectContent>
                                    </Select>
                                    <FormMessage />
                                </FormItem>
                            </FormField>
                        </div>
                    </div>

                    <FormField v-slot="{ componentField }" name="address">
                        <FormItem>
                            <FormLabel>Alamat</FormLabel>
                            <FormControl>
                                <Textarea placeholder="Masukkan alamat" class="resize-none" v-bind="componentField" />
                            </FormControl>
                            <FormMessage />
                        </FormItem>
                    </FormField>

                    <div class="py-2 flex items-center mb-4 pt-5">
                        <hr class="mt-1 mr-2 w-2 border-gray-300" />
                        <p class="text-sm font-bold text-gray-500">Narahubung</p>
                        <hr class="mt-1 flex-grow ml-2 border-gray-300" />
                    </div>


                    <div class="flex gap-4">
                        <FormInput name="contact_name" label="Nama Narahubung" type="text" />
                        <FormInput name="contact_phone" label="Telepon Narahubung" type="text" />
                    </div>
                    <div class="flex gap-4">
                        <FormInput name="contact_email" label="Email Narahubung" type="text" />
                        <FormInput name="contact_position" label="Jabatan Narahubung" type="text" />
                    </div>

                    <DialogFooter>
                        <Button type="submit" :class="{ 'bg-slate-500': isLoading }" :disabled="isLoading">
                            {{ isLoading ? 'Mohon tunggu ...' : 'Tambah Supplier' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogWrapper>

            <!-- Edit Modal -->
            <DialogWrapper v-model:open="isEditModalOpen" title="Ubah Supplier" desc="Ubah supplier">
                <form @submit="onSubmit" enctype="multipart/form-data" class="space-y-4">
                    <div class="flex gap-4">
                        <div class="w-full">
                            <FormInput name="name" label="Nama" type="text" />
                        </div>
                        <div class="w-full">
                            <FormField v-slot="{ componentField }" name="status">
                                <FormItem>
                                    <FormLabel>Status</FormLabel>
                                    <Select v-bind="componentField">
                                        <FormControl>
                                            <SelectTrigger>
                                                <SelectValue placeholder="Pilih Status" />
                                            </SelectTrigger>
                                        </FormControl>
                                        <SelectContent>
                                            <SelectGroup>
                                                <SelectItem value="active">
                                                    Aktif
                                                </SelectItem>
                                                <SelectItem value="inactive">
                                                    Tidak Aktif
                                                </SelectItem>
                                                <SelectItem value="draft">
                                                    Draft
                                                </SelectItem>
                                            </SelectGroup>
                                        </SelectContent>
                                    </Select>
                                    <FormMessage />
                                </FormItem>
                            </FormField>
                        </div>
                    </div>

                    <FormField v-slot="{ componentField }" name="address">
                        <FormItem>
                            <FormLabel>Alamat</FormLabel>
                            <FormControl>
                                <Textarea placeholder="Masukkan alamat" class="resize-none" v-bind="componentField" />
                            </FormControl>
                            <FormMessage />
                        </FormItem>
                    </FormField>

                    <div class="py-2 flex items-center mb-4 pt-5">
                        <hr class="mt-1 mr-2 w-2 border-gray-300" />
                        <p class="text-sm font-bold text-gray-500">Narahubung</p>
                        <hr class="mt-1 flex-grow ml-2 border-gray-300" />
                    </div>


                    <div class="flex gap-4">
                        <FormInput name="contact_name" label="Nama Narahubung" type="text" />
                        <FormInput name="contact_phone" label="Telepon Narahubung" type="text" />
                    </div>
                    <div class="flex gap-4">
                        <FormInput name="contact_email" label="Email Narahubung" type="text" />
                        <FormInput name="contact_position" label="Jabatan Narahubung" type="text" />
                    </div>

                    <DialogFooter>
                        <Button type="submit" :class="{ 'bg-slate-500': isLoading }" :disabled="isLoading">
                            {{ isLoading ? 'Mohon tunggu ...' : 'Tambah Supplier' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogWrapper>

            <!-- Delete Modal -->
            <DialogWrapper v-model:open="isDeleteModalOpen" title="Hapus Supplier" desc="Hapus supplier">
                <DialogFooter>
                    <Button @click="isDeleteModalOpen = false" variant="outline">Batal</Button>
                    <Button @click="deleteSupplier" variant="destructive">Hapus</Button>
                </DialogFooter>
            </DialogWrapper>
        </div>
    </AdminLayout>
</template>
