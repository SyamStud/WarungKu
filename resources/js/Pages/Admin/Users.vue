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
import Button from '@/Components/ui/button/Button.vue';
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

const Toast = useToast();

/* MODAL */
const isAddModalOpen = ref(false);
const isEditModalOpen = ref(false);
const isDeleteModalOpen = ref(false);
const selectedUser = ref(null);
const isEdit = ref(false);

const openAddModal = () => {
    form.setValues({
        nama: '',
        email: '',
        telepon: '',
    });
    isEdit.value = false;
    isAddModalOpen.value = true;
};

const openEditModal = (user) => {
    isEdit.value = true;
    selectedUser.value = user;
    form.resetForm();
    form.setValues({
        nama: user.nama,
        email: user.email,
        telepon: user.telepon || '',
        no_kk: user.no_kk || '',
        alamat_domisili: user.alamat_domisili || '',
        status: user.status || '',
        role: user.roles || '',
    });
    isEditModalOpen.value = true;
};

const openDeleteModal = (user) => {
    selectedUser.value = user;
    isDeleteModalOpen.value = true;
};

// VALIDATION FRONT END FORM
const addFormSchema = toTypedSchema(z.object({
    nama: z.string().min(2).max(50),
    email: z.string().email(),
    password: z.string().min(8).max(20),
    foto: z.any().optional(),
}));

const editFormSchema = toTypedSchema(z.object({
    nama: z.string().min(2).max(50),
    email: z.string().email(),
    telepon: z.string().min(10).max(15),
    no_kk: z.string().min(16),
    alamat_domisili: z.string().min(10).max(255),
    status: z.string().min(2).max(50),
    role: z.string().min(2).max(50),
}));

const form = useForm({
    validationSchema: computed(() => isEdit.value ? editFormSchema : addFormSchema),
});

let isLoading = ref(false);

// ACTION FORM 
const onSubmit = form.handleSubmit(async (values) => {
    try {
        isLoading.value = true;
        let response;
        if (isEdit.value) {
            response = await axios.put(`/admin/pengguna/${selectedUser.value.id}`, values);
        } else {
            response = await axios.post('/admin/pengguna', values, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });
        }

        if (response.data.status === 'error') {
            return Toast.fire({
                icon: "error",
                title: "pengguna gagal ditambahkan",
            });
        } else {
            Toast.fire({
                icon: "success",
                title: "pengguna berhasil ditambahkan",
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

const deleteUser = async () => {
    if (selectedUser.value) {
        try {
            const response = await axios.post(`/admin/pengguna/${selectedUser.value.id}?_method=DELETE`);
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
            console.error('Error deleting user:', error);
        }
    }
};

// FILE UPLOAD
const onFileChange = (event) => {
    const file = event.target.files[0];
    form.setValues({ foto: file });
    console.log('Selected file:', file);
};

/* TABLE */
const columns = [
    { accessorKey: 'nama', header: 'Nama' },
    { accessorKey: 'email', header: 'Email' },
    { accessorKey: 'telepon', header: 'Telepon' },
    { accessorKey: 'no_kk', header: 'Nomor KK' },
    { accessorKey: 'alamat_domisili', header: 'Alamat Domisili' },
    { accessorKey: 'roles', header: 'Role' },
    { accessorKey: 'status', header: 'Status' },
];

console.log('columns:', columns);

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

const fetchData = async () => {
    try {
        const response = await axios.get('/api/users', {
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

const debouncedFetchData = debounce(fetchData, 300);

const sortBy = (field) => {
    if (sorting.value.field === field) {
        sorting.value.direction = sorting.value.direction === 'asc' ? 'desc' : 'asc';
    } else {
        sorting.value.field = field;
        sorting.value.direction = 'asc';
    }
    fetchData();
};

onMounted(fetchData);

watch(() => pagination.value, () => { }, { deep: true });

const handlePageChange = (newPageIndex) => {
    pagination.value.pageIndex = newPageIndex;
    fetchData();
};
</script>


<template>

    <Head title="Daftar Pengguna" />

    <AdminLayout>
        <h1 class="text-2xl font-semibold text-gray-900">Daftar Pengguna</h1>
        <div class="flex justify-between">
            <Button @click="openAddModal()" class="mt-4 bg-green-700 hover:bg-green-800">Tambah Pengguna</Button>
            <div class="flex items-center py-4 w-72">
                <Input placeholder="Cari Pengguna..." v-model="globalFilter" class="max-w-sm"
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
                                    <Button @click="() => openEditModal(row.original)">Edit</Button>
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
            <DialogWrapper v-model:open="isAddModalOpen" title="Tambah Pengguna"
                desc="Pengguna harus melengkapi data sendiri.">
                <form @submit="onSubmit" enctype="multipart/form-data" class="space-y-4">
                    <FormInput name="nama" label="Nama" type="text" />
                    <FormInput name="email" label="Email" type="text" />
                    <FormInput name="password" label="Password" type="password" />
                    <DialogFooter>
                        <Button type="submit" :class="{ 'bg-slate-500': isLoading }" :disabled="isLoading">
                            {{ isLoading ? 'Mohon tunggu ...' : 'Tambah Pengguna' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogWrapper>

            <!-- Edit Modal -->
            <DialogWrapper v-model:open="isEditModalOpen" title="Ubah Pengguna" desc="Ubah pengguna">
                <form @submit="onSubmit">
                    <div class="grid gap-4 py-4">
                        <div class="flex gap-4">
                            <FormInput name="nama" label="Nama" placeholder="shadcn" type="text" />
                            <FormInput name="email" label="Email" placeholder="shadcn" type="text" />
                        </div>
                        <div class="flex gap-4">
                            <FormInput name="telepon" label="Nomor Telepon" placeholder="shadcn" type="text" />
                            <FormInput name="no_kk" label="Nomor Kartu Keluarga" placeholder="shadcn" type="text" />
                        </div>
                        <div class="flex gap-4">
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
                                                    <SelectItem value="aktif">
                                                        Aktif
                                                    </SelectItem>
                                                    <SelectItem value="nonaktif">
                                                        Nonaktif
                                                    </SelectItem>
                                                </SelectGroup>
                                            </SelectContent>
                                        </Select>
                                        <FormMessage />
                                    </FormItem>
                                </FormField>
                            </div>
                            <div class="w-full">
                                <FormField v-slot="{ componentField }" name="role">
                                    <FormItem>
                                        <FormLabel>Role</FormLabel>
                                        <Select v-bind="componentField">
                                            <FormControl>
                                                <SelectTrigger>
                                                    <SelectValue placeholder="Pilih Role" />
                                                </SelectTrigger>
                                            </FormControl>
                                            <SelectContent>
                                                <SelectGroup>
                                                    <SelectItem value="user">
                                                        User
                                                    </SelectItem>
                                                    <SelectItem value="admin">
                                                        Admin
                                                    </SelectItem>
                                                </SelectGroup>
                                            </SelectContent>
                                        </Select>
                                        <FormMessage />
                                    </FormItem>
                                </FormField>
                            </div>
                        </div>
                        <FormField v-slot="{ componentField }" name="alamat_domisili">
                            <FormItem>
                                <FormLabel>Alamat Domisili</FormLabel>
                                <FormControl>
                                    <Textarea placeholder="Masukkan alamat saat ini." class="resize-none"
                                        v-bind="componentField" />
                                </FormControl>
                                <FormMessage />
                            </FormItem>
                        </FormField>
                    </div>
                    <DialogFooter>
                        <Button type="submit">
                            Simpan Perubahan
                        </Button>
                    </DialogFooter>
                </form>
            </DialogWrapper>

            <!-- Delete Modal -->
            <DialogWrapper v-model:open="isDeleteModalOpen" title="Hapus Pengguna" desc="Hapus pengguna">
                <DialogFooter>
                    <Button @click="isDeleteModalOpen = false" variant="outline">Batal</Button>
                    <Button @click="deleteUser" variant="destructive">Hapus</Button>
                </DialogFooter>
            </DialogWrapper>
        </div>
    </AdminLayout>
</template>
