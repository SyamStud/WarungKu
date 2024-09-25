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
import FormMessage from '@/Components/ui/form/FormMessage.vue';
import Multiselect from 'vue-multiselect';
import Label from '@/components/ui/label/Label.vue';

const Toast = useToast();

/* MODAL */
const isAddModalOpen = ref(false);
const isEditModalOpen = ref(false);
const isDeleteModalOpen = ref(false);
const selectedStock = ref(null);
const isEdit = ref(false);

const openAddModal = () => {
    form.setValues({
        'product_variant_id': '',
        'quantity': '',
    });
    isEdit.value = false;
    isAddModalOpen.value = true;
    selectedProducts.value = [];
};

const openEditModal = (stock) => {
    isEdit.value = true;
    selectedStock.value = stock;
    form.resetForm();

    const oldProduct = options.value.find((option) => option.rawName === stock.product);
    selectedProducts.value = options.value.find((option) => option.rawName === oldProduct.rawName);

    form.setValues({
        product_variant_id: oldProduct.id,
        quantity: stock.quantity,
    });
    isEditModalOpen.value = true;
};

// VALIDATION FRONT END FORM
const addFormSchema = toTypedSchema(z.object({
    product_variant_id: z.number().min(1),
    quantity: z.number().min(1),
}));

const editFormSchema = toTypedSchema(z.object({
    product_variant_id: z.number().min(1),
    quantity: z.number(),
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
            response = await axios.post(`/admin/stocks/${selectedStock.value.id}?_method=PUT`, values);
        } else {
            response = await axios.post('/admin/stocks', values);
        }

        console.log(response.data);

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
        selectedProducts.value = [];
        isLoading.value = false;
    } catch (error) {
        console.error('Error submitting form:', error);
        isLoading.value = false;
    }
});

/* TABLE */
const columns = [
    { accessorKey: 'product', header: 'Nama Produk' },
    { accessorKey: 'variant', header: 'Variasi' },
    { accessorKey: 'quantity', header: 'Kuantitas' },
    { accessorKey: 'status', header: 'Status' },
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
        const response = await axios.get('/api/stocks', {
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

        fetchOptions();
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

onMounted(() => {
    fetchOptions();
    fetchData();
})

watch(() => pagination.value, () => { }, { deep: true });

const handlePageChange = (newPageIndex) => {
    pagination.value.pageIndex = newPageIndex;
    fetchData();
};


const options = ref([])
const selectedProducts = ref([])

const fetchOptions = async () => {
    try {
        const response = await axios.get('/api/productVariants')

        options.value = response.data.data.map((product) => ({
            id: product.id,
            name: product.name + ' - ' + product.variant,
            rawName: product.name,
            stock: product.stock,
            value: product.id
        }))
    } catch (error) {
        console.error('Error fetching options:', error)
    }
}

const updateIdProduct = (value) => {
    selectedProducts.value = value;
    form.setFieldValue('product_variant_id', value.id);
};
</script>

<style scope src="vue-multiselect/dist/vue-multiselect.css"></style>

<template>

    <Head title="Daftar Stok" />

    <AdminLayout>
        <h1 class="text-2xl font-semibold text-gray-900">Daftar Stok</h1>
        <div class="flex flex-col md:flex-row justify-between">
            <div class="flex gap-2">
                <Button @click="openAddModal()" class="w-full md:w-max mt-4 bg-green-700 hover:bg-green-800">Tambah
                    Stok</Button>
            </div>
            <div class="flex items-center py-4 w-full md:w-72">
                <Input placeholder="Cari Stok..." v-model="globalFilter" class="w-full max-w-full md:max-w-sm"
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
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <!-- Pagination -->
            <PaginationWrapper :pagination="pagination" :onPageChange="handlePageChange" />

            <!-- Add Modal -->
            <DialogWrapper v-model:open="isAddModalOpen" title="Tambah Stok"
                desc="Stok saat ini akan ditambahkan dengan stok tambahan">
                <form @submit="onSubmit" enctype="multipart/form-data" class="space-y-4">
                    <div class="w-full">
                        <FormField v-slot="{ field }" name="product_variant_id">
                            <FormItem>
                                <FormLabel>Nama Produk</FormLabel>
                                <Multiselect v-model="selectedProducts" :options="options"
                                    @update:modelValue="updateIdProduct" :close-on-select="false"
                                    :preserve-search="true" placeholder="Pilih kategori" label="name" track-by="name"
                                    :preselect-first="false" />
                                <FormMessage />
                            </FormItem>
                        </FormField>
                    </div>

                    <div>
                        <Label>Stok Saat Ini</Label>
                        <Input v-bind:modelValue="selectedProducts.quantity" disabled class="mt-2" type="text"
                            :value="selectedProducts.stock" />
                    </div>
                    <FormInput name="quantity" label="Stok Tambahan" type="number"
                        placeholder="Masukkan stok tambahan" />

                    <DialogFooter>
                        <Button type="submit" :class="{ 'bg-slate-500': isLoading }" :disabled="isLoading">
                            {{ isLoading ? 'Mohon tunggu ...' : 'Tambah Stok' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogWrapper>

            <!-- Edit Modal -->
            <DialogWrapper v-model:open="isEditModalOpen" title="Ubah Stok" desc="Ubah stok">
                <form @submit="onSubmit" enctype="multipart/form-data" class="space-y-4">
                    <div>
                        <Label>Nama Produk</Label>
                        <Input v-bind:modelValue="selectedProducts.name" disabled class="mt-2 font-semibold" type="text"
                            :value="selectedProducts.name" />
                    </div>

                    <FormInput name="quantity" label="Kuantitas" type="number" />
                    <FormInput name="product_variant_id" label="" type="hidden" placeholder="Masukkan stok tambahan" />

                    <DialogFooter>
                        <Button type="submit" :class="{ 'bg-slate-500': isLoading }" :disabled="isLoading">
                            {{ isLoading ? 'Mohon tunggu ...' : 'Ubah Stok' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogWrapper>
        </div>
    </AdminLayout>
</template>
