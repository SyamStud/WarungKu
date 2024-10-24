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
import FormControl from '@/Components/ui/form/FormControl.vue';
import Select from '@/Components/ui/select/Select.vue';
import SelectTrigger from '@/Components/ui/select/SelectTrigger.vue';
import SelectValue from '@/Components/ui/select/SelectValue.vue';
import SelectContent from '@/Components/ui/select/SelectContent.vue';
import SelectGroup from '@/Components/ui/select/SelectGroup.vue';
import SelectItem from '@/Components/ui/select/SelectItem.vue';
import FormMessage from '@/Components/ui/form/FormMessage.vue';
import Multiselect from 'vue-multiselect';
import { useFormatRupiah } from '@/Composables/useFormatRupiah';
import FormLabel from '@/Components/ui/form/FormLabel.vue';
import Button from '@/components/ui/button/Button.vue';

// Menggunakan Toast untuk notifikasi
const Toast = useToast();
// Menggunakan fungsi untuk format rupiah
const { formatRupiah } = useFormatRupiah();

/* MODAL */
// State untuk membuka modal
const isAddModalOpen = ref(false);
const isEditModalOpen = ref(false);
const isDeleteModalOpen = ref(false);
// State untuk menyimpan diskon yang dipilih
const selectedDiscount = ref(null);
// State untuk menentukan apakah sedang dalam mode edit
const isEdit = ref(false);

// Fungsi untuk membuka modal tambah diskon
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
    isEdit.value = false; // Set mode edit ke false
    isAddModalOpen.value = true; // Buka modal tambah
};

// Fungsi untuk membuka modal edit diskon
const openEditModal = (discount) => {
    console.log(discount); // Log diskon yang dipilih
    isEdit.value = true; // Set mode edit ke true
    selectedDiscount.value = discount; // Simpan diskon yang dipilih
    form.resetForm(); // Reset form
    // Set nilai form sesuai dengan diskon yang dipilih
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
    isEditModalOpen.value = true; // Buka modal edit
};

// Fungsi untuk membuka modal hapus diskon
const openDeleteModal = (discount) => {
    selectedDiscount.value = discount; // Simpan diskon yang dipilih
    isDeleteModalOpen.value = true; // Buka modal hapus
};

// VALIDATION FRONT END FORM
// Skema validasi untuk form tambah diskon
const addFormSchema = toTypedSchema(z.object({
    name: z.string().max(50), // Nama diskon, maksimal 50 karakter
    description: z.string().min(2).max(255), // Deskripsi diskon, minimal 2 dan maksimal 255 karakter
    type: z.string().min(2).max(50), // Tipe diskon, minimal 2 dan maksimal 50 karakter
    amount: z.number(), // Jumlah diskon
    amount_type: z.string().min(2).max(50), // Tipe jumlah (persentase/nominal)
    threshold: z.number(), // Ambang batas belanja
    start_date: z.string().min(2).max(50), // Tanggal mulai
    end_date: z.string().min(2).max(50), // Tanggal selesai
    is_active: z.string(), // Status aktif
    product_id: z.any(), // ID produk
}));

// Skema validasi untuk form edit diskon
const editFormSchema = toTypedSchema(z.object({
    name: z.string().min(2).max(50), // Nama diskon, minimal 2 dan maksimal 50 karakter
    description: z.string().min(2).max(255), // Deskripsi diskon, minimal 2 dan maksimal 255 karakter
    type: z.string().min(2).max(50), // Tipe diskon, minimal 2 dan maksimal 50 karakter
    amount: z.number().min(1), // Jumlah diskon, minimal 1
    amount_type: z.string().min(2).max(50), // Tipe jumlah (persentase/nominal)
    threshold: z.number().min(1), // Ambang batas belanja, minimal 1
    start_date: z.string().min(2).max(50), // Tanggal mulai
    end_date: z.string().min(2).max(50), // Tanggal selesai
    is_active: z.string(), // Status aktif
}));

// Inisialisasi form dengan skema validasi
const form = useForm({
    validationSchema: computed(() => isEdit.value ? editFormSchema : addFormSchema),
});

// State untuk loading
let isLoading = ref(false);

// ACTION FORM 
// Fungsi untuk mengirimkan form
const onSubmit = async () => {
    try {
        isLoading.value = true; // Set loading ke true
        let response;
        // Cek apakah dalam mode edit atau tambah
        if (isEdit.value) {
            // Kirim data untuk edit diskon
            response = await axios.post(`/admin/discounts/${selectedDiscount.value.id}?_method=PUT`, form.values);
        } else {
            // Kirim data untuk tambah diskon
            response = await axios.post('/admin/discounts', form.values);
        }

        // Cek status respons
        if (response.data.status === 'error') {
            isLoading.value = false; // Set loading ke false
            return Toast.fire({
                icon: "error",
                title: response.data.message, // Tampilkan pesan error
            });
        } else {
            Toast.fire({
                icon: "success",
                title: response.data.message, // Tampilkan pesan sukses
            });
        }

        // Tutup modal sesuai dengan mode
        isEdit.value ? (isEditModalOpen.value = false) : (isAddModalOpen.value = false);
        fetchData(); // Ambil data terbaru
        isLoading.value = false; // Set loading ke false
    } catch (error) {
        console.error('Error submitting form:', error);
        isLoading.value = false; // Set loading ke false saat terjadi error
    }
};

// Fungsi untuk menghapus diskon
const deleteDiscount = async () => {
    if (selectedDiscount.value) {
        try {
            // Kirim permintaan untuk menghapus diskon
            const response = await axios.post(`/admin/discounts/${selectedDiscount.value.id}?_method=DELETE`);
            // Cek status respons
            if (response.data.status === 'error') {
                return Toast.fire({
                    icon: "error",
                    title: response.data.message, // Tampilkan pesan error
                });
            } else {
                Toast.fire({
                    icon: "success",
                    title: response.data.message, // Tampilkan pesan sukses
                });
            }

            // Tutup modal hapus dan ambil data terbaru
            isAddModalOpen.value = false;
            isDeleteModalOpen.value = false;
            fetchData();
        } catch (error) {
            console.error('Error deleting discount:', error);
        }
    }
};

/* TABLE */
// Definisi kolom untuk tabel
const columns = [
    { accessorKey: 'name', header: 'Nama Diskon' }, // Kolom untuk nama diskon
    { accessorKey: 'type', header: 'Tipe' }, // Kolom untuk tipe diskon
    { accessorKey: 'amount', header: 'Jumlah' }, // Kolom untuk jumlah diskon
    { accessorKey: 'threshold', header: 'Min Jumlah / Belanja' }, // Kolom untuk ambang batas
    { accessorKey: 'start_date', header: 'Tanggal Mulai' }, // Kolom untuk tanggal mulai
    { accessorKey: 'end_date', header: 'Tanggal Selesai' }, // Kolom untuk tanggal selesai
    { accessorKey: 'is_active', header: 'Aktif' }, // Kolom untuk status aktif
];

// State untuk data tabel
const data = ref([]);
// State untuk filter global
const globalFilter = ref('');
// State untuk pagination
const pagination = ref({
    pageIndex: 0,
    pageSize: 10,
    pageCount: 1,
    total: 0,
});

// State untuk sorting
const sorting = ref({ field: 'id', direction: 'asc' });

// Inisialisasi tabel menggunakan Vue Table
const table = useVueTable({
    get data() { return data.value; }, // Ambil data dari state
    columns,
    getCoreRowModel: getCoreRowModel(),
    getPaginationRowModel: getPaginationRowModel(),
    state: {
        pagination: computed(() => ({
            pageIndex: pagination.value.pageIndex,
            pageSize: pagination.value.pageSize,
        })),
    },
    manualPagination: true, // Gunakan pagination manual
    pageCount: computed(() => pagination.value.pageCount), // Hitung total halaman
    onPaginationChange: (updater) => {
        // Update pagination
        if (typeof updater === 'function') {
            const newPagination = updater(pagination.value);
            pagination.value = { ...pagination.value, ...newPagination };
        } else {
            pagination.value = { ...pagination.value, ...updater };
        }
        fetchData(); // Ambil data terbaru
    }
});

// Fungsi untuk mengambil data diskon
const fetchData = async () => {
    try {
        const response = await axios.get('/api/discounts', {
            params: {
                search: globalFilter.value, // Filter pencarian
                page: pagination.value.pageIndex + 1, // Halaman saat ini
                per_page: pagination.value.pageSize, // Jumlah data per halaman
                sort: sorting.value.field, // Kolom yang digunakan untuk sorting
                direction: sorting.value.direction, // Arah sorting
            }
        });

        // Update data dan pagination berdasarkan respons
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

// State untuk opsi produk
const productOptions = ref([]);
// State untuk produk yang dipilih
const selectedProducts = ref([]);

// Fungsi untuk mengambil data produk
const fetchProducts = async () => {
    try {
        const response = await axios.get('/api/productVariants');

        // Map data produk ke format yang diinginkan
        productOptions.value = response.data.data.map((product) => ({
            id: product.id,
            name: product.name + ' - ' + product.variant,
            value: product.id
        }));
    } catch (error) {
        console.error('Error fetching products:', error);
    }
};

// Fungsi untuk memperbarui ID produk
const updateIdProduct = (value) => {
    selectedProducts.value = value; // Simpan produk yang dipilih
    form.setFieldValue('product_id', value.id); // Set nilai produk di form
};

// Lifecycle hook untuk mengambil data saat komponen dimuat
onMounted(() => {
    fetchData(); // Ambil data diskon
    fetchProducts(); // Ambil data produk
});

// Watcher untuk pagination
watch(() => pagination.value, () => { }, { deep: true });

// Fungsi untuk mengubah halaman
const handlePageChange = (newPageIndex) => {
    pagination.value.pageIndex = newPageIndex; // Update index halaman
    fetchData(); // Ambil data terbaru
};

</script>


<style scope src="vue-multiselect/dist/vue-multiselect.css"></style>


<template>
    <!-- Mengatur judul halaman -->

    <Head title="Daftar Diskon" />

    <AdminLayout>
        <!-- Judul Halaman -->
        <h1 class="text-2xl font-semibold text-gray-900">Daftar Diskon</h1>
        <!-- Button Tambah dan Input Pencarian -->
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

                                <template v-if="cell.column.id === 'is_active'">
                                    <span :class="{
                                        'flex gap-1 items-center': '1',
                                        'text-green-500': cell.getValue() === '1',
                                        'text-red-500': cell.getValue() === '0'
                                    }">
                                        <img class="w-5"
                                            :src="cell.getValue() === '0' ? 'https://img.icons8.com/?size=100&id=63688&format=png&color=000000' : 'https://img.icons8.com/?size=100&id=63312&format=png&color=000000'"
                                            alt="">

                                        {{ cell.getValue() === '1' ? 'Aktif' : 'Tidak Aktif' }}
                                    </span>
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
                                    {{ cell.getValue() ? cell.getValue() : '0' }}
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
                            <FormInput name="amount" label="Jumlah" type="number" />
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
