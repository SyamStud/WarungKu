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
const isApproveModalOpen = ref(false);
const isRejectModalOpen = ref(false);
const selectedStore = ref(null);
const isApprove = ref(false);

// Buka modal tambah toko
const openAddModal = () => {
    form.setValues({
        name: '',
    });
    isApprove.value = false;
    isAddModalOpen.value = true;
};

// Buka modal edit toko
const openApproveModal = (store) => {
    isApprove.value = true;
    selectedStore.value = store;
    form.resetForm();
    form.setValues({
        id: store.id,
    });
    isApproveModalOpen.value = true;
};

// Buka modal hapus toko
const openRejectModal = (store) => {
    selectedStore.value = store;
    form.resetForm();
    form.setValues({
        id: store.id,
        reason: '',
    });
    isRejectModalOpen.value = true;
};

/* -------------- VALIDASI FRONT-END FORM -------------- */
// Skema validasi untuk tambah toko
const approveFormSchema = toTypedSchema(z.object({
    id: z.number(),
}));

// Skema validasi untuk edit toko
const rejectFormSchema = toTypedSchema(z.object({
    id: z.number(),
    reason: z.string().min(5).max(255),
}));

// Gunakan vee-validate untuk validasi form
const form = useForm({
    validationSchema: computed(() => isApprove.value ? approveFormSchema : rejectFormSchema),
});

let isLoading = ref(false); // State untuk loading saat submit form

/* --------------- ACTION SUBMIT FORM --------------- */
const onSubmit = async () => {
    try {
        isLoading.value = true;

        // Validasi form
        await form.validate();

        if (form.errors.value.lenth > 0) {
            isLoading.value = false;
            return Toast.fire({
                icon: "error",
                title: "Masukkan alasan penolakan",
            });
        }

        console.log(selectedStore.value);

        let response = await axios.post(`/super-admin/store-applications/${selectedStore.value.id}?_method=PUT`, form.values);

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
        isApprove.value ? (isApproveModalOpen.value = false) : (isRejectModalOpen.value = false);
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
            isRejectModalOpen.value = false;
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
    { accessorKey: 'created_at', header: 'Tanggal Pengajuan', sortable: true },
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
        const response = await axios.get('/api/store-applications', {
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
import FormLabel from '@/Components/ui/form/FormLabel.vue';
import Textarea from '@/Components/ui/textarea/Textarea.vue';

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
        <h1 class="text-2xl font-semibold text-gray-900">Daftar Pengajuan Toko</h1>

        <!-- Button Tambah Toko dan Input Pencarian -->
        <div class="flex flex-col md:flex-row justify-between my-4">
            <div class="mt-4 md:mt-0 flex md:flex-row flex-col gap-2 items-start md:items-center">
                <div class="flex gap-2 items-center">
                    <Label>Pilih Rentang Export : </Label>
                    <Popover class="w-full">
                        <PopoverTrigger class="w-full" as-child>
                            <Button variant="outline" :class="cn(
                                'w-full md:w-[280px] justify-start text-left font-normal',
                                !value && 'text-muted-foreground',
                            )">
                                <img class="me-2" width="20" height="20"
                                    src="https://img.icons8.com/color/48/calendar--v1.png" alt="calendar--v1" />
                                <template v-if="value.start">
                                    <template v-if="value.end">
                                        {{ df.format(value.start.toDate(getLocalTimeZone())) }} - {{
                                            df.format(value.end.toDate(getLocalTimeZone())) }}
                                    </template>

                                    <template v-else>
                                        {{ df.format(value.start.toDate(getLocalTimeZone())) }}
                                    </template>
                                </template>
                                <template v-else>
                                    Pick a date
                                </template>
                            </Button>
                        </PopoverTrigger>
                        <PopoverContent class="w-full p-0">
                            <RangeCalendar v-model="value" initial-focus :number-of-months="2"
                                @update:start-value="(startDate) => value.start = startDate" />
                        </PopoverContent>
                    </Popover>
                </div>

                <Button @click="exportExcel"
                    class="w-full md:w-max bg-green-700 hover:bg-green-700 flex items-center gap-3">
                    <img class="w-5" src="https://img.icons8.com/?size=100&id=11594&format=png&color=FFFFFF" alt="">
                    Export Excel
                </Button>

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
                                    <Button class="bg-green-600 hover:bg-green-700"
                                        @click="() => openApproveModal(row.original)">
                                        <img class="w-5"
                                            src="https://img.icons8.com/?size=100&id=7690&format=png&color=FFFFFF"
                                            alt="">
                                    </Button>
                                    <Button class="bg-red-600 hover:bg-red-700"
                                        @click="() => openRejectModal(row.original)">
                                        <img class="w-5"
                                            src="https://img.icons8.com/?size=100&id=95771&format=png&color=FFFFFF"
                                            alt="">
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <!-- Pagination -->
            <PaginationWrapper :pagination="pagination" :onPageChange="handlePageChange" />

            <!-- Approve Modal -->
            <DialogWrapper v-model:open="isApproveModalOpen" title="Setujui Pengajuan" desc="">
                <form @submit.prevent="onSubmit" enctype="multipart/form-data" class="space-y-2">
                    <p>Anda yakin akan menyetujui pengajuan toko ini?</p>

                    <DialogFooter>
                        <Button type="submit"
                            :class="{ 'bg-slate-500': isLoading, 'bg-green-600 hover:bg-green-700': !isLoading }"
                            :disabled="isLoading">
                            {{ isLoading ? 'Mohon tunggu ...' : 'Ya, Setujui' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogWrapper>

            <!-- Reject Modal -->
            <DialogWrapper v-model:open="isRejectModalOpen" title="Tolak Pengajuan" desc="">
                <form @submit.prevent="onSubmit" enctype="multipart/form-data" class="space-y-2">

                    <FormField v-slot="{ componentField }" name="reason">
                        <FormItem>
                            <FormLabel>Alasan Penolakan</FormLabel>
                            <FormControl>
                                <Textarea placeholder="Masukkan alasan disini." class="resize-none"
                                    v-bind="componentField" />
                            </FormControl>
                            <FormMessage />
                        </FormItem>
                    </FormField>

                    <DialogFooter>
                        <Button type="submit"
                            :class="{ 'bg-slate-500': isLoading, 'bg-red-600 hover:bg-red-700': !isLoading }"
                            :disabled="isLoading">
                            {{ isLoading ? 'Mohon tunggu ...' : 'Tolak Pengajuan' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogWrapper>
        </div>
    </SuperAdminLayout>
</template>
