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
import Select from '@/Components/ui/select/Select.vue';
import SelectTrigger from '@/Components/ui/select/SelectTrigger.vue';
import SelectValue from '@/Components/ui/select/SelectValue.vue';
import SelectContent from '@/Components/ui/select/SelectContent.vue';
import SelectGroup from '@/Components/ui/select/SelectGroup.vue';
import SelectItem from '@/Components/ui/select/SelectItem.vue';
import FormMessage from '@/Components/ui/form/FormMessage.vue';
import Multiselect from 'vue-multiselect';
import Label from '@/components/ui/label/Label.vue';
import Button from '@/components/ui/button/Button.vue';

const Toast = useToast();

/* MODAL */
// State untuk kontrol modal
const isAddModalOpen = ref(false);
const isEditModalOpen = ref(false);
const isDeleteModalOpen = ref(false);
const isPhotoModalOpen = ref(false);
const selectedProduct = ref(null); // Menyimpan produk yang dipilih
const isEdit = ref(false); // Menyimpan status apakah dalam mode edit
const sku = ref(''); // Menyimpan SKU produk
const units = ref([]); // Menyimpan data unit

// Fungsi untuk membuka modal edit
const openEditModal = (product) => {
    isEdit.value = true; // Set status edit
    selectedProduct.value = product; // Set produk yang dipilih
    form.resetForm(); // Reset form
    sku.value = product.sku; // Set SKU produk

    // Cari kategori lama dari produk yang dipilih
    const oldCategory = options.value.find((option) => option.name === product.category);
    selectedCategories.value = options.value.find((option) => option.name === oldCategory.name);

    // Set nilai form berdasarkan produk yang dipilih
    form.setValues({
        sku: product.sku,
        name: product.name,
        category_id: oldCategory.id,
        price: product.price,
        cost: product.cost,
        status: product.status,
        quantity: product.quantity,
        unit_id: product.unit_id.toString(),
    });
    isEditModalOpen.value = true; // Buka modal edit
};

// Fungsi untuk membuka modal hapus
const openDeleteModal = (product) => {
    selectedProduct.value = product; // Set produk yang dipilih
    isDeleteModalOpen.value = true; // Buka modal hapus
};

// Schema validasi untuk form
const formSchema = toTypedSchema(z.object({
    sku: z.string().min(2),
    name: z.string().min(2),
    category_id: z.number().min(1),
    price: z.number(),
    cost: z.number(),
    status: z.string().min(2),
    quantity: z.string(),
    unit_id: z.string(),
}));

// Inisialisasi form dengan schema validasi
const form = useForm({
    validationSchema: computed(() => formSchema),
});

let isLoading = ref(false); // State loading untuk form

// ACTION FORM 
// Fungsi untuk meng-handle submit form
const onSubmit = form.handleSubmit(async (values) => {
    try {
        isLoading.value = true; // Set loading true

        values.quantity = values.quantity.replace(/\s/g, ''); // Menghapus spasi dari quantity

        // Kirim request untuk menyimpan data produk
        let response = await axios.post(`/admin/products/${selectedProduct.value.id}?_method=PUT`, values);

        // Cek status response
        if (response.data.status === 'error') {
            isLoading.value = false; // Set loading false
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

        // Tutup modal sesuai dengan status edit
        isEdit.value ? (isEditModalOpen.value = false) : (isAddModalOpen.value = false);
        fetchData(); // Ambil data terbaru
        isLoading.value = false; // Set loading false
    } catch (error) {
        console.error('Error submitting form:', error);
        isLoading.value = false; // Set loading false
    }
});

// Fungsi untuk menghapus produk
const deleteProduct = async () => {
    if (selectedProduct.value) {
        try {
            const response = await axios.post(`/admin/products/${selectedProduct.value.id}?_method=DELETE`);
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

            // Tutup modal setelah berhasil hapus
            isAddModalOpen.value = false;
            isDeleteModalOpen.value = false;
            fetchData(); // Ambil data terbaru
        } catch (error) {
            console.error('Error deleting product:', error);
        }
    }
};

/* TABLE */
// Kolom untuk tabel produk
const columns = [
    { accessorKey: 'sku', header: 'SKU' },
    { accessorKey: 'name', header: 'Nama' },
    { accessorKey: 'variant', header: 'Variasi' },
    { accessorKey: 'category', header: 'Kategori' },
    { accessorKey: 'price', header: 'Harga Jual' },
    { accessorKey: 'cost', header: 'Harga Modal' },
    { accessorKey: 'status', header: 'Status' },
];

const data = ref([]); // Data produk
const globalFilter = ref(''); // Filter global untuk pencarian
const pagination = ref({
    pageIndex: 0,
    pageSize: 10,
    pageCount: 1,
    total: 0,
});

const sorting = ref({ field: 'id', direction: 'asc' }); // State untuk sorting

// Inisialisasi table menggunakan useVueTable
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
    manualPagination: true, // Mengatur pagination manual
    pageCount: computed(() => pagination.value.pageCount),
    onPaginationChange: (updater) => {
        if (typeof updater === 'function') {
            const newPagination = updater(pagination.value);
            pagination.value = { ...pagination.value, ...newPagination };
        } else {
            pagination.value = { ...pagination.value, ...updater };
        }
        fetchData(); // Ambil data berdasarkan perubahan pagination
    }
});

// Fungsi untuk mengambil data produk
const fetchData = async () => {
    try {
        const response = await axios.get('/api/productVariants', {
            params: {
                search: globalFilter.value,
                page: pagination.value.pageIndex + 1,
                per_page: pagination.value.pageSize,
                sort: sorting.value.field,
                direction: sorting.value.direction,
            }
        });

        // Update data dan pagination berdasarkan response
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

// Debounce untuk menghindari panggilan fetchData yang terlalu sering
const debouncedFetchData = debounce(fetchData, 300);

// Fungsi untuk sorting
const sortBy = (field) => {
    if (sorting.value.field === field) {
        // Toggle arah sorting jika kolom yang sama
        sorting.value.direction = sorting.value.direction === 'asc' ? 'desc' : 'asc';
    } else {
        sorting.value.field = field; // Set kolom untuk sorting
        sorting.value.direction = 'asc'; // Set arah sorting ke asc
    }
    fetchData(); // Ambil data terbaru
};

// Lifecycle hook untuk mengambil data saat komponen dimuat
onMounted(() => {
    fetchOptions(); // Ambil opsi kategori dan unit
    fetchData(); // Ambil data produk
})

// Watcher untuk pagination
watch(() => pagination.value, () => { }, { deep: true });

// Fungsi untuk mengubah halaman
const handlePageChange = (newPageIndex) => {
    pagination.value.pageIndex = newPageIndex; // Update index halaman
    fetchData(); // Ambil data terbaru
};

// State untuk opsi kategori dan unit
const options = ref([]);
const selectedCategories = ref([]);

// Fungsi untuk mengambil opsi kategori dan unit
const fetchOptions = async () => {
    try {
        const response = await axios.get('/api/categories');
        // Map data kategori ke format yang diinginkan
        options.value = response.data.data.map((category) => ({
            id: category.id,
            name: category.name,
            value: category.id
        }));

        const unitResponse = await axios.get('/api/units');
        // Map data unit ke format yang diinginkan
        units.value = unitResponse.data.data.map((unit) => ({
            id: unit.id,
            name: unit.name,
            value: unit.id
        }));
    } catch (error) {
        console.error('Error fetching options:', error);
    }
};

// Fungsi untuk memperbarui ID kategori
const updateIdCategory = (value) => {
    selectedCategories.value = value; // Simpan kategori yang dipilih
    form.setFieldValue('category_id', value.id); // Set nilai kategori di form
};

// Fungsi untuk menghasilkan SKU unik
const generateSKU = async () => {
    let uniqueSKU = false; // Status SKU unik
    let newSKU = ''; // Variabel untuk SKU baru

    // Loop sampai menemukan SKU yang unik
    while (!uniqueSKU) {
        newSKU = 'CSTM' + Math.floor(Math.random() * 1000000000).toString(); // Generate SKU baru

        const response = await axios.post(`/api/products/check-sku`, { sku: newSKU }); // Cek apakah SKU unik
        uniqueSKU = response.data.unique; // Update status SKU unik
    }

    sku.value = newSKU; // Set SKU baru
    form.setFieldValue('sku', newSKU); // Set nilai SKU di form
};
</script>

<style scope src="vue-multiselect/dist/vue-multiselect.css"></style>

<template>
    <!-- Mengatur judul halaman -->

    <Head title="Daftar Produk" />

    <AdminLayout>
        <!-- Judul Halaman -->
        <h1 class="text-2xl font-semibold text-gray-900">Daftar Produk</h1>
        <!-- Button Tambah dan Input Pencarian -->
        <div class="flex flex-col md:flex-row justify-between">
            <div class="flex gap-2">
                <Link :href="route('products.create')">
                <Button class="w-full md:w-max mt-4 bg-green-700 hover:bg-green-800">Tambah Produk</Button>
                </Link>

                <Link :href="route('products.add.variant')">
                <Button class="w-full md:w-max mt-4">Tambah Variasi Produk</Button>
                </Link>
            </div>
            <div class="flex items-center py-4 w-full md:w-72">
                <Input placeholder="Cari Produk..." v-model="globalFilter" class="w-full max-w-full md:max-w-sm"
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
            <DialogWrapper v-model:open="isAddModalOpen" title="Tambah Produk" desc="Tambah produk">
                <form @submit="onSubmit" enctype="multipart/form-data" class="space-y-4">
                    <div class="flex flex-col md:flex-row gap-4">
                        <!-- <FormInput name="sku" label="SKU" type="text" /> -->
                        <div class="w-full">
                            <Label>SKU</Label>
                            <Input v-model="sku" type="text" name="sku" class="w-full mt-2"></Input>
                        </div>
                        <Button @click="generateSKU" type="button" class="mt-8">Generate SKU</Button>
                    </div>
                    <div class="flex flex-col md:flex-row gap-4 mt-2">
                        <FormInput name="name" label="Nama Produk" type="text" />
                        <div class="w-full">
                            <FormField v-slot="{ field }" name="category_id">
                                <FormItem>
                                    <FormLabel>Kategori Produk</FormLabel>
                                    <Multiselect v-model="selectedCategories" :options="options"
                                        @update:modelValue="updateIdCategory" :close-on-select="false"
                                        :preserve-search="true" placeholder="Pilih kategori" label="name"
                                        track-by="name" :preselect-first="false" />
                                    <FormMessage />
                                </FormItem>
                            </FormField>
                        </div>
                    </div>

                    <div class="flex flex-col md:flex-row gap-4">
                        <FormInput name="price" label="Harga Jual" type="number" />
                        <FormInput name="cost" label="Harga Modal" type="number" />
                    </div>
                    <div class="flex flex-col md:flex-row gap-4">
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
                        <FormInput name="stock" label="Stok" type="number" />
                    </div>
                    <FormField v-slot="{ componentField }" name="description">
                        <FormItem>
                            <FormLabel>Deskripsi</FormLabel>
                            <FormControl>
                                <Textarea placeholder="Masukkan deskripsi saat ini." class="resize-none"
                                    v-bind="componentField" />
                            </FormControl>
                            <FormMessage />
                        </FormItem>
                    </FormField>
                    <DialogFooter>
                        <Button type="submit" :class="{ 'bg-slate-500': isLoading }" :disabled="isLoading">
                            {{ isLoading ? 'Mohon tunggu ...' : 'Tambah Produk' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogWrapper>

            <!-- Edit Modal -->
            <DialogWrapper v-model:open="isEditModalOpen" title="Ubah Produk" desc="Ubah produk">
                <form @submit="onSubmit" enctype="multipart/form-data" class="space-y-4">
                    <div class="flex flex-col md:flex-row gap-4">
                        <!-- <FormInput name="sku" label="SKU" type="text" /> -->
                        <div class="w-full">
                            <Label>SKU</Label>
                            <Input v-model="sku" type="text" name="sku" class="w-full mt-2"></Input>
                        </div>
                        <Button @click="generateSKU" type="button" class="mt-8">Generate SKU</Button>
                    </div>
                    <div class="flex flex-col md:flex-row gap-4 mt-2">
                        <FormInput name="name" label="Nama Produk" type="text" />
                        <div class="w-full">
                            <FormField v-slot="{ field }" name="category_id">
                                <FormItem>
                                    <FormLabel>Kategori Produk</FormLabel>
                                    <Multiselect v-model="selectedCategories" :options="options"
                                        @update:modelValue="updateIdCategory" :close-on-select="false"
                                        :preserve-search="true" placeholder="Pilih kategori" label="name"
                                        track-by="name" :preselect-first="false" />
                                    <FormMessage />
                                </FormItem>
                            </FormField>
                        </div>

                    </div>
                    <div class="flex gap-4">
                        <div class="w-full">
                            <Label for="">Satuan Unit</Label>
                            <div class="flex gap-1">
                                <div class="w-14">
                                    <FormInput name="quantity" label="" type="text" />
                                </div>

                                <div class="min-w-max w-full mt-2">
                                    <FormField v-slot="{ componentField }" name="unit_id">
                                        <FormItem>
                                            <Select v-bind="componentField">
                                                <FormControl>
                                                    <SelectTrigger>
                                                        <SelectValue placeholder="Pilih Tipe" />
                                                    </SelectTrigger>
                                                </FormControl>
                                                <SelectContent>
                                                    <SelectGroup>
                                                        <SelectItem v-for="unit in units" :key="unit.id.toString()"
                                                            :value="unit.id.toString()">
                                                            {{ unit.name }}
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

                    <div class="flex flex-col md:flex-row gap-4">
                        <FormInput name="price" label="Harga Jual" type="number" />
                        <FormInput name="cost" label="Harga Modal" type="number" />
                    </div>
                    <div class="flex flex-col md:flex-row gap-4">

                    </div>
                    <DialogFooter>
                        <Button type="submit" :class="{ 'bg-slate-500': isLoading }" :disabled="isLoading">
                            {{ isLoading ? 'Mohon tunggu ...' : 'Ubah Produk' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogWrapper>

            <!-- Delete Modal -->
            <DialogWrapper v-model:open="isDeleteModalOpen" title="Hapus Produk" desc="Hapus produk">
                <DialogFooter>
                    <Button @click="isDeleteModalOpen = false" variant="outline">Batal</Button>
                    <Button @click="deleteProduct" variant="destructive">Hapus</Button>
                </DialogFooter>
            </DialogWrapper>

            <!-- Photo Modal -->
            <DialogWrapper v-model:open="isPhotoModalOpen" title="Photo" desc="">
                <div class="flex gap-2">
                    <img :src="`http://127.0.0.1:8000/${selectedProduct.photo}`"
                        class="h-72 p-1 w-full rounded-xl border-dashed border-2">
                </div>
                <DialogFooter>
                    <Button @click="isPhotoModalOpen = false" variant="outline">Tutup</Button>
                </DialogFooter>
            </DialogWrapper>
        </div>
    </AdminLayout>
</template>
