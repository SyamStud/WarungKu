<script setup>
// Import dependencies dan komponen
import { ref, onMounted, computed, watch } from 'vue';
import axios from 'axios';
import debounce from 'lodash/debounce';
import * as z from 'zod';
import { useForm } from 'vee-validate';
import { toTypedSchema } from '@vee-validate/zod';
import { Head } from '@inertiajs/vue3';
import { useVueTable, getCoreRowModel, getPaginationRowModel } from '@tanstack/vue-table';
import SuperAdminLayout from '@/Layouts/SuperAdminLayout.vue';
import { Input } from '@/Components/ui/input/index.js';
import { useToast } from '@/Composables/useToast';
import TableHeaderWrapper from '@/Components/ui/table/TableHeaderWrapper.vue';
import { DialogFooter } from '@/Components/ui/dialog';
import DialogWrapper from '@/Components/ui/dialog/DialogWrapper.vue';
import FormInput from '@/Components/ui/form/FormInput.vue';
import { Table, TableBody, TableCell, TableRow } from '@/Components/ui/table';
import PaginationWrapper from '@/Components/ui/pagination/PaginationWrapper.vue';
import Button from '@/components/ui/button/Button.vue';
import { FormField } from '@/Components/ui/form';
import FormItem from '@/Components/ui/form/FormItem.vue';
import Select from '@/Components/ui/select/Select.vue';
import FormControl from '@/Components/ui/form/FormControl.vue';
import SelectTrigger from '@/Components/ui/select/SelectTrigger.vue';
import SelectValue from '@/Components/ui/select/SelectValue.vue';
import SelectContent from '@/Components/ui/select/SelectContent.vue';
import SelectGroup from '@/Components/ui/select/SelectGroup.vue';
import SelectItem from '@/Components/ui/select/SelectItem.vue';
import FormMessage from '@/Components/ui/form/FormMessage.vue';
import Label from '@/components/ui/label/Label.vue';

// Inisialisasi Toast untuk notifikasi
const Toast = useToast();

/* ------------------- MODAL ------------------- */
const isAddModalOpen = ref(false);
const isEditModalOpen = ref(false);
const isDeleteModalOpen = ref(false);
const selectedStore = ref(null);
const isEdit = ref(false);

// Buka modal tambah toko
const openAddModal = () => {
    form.setValues({
        name: '',
    });
    isEdit.value = false;
    isAddModalOpen.value = true;
};

// Buka modal edit toko
const openEditModal = (store) => {
    isEdit.value = true;
    selectedStore.value = store;
    form.resetForm();
    form.setValues({
        status: store.status,
    });
    isEditModalOpen.value = true;
};

// Buka modal hapus toko
const openDeleteModal = (store) => {
    selectedStore.value = store;
    isDeleteModalOpen.value = true;
};

/* -------------- VALIDASI FRONT-END FORM -------------- */
// Skema validasi untuk tambah toko
const addFormSchema = toTypedSchema(z.object({
    name: z.string().min(2).max(50),
}));

// Skema validasi untuk edit toko
const editFormSchema = toTypedSchema(z.object({
    name: z.string().min(2).max(50),
}));

// Gunakan vee-validate untuk validasi form
const form = useForm({
    validationSchema: computed(() => isEdit.value ? editFormSchema : addFormSchema),
});

let isLoading = ref(false); // State untuk loading saat submit form

/* --------------- ACTION SUBMIT FORM --------------- */
const onSubmit = async (values) => {
    try {
        isLoading.value = true;
        let response;
        if (isEdit.value) {
            // Update toko yang dipilih
            response = await axios.post(`/super-admin/stores/${selectedStore.value.id}?_method=PUT`, form.values);
        } else {
            // Tambah toko baru
            response = await axios.post('/super-admin/stores', form.values);
        }

        // Notifikasi berdasarkan status respons
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

        // Tutup modal setelah submit
        isEdit.value ? (isEditModalOpen.value = false) : (isAddModalOpen.value = false);
        fetchData(); // Refresh data setelah submit
        isLoading.value = false;
    } catch (error) {
        console.error('Error submitting form:', error);
        isLoading.value = false;
    }
};

/* --------------- ACTION DELETE STORE --------------- */
const deleteStore = async () => {
    if (selectedStore.value) {
        try {
            const response = await axios.post(`/super-admin/stores/${selectedStore.value.id}?_method=DELETE`);
            // Notifikasi berdasarkan status respons
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
            fetchData(); // Refresh data setelah toko dihapus
        } catch (error) {
            console.error('Error deleting store:', error);
        }
    }
};

/* ----------------- TABLE ----------------- */
// Konfigurasi kolom tabel
const columns = [
    { accessorKey: 'name', header: 'Nama' },
    { accessorKey: 'address', header: 'Alamat' },
    { accessorKey: 'phone', header: 'Telepon' },
    { accessorKey: 'email', header: 'Email' },
    { accessorKey: 'website', header: 'Website' },
    { accessorKey: 'owner', header: 'Pemilik' },
    { accessorKey: 'status', header: 'Status' },
    { accessorKey: 'created_at', header: 'Dibuat', sortable: true },
];

// Inisialisasi state untuk data tabel dan pagination
const data = ref([]);
const globalFilter = ref('');
const pagination = ref({
    pageIndex: 0,
    pageSize: 10,
    pageCount: 1,
    total: 0,
});

// Sorting data berdasarkan kolom
const sorting = ref({ field: 'id', direction: 'asc' });

// Konfigurasi TanStack Table
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
        fetchData(); // Refresh data setelah pagination diubah
    }
});

// Fungsi untuk mengambil data dari API
const fetchData = async () => {
    try {
        const response = await axios.get('/api/stores', {
            params: {
                search: globalFilter.value,
                page: pagination.value.pageIndex + 1,
                per_page: pagination.value.pageSize,
                sort: sorting.value.field,
                direction: sorting.value.direction,
            }
        });

        // Set data tabel dan informasi pagination
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

// Debounce untuk menghindari permintaan berulang terlalu cepat
const debouncedFetchData = debounce(fetchData, 300);

// Fungsi untuk mengubah urutan sorting berdasarkan kolom
const sortBy = (field) => {
    if (sorting.value.field === field) {
        sorting.value.direction = sorting.value.direction === 'asc' ? 'desc' : 'asc';
    } else {
        sorting.value.field = field;
        sorting.value.direction = 'asc';
    }
    fetchData();
};

// Fetch data saat komponen dimount
onMounted(fetchData);

// Watcher untuk memantau perubahan pagination dan memanggil fetchData saat ada perubahan
watch(() => pagination.value, () => { }, { deep: true });

// Fungsi untuk menangani perubahan halaman pada pagination
const handlePageChange = (newPageIndex) => {
    pagination.value.pageIndex = newPageIndex;
    fetchData();
};

import { RangeCalendar } from '@/Components/ui/range-calendar'
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover'
import { cn } from '@/lib/utils';

import {
    CalendarDate,
    DateFormatter,
    getLocalTimeZone,
} from '@internationalized/date'

// Formatter untuk tanggal
const df = new DateFormatter('en-US', {
    dateStyle: 'medium',
})

// Menggunakan ref tanpa tipe DateValue (JavaScript tidak membutuhkan tipe)
const value = ref({
    start: new CalendarDate(new Date().getFullYear(), new Date().getMonth() + 1, new Date().getDate()),
    end: new CalendarDate(new Date().getFullYear(), new Date().getMonth() + 1, new Date().getDate()),
})

const formatDate = (date) => {
    // Format the date as YYYY-MM-DD
    return date.getFullYear() + '-' +
        ('0' + (date.getMonth() + 1)).slice(-2) + '-' +
        ('0' + date.getDate()).slice(-2);
}

const exportExcel = async () => {
    const startDateObj = value.value.start.toDate(getLocalTimeZone());
    const endDateObj = value.value.end.toDate(getLocalTimeZone());

    const startDate = formatDate(startDateObj);
    const endDate = formatDate(endDateObj);
    console.log(startDate, endDate);

    window.location.href = `/admin/stock-movements/export-excel?start_date=${startDate}&end_date=${endDate}`;
}
</script>



<template>
    <!-- Mengatur judul halaman -->

    <Head title="Daftar Toko" />

    <SuperAdminLayout>
        <!-- Judul Halaman -->
        <h1 class="text-2xl font-semibold text-gray-900">Daftar Toko</h1>

        <!-- Button Tambah Toko dan Input Pencarian -->
        <div class="flex flex-col md:flex-row justify-between">
            <a href="/admin/products/excel-export">
                <Button class="w-full md:w-max mt-4 bg-green-700 hover:bg-green-700 flex items-center gap-3">
                    <img class="w-5" src="https://img.icons8.com/?size=100&id=11594&format=png&color=FFFFFF" alt="">
                    Export Excel
                </Button>
            </a>

            <div class="flex items-center py-4 w-full md:w-72">
                <Input placeholder="Cari Toko..." v-model="globalFilter" class="w-full max-w-full md:max-w-sm"
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

            <!-- Edit Modal -->
            <DialogWrapper v-model:open="isEditModalOpen" title="Ubah Status Toko" desc="Ubah status toko">
                <form @submit="onSubmit" enctype="multipart/form-data" class="space-y-2">
                    <Label class="">Status</Label>
                    <div class="w-full">
                        <FormField v-slot="{ componentField }" name="status">
                            <FormItem>
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
                                        </SelectGroup>
                                    </SelectContent>
                                </Select>
                                <FormMessage />
                            </FormItem>
                        </FormField>
                    </div>

                    <DialogFooter>
                        <Button type="submit" :class="{ 'bg-slate-500': isLoading }" :disabled="isLoading">
                            {{ isLoading ? 'Mohon tunggu ...' : 'Ubah Status Toko' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogWrapper>

            <!-- Delete Modal -->
            <DialogWrapper v-model:open="isDeleteModalOpen" title="Hapus Toko" desc="Hapus toko">
                <DialogFooter>
                    <Button @click="isDeleteModalOpen = false" variant="outline">Batal</Button>
                    <Button @click="deleteStore" variant="destructive">Hapus</Button>
                </DialogFooter>
            </DialogWrapper>
        </div>
    </SuperAdminLayout>
</template>
