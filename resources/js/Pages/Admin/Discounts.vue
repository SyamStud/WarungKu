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
import FormLa from '@/Components/ui/form/FormLabel.vue';
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
import Label from '@/components/ui/label/Label.vue';
import Multiselect from 'vue-multiselect';
import { useFormatRupiah } from '@/Composables/useFormatRupiah';
import FormLabel from '@/Components/ui/form/FormLabel.vue';

const Toast = useToast();
const { formatRupiah } = useFormatRupiah();

/* MODAL */
const isAddModalOpen = ref(false);
const isEditModalOpen = ref(false);
const isDeleteModalOpen = ref(false);
const selectedDiscount = ref(null);
const isEdit = ref(false);

const openAddModal = () => {
    form.setValues({
        name: '',
        description: '',
        type: 'product',
        amount: 0,
        amount_type: 'percentage',
        threshold: 0,
        start_date: '',
        end_date: '',
        is_active: '1',
        product_id: '',
    });
    isEdit.value = false;
    isAddModalOpen.value = true;
};

const openEditModal = (discount) => {
    console.log(discount);
    isEdit.value = true;
    selectedDiscount.value = discount;
    form.resetForm();
    form.setValues({
        name: discount.name,
        description: discount.description,
        type: discount.type,
        amount: discount.amount,
        amount_type: discount.amount_type,
        threshold: discount.threshold,
        start_date: discount.start_date,
        end_date: discount.end_date,
        is_active: discount.is_active,
    });
    isEditModalOpen.value = true;
};

const openDeleteModal = (discount) => {
    selectedDiscount.value = discount;
    isDeleteModalOpen.value = true;
};


// VALIDATION FRONT END FORM
const addFormSchema = toTypedSchema(z.object({
    name: z.string().max(50),
    description: z.string().min(2).max(255),
    type: z.string().min(2).max(50),
    amount: z.number(),
    amount_type: z.string().min(2).max(50),
    threshold: z.number(),
    start_date: z.string().min(2).max(50),
    end_date: z.string().min(2).max(50),
    is_active: z.string(),
    product_id: z.any(),
}));

const editFormSchema = toTypedSchema(z.object({
    name: z.string().min(2).max(50),
    description: z.string().min(2).max(255),
    type: z.string().min(2).max(50),
    amount: z.number().min(1),
    amount_type: z.string().min(2).max(50),
    threshold: z.number().min(1),
    start_date: z.string().min(2).max(50),
    end_date: z.string().min(2).max(50),
    is_active: z.string(),
}));

const form = useForm({
    validationSchema: computed(() => isEdit.value ? editFormSchema : addFormSchema),
});

let isLoading = ref(false);

// ACTION FORM 
const onSubmit = async () => {
    try {
        isLoading.value = true;
        let response;
        if (isEdit.value) {
            response = await axios.post(`/admin/discounts/${selectedDiscount.value.id}?_method=PUT`, form.values);
        } else {
            response = await axios.post('/admin/discounts', form.values);
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
};

const deleteDiscount = async () => {
    if (selectedDiscount.value) {
        try {
            const response = await axios.post(`/admin/discounts/${selectedDiscount.value.id}?_method=DELETE`);
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
            console.error('Error deleting discount:', error);
        }
    }
};

/* TABLE */
const columns = [
    { accessorKey: 'name', header: 'Nama Diskon' },
    { accessorKey: 'type', header: 'Tipe' },
    { accessorKey: 'amount', header: 'Jumlah' },
    { accessorKey: 'threshold', header: 'Min Jumlah / Belanja' },
    { accessorKey: 'start_date', header: 'Tanggal Mulai' },
    { accessorKey: 'end_date', header: 'Tanggal Selesai' },
    // { accessorKey: 'description', header: 'Deskripsi' },
    { accessorKey: 'is_active', header: 'Aktif' },
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
        const response = await axios.get('/api/discounts', {
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

const productOptions = ref([]);
const selectedProducts = ref([]);

const fetchProducts = async () => {
    try {
        const response = await axios.get('/api/productVariants');

        console.log(response.data.data);

        productOptions.value = response.data.data.map((product) => ({
            id: product.id,
            name: product.name + ' - ' + product.variant,
            value: product.id
        }))
    } catch (error) {
        console.error('Error fetching products:', error);
    }
};

const updateIdProduct = (value) => {
    selectedProducts.value = value;
    form.setFieldValue('product_id', value.id);
};

onMounted(() => {
    fetchData();
    fetchProducts();
});

watch(() => pagination.value, () => { }, { deep: true });

const handlePageChange = (newPageIndex) => {
    pagination.value.pageIndex = newPageIndex;
    fetchData();
};
</script>


<style scope src="vue-multiselect/dist/vue-multiselect.css"></style>


<template>

    <Head title="Daftar Diskon" />

    <AdminLayout>
        <h1 class="text-2xl font-semibold text-gray-900">Daftar Diskon</h1>
        <div class="flex flex-col md:flex-row justify-between">
            <Button @click="openAddModal()" class="w-full md:w-max mt-4 bg-green-700 hover:bg-green-800">Tambah
                Diskon</Button>
            <div class="flex items-center py-4 w-full md:w-72">
                <Input placeholder="Cari Diskon..." v-model="globalFilter" class="w-full max-w-full md:max-w-sm"
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
                                <template v-if="cell.column.id === 'type'">
                                    {{ cell.getValue() === 'product' ? 'Potongan Harga Produk' :
                                            cell.getValue() === 'order' ? 'Potongan Harga Belanja' : '-' }}
                                </template>

                                <template v-else-if="cell.column.id === 'amount'">
                                    <template v-if="row.original.amount_type === 'percentage'">
                                        {{ cell.getValue() }}%
                                    </template>
                                    <template v-else-if="row.original.amount_type === 'fixed'">
                                        {{ formatRupiah(cell.getValue()) }}
                                    </template>
                                    <template v-else>
                                        {{ '-' }}
                                    </template>
                                </template>

                                <template v-else>
                                    {{ cell.getValue() ? cell.getValue() : '-' }}
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

            <!-- Add Modal -->
            <DialogWrapper v-model:open="isAddModalOpen" title="Tambah Diskon" customClass="md:w-[50rem]"
                desc="Tambah diskon">
                <form @submit.prevent="onSubmit" enctype="multipart/form-data" class="space-y-4">
                    <div class="flex gap-4 item-center">
                        <FormInput name="name" label="Nama Diskon" type="text" />
                        <div class="w-full">
                            <FormField v-slot="{ componentField }" name="type">
                                <FormItem>
                                    <FormLabel>Tipe Diskon</FormLabel>
                                    <Select v-bind="componentField">
                                        <FormControl>
                                            <SelectTrigger>
                                                <SelectValue placeholder="Pilih Tipe" />
                                            </SelectTrigger>
                                        </FormControl>
                                        <SelectContent>
                                            <SelectGroup>
                                                <SelectItem value="product">
                                                    Potongan Harga Produk
                                                </SelectItem>
                                                <SelectItem value="order">
                                                    Potongan Harga Belanja
                                                </SelectItem>
                                            </SelectGroup>
                                        </SelectContent>
                                    </Select>
                                    <FormMessage />
                                </FormItem>
                            </FormField>
                        </div>
                    </div>
                    <div class="flex gap-4 item-center">
                        <div v-if="form.values.type == 'product'" class="w-full">
                            <FormField v-slot="{ field }" name="category_id">
                                <FormItem>
                                    <FormLabel>Pilih Produk</FormLabel>
                                    <Multiselect class="h-10" v-model="selectedProducts" :options="productOptions"
                                        @update:modelValue="updateIdProduct" :close-on-select="false"
                                        :preserve-search="true" placeholder="Pilih produk" label="name" track-by="name"
                                        :preselect-first="false" />
                                    <FormMessage />
                                </FormItem>
                            </FormField>
                        </div>

                        <div v-if="form.values.type == 'order'" class="w-full">
                            <FormInput v-if="form.values.type == 'order'" name="threshold" label="Minimum Belanja"
                                type="number" />
                        </div>

                        <div class="flex gap-1 item-center w-full">
                            <FormInput name="amount" label="Jumlah Diskon" type="number" />
                            <div class="w-full mt-8">
                                <FormField v-slot="{ componentField }" name="amount_type">
                                    <FormItem>
                                        <Select v-bind="componentField">
                                            <FormControl>
                                                <SelectTrigger>
                                                    <SelectValue placeholder="Pilih Tipe" />
                                                </SelectTrigger>
                                            </FormControl>
                                            <SelectContent>
                                                <SelectGroup>
                                                    <SelectItem value="percentage">
                                                        Persen
                                                    </SelectItem>
                                                    <SelectItem value="fixed">
                                                        Rupiah
                                                    </SelectItem>
                                                </SelectGroup>
                                            </SelectContent>
                                        </Select>
                                        <FormMessage />
                                    </FormItem>
                                </FormField>
                            </div>
                        </div>
                    </div>

                    <FormInput v-if="form.values.type == 'product'" name="threshold" label="Minimum Jumlah Produk"
                            type="number" />

                    <div class="flex gap-4 item-center">
                        <FormInput name="start_date" label="Tanggal Mulai" type="date" />
                        <FormInput name="end_date" label="Tanggal Selesai" type="date" />
                    </div>

                    <DialogFooter>
                        <Button type="submit" :class="{ 'bg-slate-500': isLoading }" :disabled="isLoading">
                            {{ isLoading ? 'Mohon tunggu ...' : 'Tambah Diskon' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogWrapper>

            <!-- Edit Modal -->
            <DialogWrapper v-model:open="isEditModalOpen" title="Ubah Diskon" desc="Ubah diskon">
                <form @submit.prevent="onSubmit" enctype="multipart/form-data" class="space-y-4">
                    <div class="flex gap-4 item-center">
                        <FormInput name="name" label="Nama Diskon" type="text" />
                    </div>
                    <div class="flex gap-4 item-center">
                        <div v-if="form.values.type == 'order'" class="w-full">
                            <FormInput v-if="form.values.type == 'order'" name="threshold" label="Minimum Belanja"
                                type="number" />
                        </div>

                        <div class="flex gap-1 item-center w-full">
                            <FormInput name="amount" label="Jumlah Diskon" type="number" />
                            <div class="w-full mt-8">
                                <FormField v-slot="{ componentField }" name="amount_type">
                                    <FormItem>
                                        <Select v-bind="componentField">
                                            <FormControl>
                                                <SelectTrigger>
                                                    <SelectValue placeholder="Pilih Tipe" />
                                                </SelectTrigger>
                                            </FormControl>
                                            <SelectContent>
                                                <SelectGroup>
                                                    <SelectItem value="percentage">
                                                        Persen
                                                    </SelectItem>
                                                    <SelectItem value="fixed">
                                                        Rupiah
                                                    </SelectItem>
                                                </SelectGroup>
                                            </SelectContent>
                                        </Select>
                                        <FormMessage />
                                    </FormItem>
                                </FormField>
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-4 item-center">
                        <FormInput name="start_date" label="Tanggal Mulai" type="date" />
                        <FormInput name="end_date" label="Tanggal Selesai" type="date" />
                    </div>
                    <div class="w-full">
                        <FormField v-slot="{ componentField }" name="is_active">
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
                                            <SelectItem value="1">
                                                Aktif
                                            </SelectItem>
                                            <SelectItem value="0">
                                                Tidak Aktif
                                            </SelectItem>
                                        </SelectGroup>
                                    </SelectContent>
                                </Select>
                                <FormMessage />
                            </FormItem>
                        </FormField>
                    </div>

                    <DialogFooter>
                        <Button type="submit" :class="{ 'bg-slate-500': isLoading }" :disabled="isLoading">
                            {{ isLoading ? 'Mohon tunggu ...' : 'Ubah Diskon' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogWrapper>

            <!-- Delete Modal -->
            <DialogWrapper v-model:open="isDeleteModalOpen" title="Hapus Diskon" desc="Hapus diskon">
                <DialogFooter>
                    <Button @click="isDeleteModalOpen = false" variant="outline">Batal</Button>
                    <Button @click="deleteDiscount" variant="destructive">Hapus</Button>
                </DialogFooter>
            </DialogWrapper>
        </div>
    </AdminLayout>
</template>
