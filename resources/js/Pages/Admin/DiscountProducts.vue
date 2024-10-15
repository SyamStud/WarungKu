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
import { Table, TableBody, TableCell, TableRow } from '@/Components/ui/table';
import PaginationWrapper from '@/Components/ui/pagination/PaginationWrapper.vue';
import { FormField } from '@/Components/ui/form';
import FormItem from '@/Components/ui/form/FormItem.vue';
import FormLabel from '@/Components/ui/form/FormLabel.vue';
import FormControl from '@/Components/ui/form/FormControl.vue';
import Select from '@/Components/ui/select/Select.vue';
import SelectTrigger from '@/Components/ui/select/SelectTrigger.vue';
import SelectValue from '@/Components/ui/select/SelectValue.vue';
import SelectContent from '@/Components/ui/select/SelectContent.vue';
import SelectGroup from '@/Components/ui/select/SelectGroup.vue';
import SelectItem from '@/Components/ui/select/SelectItem.vue';
import FormMessage from '@/Components/ui/form/FormMessage.vue';
import Label from '@/components/ui/label/Label.vue';
import Multiselect from 'vue-multiselect';
import { useFormatRupiah } from '@/Composables/useFormatRupiah';
import Button from '@/components/ui/button/Button.vue';

// Import necessary modules and libraries
const Toast = useToast();
const { formatRupiah } = useFormatRupiah();

/* MODAL STATE MANAGEMENT */
const isAddModalOpen = ref(false);  // State for Add Modal visibility
const isEditModalOpen = ref(false);  // State for Edit Modal visibility
const isDeleteModalOpen = ref(false); // State for Delete Modal visibility
const selectedDiscountProduct = ref(null); // Currently selected discount product
const isEdit = ref(false); // Flag to check if it's in edit mode

// Open Add Modal and reset the form
const openAddModal = () => {
    form.setValues({
        discount_id: null,
        product_variant_id: null,
        is_active: null,
    });
    isEdit.value = false; // Set to add mode
    isAddModalOpen.value = true; // Show Add Modal
};

// Open Edit Modal and populate the form with selected product data
const openEditModal = (discountProduct) => {
    isEdit.value = true; // Set to edit mode
    selectedDiscountProduct.value = discountProduct; // Set the selected discount product
    form.resetForm(); // Reset the form to clear previous values
    form.setValues({
        discount_id: discountProduct.discount_id,
        product_variant_id: discountProduct.product_variant_id,
        is_active: discountProduct.is_active.toString(),
    });
    isEditModalOpen.value = true; // Show Edit Modal
};

// Open Delete Modal for selected discount product
const openDeleteModal = (discountProduct) => {
    selectedDiscountProduct.value = discountProduct; // Set the selected discount product
    isDeleteModalOpen.value = true; // Show Delete Modal
};

/* VALIDATION SCHEMA */
const addFormSchema = toTypedSchema(z.object({
    discount_id: z.any(), // Validation for discount_id
    product_variant_id: z.any(), // Validation for product_variant_id
    is_active: z.any(), // Validation for is_active
}));

const editFormSchema = toTypedSchema(z.object({
    discount_id: z.any(), // Validation for discount_id
    product_variant_id: z.any(), // Validation for product_variant_id
    is_active: z.any(), // Validation for is_active
}));

// Form initialization with dynamic validation schema based on edit state
const form = useForm({
    validationSchema: computed(() => isEdit.value ? editFormSchema : addFormSchema),
});

// Loading state for form submission
let isLoading = ref(false);

/* ACTION FORM - SUBMISSION */
const onSubmit = async () => {
    try {
        form.setFieldValue('discount_id', selectedDiscountProduct.value.id); // Set discount ID

        isLoading.value = true; // Set loading state
        let response;

        // Determine whether to update or create based on edit state
        if (isEdit.value) {
            response = await axios.post(`/admin/discount-products/${selectedDiscountProduct.value.id}?_method=PUT`, form.values);
        } else {
            response = await axios.post('/admin/discount-products', form.values);
        }

        // Handle response status
        if (response.data.status === 'error') {
            isLoading.value = false; // Reset loading state
            return Toast.fire({
                icon: "error",
                title: response.data.message, // Show error message
            });
        } else {
            Toast.fire({
                icon: "success",
                title: response.data.message, // Show success message
            });
        }

        // Close the appropriate modal based on edit state
        isEdit.value ? (isEditModalOpen.value = false) : (isAddModalOpen.value = false);
        fetchData(); // Refresh data
        isLoading.value = false; // Reset loading state

        // Reset form and related states
        identifier.value = '';
        selectedProducts.value = [];
        searchingDiscount.value = [];
        selectedDiscountProduct.value = null;
        form.resetForm();
    } catch (error) {
        console.error('Error submitting form:', error);
        isLoading.value = false; // Reset loading state on error
    }
};

/* ACTION FORM - DELETE DISCOUNT PRODUCT */
const deleteDiscountProduct = async () => {
    if (selectedDiscountProduct.value) {
        try {
            const response = await axios.post(`/admin/discount-products/${selectedDiscountProduct.value.id}?_method=DELETE`);
            // Handle response status
            if (response.data.status === 'error') {
                return Toast.fire({
                    icon: "error",
                    title: response.data.message, // Show error message
                });
            } else {
                Toast.fire({
                    icon: "success",
                    title: response.data.message, // Show success message
                });
            }

            // Close Delete Modal and refresh data
            isAddModalOpen.value = false;
            isDeleteModalOpen.value = false;
            fetchData();
        } catch (error) {
            console.error('Error deleting discountProduct:', error);
        }
    }
};

/* TABLE SETUP */
const columns = [
    { accessorKey: 'discount', header: 'Nama Diskon' },
    { accessorKey: 'product', header: 'Nama Produk' },
    { accessorKey: 'product_variant', header: 'Variasi' },
    { accessorKey: 'discount_type', header: 'Tipe Diskon' },
    { accessorKey: 'discount_amount', header: 'Jumlah Diskon' },
    { accessorKey: 'discount_threshold', header: 'Min Jumlah / Belanja' },
    { accessorKey: 'discount_start_date', header: 'Tanggal Mulai' },
    { accessorKey: 'discount_end_date', header: 'Tanggal Selesai' },
    { accessorKey: 'is_active', header: 'Status' },
];

// Data management for the table
const data = ref([]);
const globalFilter = ref('');
const pagination = ref({
    pageIndex: 0,
    pageSize: 10,
    pageCount: 1,
    total: 0,
});

// Sorting state
const sorting = ref({ field: 'id', direction: 'asc' });

// Table setup using Vue Table
const table = useVueTable({
    get data() { return data.value; }, // Data for the table
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
        fetchData(); // Fetch data on pagination change
    }
});

// Fetch data for the table from the API
const fetchData = async () => {
    try {
        const response = await axios.get('/api/discount-products', {
            params: {
                search: globalFilter.value,
                page: pagination.value.pageIndex + 1,
                per_page: pagination.value.pageSize,
                sort: sorting.value.field,
                direction: sorting.value.direction,
            }
        });

        // Update table data and pagination state
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

// Debounced fetch for searching to reduce API calls
const debouncedFetchData = debounce(fetchData, 300);

// Sorting function for table columns
const sortBy = (field) => {
    if (sorting.value.field === field) {
        sorting.value.direction = sorting.value.direction === 'asc' ? 'desc' : 'asc'; // Toggle sorting direction
    } else {
        sorting.value.field = field; // Set new sorting field
        sorting.value.direction = 'asc'; // Reset sorting direction
    }
    fetchData(); // Fetch data after sorting
};

// Product selection state
const productOptions = ref([]);
const selectedProducts = ref([]);

// Fetch product variants for the selection
const fetchProducts = async () => {
    try {
        const response = await axios.get('/api/productVariants');

        console.log(response.data.data);

        productOptions.value = response.data.data.map((product) => ({
            id: product.id,
            name: product.name + ' - ' + product.variant,
            value: product.id
        }));
    } catch (error) {
        console.error('Error fetching products:', error);
    }
};

// Update selected product in the form
const updateIdProduct = (value) => {
    selectedProducts.value = value;
    form.setFieldValue('product_variant_id', value.id);
};

// Lifecycle hook for fetching initial data
onMounted(() => {
    fetchData();
    fetchProducts();
});

// Watch for changes in pagination (currently no action taken)
watch(() => pagination.value, () => { }, { deep: true });

// Handle page change event
const handlePageChange = (newPageIndex) => {
    pagination.value.pageIndex = newPageIndex; // Update page index
    fetchData(); // Fetch data for new page
};

// Search functionality for discounts
const identifier = ref('');
const isSearchingModalOpen = ref(false);
const searchingDiscount = ref([]);

// Fetch discounts based on search input
const handleSearchDiscount = async () => {
    try {
        const response = await axios.get('/api/discounts', {
            params: {
                search: form.values.name,
            }
        });

        searchingDiscount.value = response.data.data; // Update search results
        isSearchingModalOpen.value = true; // Show search modal
    } catch (error) {
        console.error('Error fetching data:', error);
    }
};

// Handle selecting a discount from the search results
const handleSelect = (discount) => {
    selectedDiscountProduct.value = discount; // Set the selected discount product
    isSearchingModalOpen.value = false; // Close search modal
};

</script>

<style scope src="vue-multiselect/dist/vue-multiselect.css"></style>


<template>
    <!-- Mengatur judul halaman -->

    <Head title="Daftar Diskon" />

    <AdminLayout>
        <!-- Judul Halaman -->
        <h1 class="text-2xl font-semibold text-gray-900">Daftar Produk Diskon</h1>
        <!-- Button Tambah dan Input Pencarian -->
        <div class="flex flex-col md:flex-row justify-between">
            <Button @click="openAddModal()" class="w-full md:w-max mt-4 bg-green-700 hover:bg-green-800">Tambah Produk
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
                                        cell.getValue() === 'quantity' ? 'Potongan Harga Grosir' :
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

                                <template v-else-if="cell.column.id === 'is_active'">
                                    <template v-if="row.original.is_active === 1">
                                        {{ 'Aktif' }}
                                    </template>
                                    <template v-else>
                                        {{ 'Tidak Aktif' }}
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
                    <div>
                        <Label>Nama Diskon</Label>
                        <Input class="mt-2" v-model="identifier" label="Nama Diskon" @keyup.enter="handleSearchDiscount"
                            @keydown.enter.prevent />

                        <div v-if="selectedDiscountProduct"
                            class="mt-6 bg-gray-50 p-4 rounded-md border border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900 mb-3">Informasi Diskon Dipilih</h3>
                            <div class="grid grid-cols-4 gap-4 text-sm">
                                <div>
                                    <p class="text-gray-500 font-medium">Nama</p>
                                    <p class="text-gray-900">{{ selectedDiscountProduct.name || '-' }}</p>
                                </div>
                                <div>
                                    <p class="text-gray-500 font-medium">Tipe</p>
                                    <p class="text-gray-900">{{ selectedDiscountProduct.type || '-' }}</p>
                                </div>
                                <div>
                                    <p class="text-gray-500 font-medium">Jumlah</p>
                                    <p class="text-gray-900">{{ selectedDiscountProduct.amount || '-' }}</p>
                                </div>
                                <div>
                                    <p class="text-gray-500 font-medium">Min Jumlah / Beli</p>
                                    <p class="text-gray-900">{{ selectedDiscountProduct.threshold || '-' }}</p>
                                </div>
                                <div>
                                    <p class="text-gray-500 font-medium">Tanggal Mulai</p>
                                    <p class="text-gray-900">{{ selectedDiscountProduct.start_date || '-' }}</p>
                                </div>
                                <div>
                                    <p class="text-gray-500 font-medium">Tanggal Selesai</p>
                                    <p class="text-gray-900">{{ selectedDiscountProduct.end_date || '-' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <FormField v-slot="{ field }" name="product_variant_id">
                        <FormItem>
                            <FormLabel>Pilih Produk</FormLabel>
                            <Multiselect v-model="selectedProducts" :options="productOptions" class="h-10"
                                @update:modelValue="updateIdProduct" :close-on-select="false" :preserve-search="true"
                                placeholder="Pilih produk" label="name" track-by="name" :preselect-first="false" />
                            <FormMessage />
                        </FormItem>
                    </FormField>

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
                            {{ isLoading ? 'Mohon tunggu ...' : 'Tambah Diskon' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogWrapper>

            <!-- Searching Modal -->
            <DialogWrapper v-model:open="isSearchingModalOpen" title="Pilih Produk" desc=""
                custom-class="md:w-[80rem] md:z-50">
                <div class="space-y-4">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr>
                                <th class="text-left p-2 border-b">Nama Diskon</th>
                                <th class="text-left p-2 border-b">Tipe Diskon</th>
                                <th class="text-left p-2 border-b">Jumlah Diskon</th>
                                <th class="text-left p-2 border-b">Min Jumlah / Belanja</th>
                                <th class="text-left p-2 border-b">Tanggal Mulai</th>
                                <th class="text-left p-2 border-b">Tanggal Selesai</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="discount in searchingDiscount" :key="discount.id" @click="handleSelect(discount)"
                                :class="{
                                    'bg-blue-100': selectedDiscount?.id === discount.id,
                                    'hover:bg-gray-100': selectedDiscount?.id !== discount.id
                                }" class="cursor-pointer transition-colors duration-150 ease-in-out" tabindex="0"
                                @keydown.enter="handleSelect(discount)" @keydown.space.prevent="handleSelect(discount)">
                                <td class="p-2 border-b">{{ discount.name }}</td>
                                <td class="p-2 border-b">{{ discount.type == 'product' ? 'Potongan Harga Produk' :
                                    discount.type ==
                                        `quantity` ? `Potongan Harga Grosir` : discount.type == `order` ? `Potongan Harga
                                    Belanja` :
                                        "-" }}</td>
                                <td class="p-2 border-b">{{ discount.amount_type == 'percentage' ? discount.amount + '%'
                                    :
                                    formatRupiah(discount.amount) }}</td>
                                <td class="p-2 border-b">{{ discount.threshold ? discount.threshold : '-' }}</td>
                                <td class="p-2 border-b">{{ discount.start_date }}</td>
                                <td class="p-2 border-b">{{ discount.end_date }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </DialogWrapper>

            <!-- Edit Modal -->
            <DialogWrapper v-model:open="isEditModalOpen" title="Ubah Diskon" desc="Ubah diskon">
                <form @submit.prevent="onSubmit" enctype="multipart/form-data" class="space-y-4">
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
                            {{ isLoading ? 'Mohon tunggu ...' : 'Ubah Produk Diskon' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogWrapper>

            <!-- Delete Modal -->
            <DialogWrapper v-model:open="isDeleteModalOpen" title="Hapus Diskon" desc="Hapus diskon">
                <DialogFooter>
                    <Button @click="isDeleteModalOpen = false" variant="outline">Batal</Button>
                    <Button @click="deleteDiscountProduct" variant="destructive">Hapus</Button>
                </DialogFooter>
            </DialogWrapper>
        </div>
    </AdminLayout>
</template>
