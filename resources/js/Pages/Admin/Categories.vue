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
import TableHead from '@/Components/ui/table/TableHead.vue';

const Toast = useToast();

/* MODAL */
const isAddModalOpen = ref(false);
const isEditModalOpen = ref(false);
const isDeleteModalOpen = ref(false);
const isPhotoModalOpen = ref(false);
const selectedCategory = ref(null);
const isEdit = ref(false);

const openAddModal = () => {
    form.setValues({
        name: '',
    });
    isEdit.value = false;
    isAddModalOpen.value = true;
};

const openEditModal = (category) => {
    isEdit.value = true;
    selectedCategory.value = category;
    form.resetForm();
    form.setValues({
        name: category.name,
    });
    isEditModalOpen.value = true;
};

const openDeleteModal = (category) => {
    selectedCategory.value = category;
    isDeleteModalOpen.value = true;
};

const openPhotoModal = (category) => {
    selectedCategory.value = category;
    isPhotoModalOpen.value = true;
};

// VALIDATION FRONT END FORM
const addFormSchema = toTypedSchema(z.object({
    name: z.string().min(2).max(50),
}));

const editFormSchema = toTypedSchema(z.object({
    name: z.string().min(2).max(50),
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
            console.log(values);
            response = await axios.post(`/admin/categories/${selectedCategory.value.id}?_method=PUT`, values);
        } else {
            response = await axios.post('/admin/categories', values);
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

const deleteCategory = async () => {
    if (selectedCategory.value) {
        try {
            const response = await axios.post(`/admin/categories/${selectedCategory.value.id}?_method=DELETE`);
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
            console.error('Error deleting category:', error);
        }
    }
};

/* TABLE */
const columns = [
    { accessorKey: 'name', header: 'Nama' },
    { accessorKey: 'slug', header: 'Slug' },
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

const fetchData = async () => {
    try {
        const response = await axios.get('/api/categories', {
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

    <Head title="Daftar Kategori" />

    <AdminLayout>
        <h1 class="text-2xl font-semibold text-gray-900">Daftar Kategori</h1>
        <div class="flex flex-col md:flex-row justify-between">
            <Button @click="openAddModal()" class="w-full md:w-max mt-4 bg-green-700 hover:bg-green-800">Tambah
                Kategori</Button>
            <div class="flex items-center py-4 w-full md:w-72">
                <Input placeholder="Cari Kategori..." v-model="globalFilter" class="w-full max-w-full md:max-w-sm"
                    @input="debouncedFetchData" />
            </div>
        </div>

        <div>
            <!-- Table  -->
            <div class="rounded-md border">
                <Table>
                    <TableHeaderWrapper :columns="columns" :sorting="sorting" @sort="sortBy"/>

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
            <DialogWrapper style="width: 70rem;" v-model:open="isAddModalOpen" title="Tambah Kategori" desc="Tambah kategori">
                <form @submit="onSubmit" enctype="multipart/form-data" class="space-y-4">
                    <FormInput name="name" label="Nama" type="text" />

                    <DialogFooter>
                        <Button type="submit" :class="{ 'bg-slate-500': isLoading }" :disabled="isLoading">
                            {{ isLoading ? 'Mohon tunggu ...' : 'Tambah Kategori' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogWrapper>

            <!-- Edit Modal -->
            <DialogWrapper v-model:open="isEditModalOpen" title="Ubah Kategori" desc="Ubah kategori">
                <form @submit="onSubmit" enctype="multipart/form-data" class="space-y-4">
                    <FormInput name="name" label="Nama" type="text" />

                    <DialogFooter>
                        <Button type="submit" :class="{ 'bg-slate-500': isLoading }" :disabled="isLoading">
                            {{ isLoading ? 'Mohon tunggu ...' : 'Ubah Kategori' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogWrapper>

            <!-- Delete Modal -->
            <DialogWrapper v-model:open="isDeleteModalOpen" title="Hapus Kategori" desc="Hapus kategori">
                <DialogFooter>
                    <Button @click="isDeleteModalOpen = false" variant="outline">Batal</Button>
                    <Button @click="deleteCategory" variant="destructive">Hapus</Button>
                </DialogFooter>
            </DialogWrapper>
        </div>
    </AdminLayout>
</template>
