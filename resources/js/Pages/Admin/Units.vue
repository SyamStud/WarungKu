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
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Input } from '@/Components/ui/input/index.js';
import { useToast } from '@/Composables/useToast';
import TableHeaderWrapper from '@/Components/ui/table/TableHeaderWrapper.vue';
import { DialogFooter } from '@/Components/ui/dialog';
import DialogWrapper from '@/Components/ui/dialog/DialogWrapper.vue';
import FormInput from '@/Components/ui/form/FormInput.vue';
import { Table, TableBody, TableCell, TableRow } from '@/Components/ui/table';
import PaginationWrapper from '@/Components/ui/pagination/PaginationWrapper.vue';
import Button from '@/components/ui/button/Button.vue';

// Inisialisasi Toast untuk notifikasi
const Toast = useToast();

/* ------------------- MODAL ------------------- */
const isAddModalOpen = ref(false);
const isEditModalOpen = ref(false);
const isDeleteModalOpen = ref(false);
const selectedUnit = ref(null);
const isEdit = ref(false);

// Buka modal tambah unit
const openAddModal = () => {
    form.setValues({
        name: '',
    });
    isEdit.value = false;
    isAddModalOpen.value = true;
};

// Buka modal edit unit
const openEditModal = (unit) => {
    isEdit.value = true;
    selectedUnit.value = unit;
    form.resetForm();
    form.setValues({
        name: unit.name,
    });
    isEditModalOpen.value = true;
};

// Buka modal hapus unit
const openDeleteModal = (unit) => {
    selectedUnit.value = unit;
    isDeleteModalOpen.value = true;
};

/* -------------- VALIDASI FRONT-END FORM -------------- */
// Skema validasi untuk tambah unit
const addFormSchema = toTypedSchema(z.object({
    name: z.string().min(2).max(50),
}));

// Skema validasi untuk edit unit
const editFormSchema = toTypedSchema(z.object({
    name: z.string().min(2).max(50),
}));

// Gunakan vee-validate untuk validasi form
const form = useForm({
    validationSchema: computed(() => isEdit.value ? editFormSchema : addFormSchema),
});

let isLoading = ref(false); // State untuk loading saat submit form

/* --------------- ACTION SUBMIT FORM --------------- */
const onSubmit = form.handleSubmit(async (values) => {
    try {
        isLoading.value = true;
        let response;
        if (isEdit.value) {
            // Update unit yang dipilih
            response = await axios.post(`/admin/units/${selectedUnit.value.id}?_method=PUT`, values);
        } else {
            // Tambah unit baru
            response = await axios.post('/admin/units', values);
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
});

/* --------------- ACTION DELETE UNIT --------------- */
const deleteUnit = async () => {
    if (selectedUnit.value) {
        try {
            const response = await axios.post(`/admin/units/${selectedUnit.value.id}?_method=DELETE`);
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
            fetchData(); // Refresh data setelah unit dihapus
        } catch (error) {
            console.error('Error deleting unit:', error);
        }
    }
};

/* ----------------- TABLE ----------------- */
// Konfigurasi kolom tabel
const columns = [
    { accessorKey: 'name', header: 'Nama Unit' },
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
        const response = await axios.get('/api/units', {
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
</script>



<template>
    <!-- Mengatur judul halaman -->
    <Head title="Daftar Unit" />

    <AdminLayout>
        <!-- Judul Halaman -->
        <h1 class="text-2xl font-semibold text-gray-900">Daftar Unit</h1>

        <!-- Button Tambah Unit dan Input Pencarian -->
        <div class="flex flex-col md:flex-row justify-between">
            <Button @click="openAddModal()" class="w-full md:w-max mt-4 bg-green-700 hover:bg-green-800">Tambah
                Unit</Button>
            <div class="flex items-center py-4 w-full md:w-72">
                <Input placeholder="Cari Unit..." v-model="globalFilter" class="w-full max-w-full md:max-w-sm"
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
            <DialogWrapper style="width: 70rem;" v-model:open="isAddModalOpen" title="Tambah Unit"
                desc="Tambah unit">
                <form @submit="onSubmit" enctype="multipart/form-data" class="space-y-4">
                    <FormInput name="name" label="Nama" type="text" />

                    <DialogFooter>
                        <Button type="submit" :class="{ 'bg-slate-500': isLoading }" :disabled="isLoading">
                            {{ isLoading ? 'Mohon tunggu ...' : 'Tambah Unit' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogWrapper>

            <!-- Edit Modal -->
            <DialogWrapper v-model:open="isEditModalOpen" title="Ubah Unit" desc="Ubah unit">
                <form @submit="onSubmit" enctype="multipart/form-data" class="space-y-4">
                    <FormInput name="name" label="Nama" type="text" />

                    <DialogFooter>
                        <Button type="submit" :class="{ 'bg-slate-500': isLoading }" :disabled="isLoading">
                            {{ isLoading ? 'Mohon tunggu ...' : 'Ubah Unit' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogWrapper>

            <!-- Delete Modal -->
            <DialogWrapper v-model:open="isDeleteModalOpen" title="Hapus Unit" desc="Hapus unit">
                <DialogFooter>
                    <Button @click="isDeleteModalOpen = false" variant="outline">Batal</Button>
                    <Button @click="deleteUnit" variant="destructive">Hapus</Button>
                </DialogFooter>
            </DialogWrapper>
        </div>
    </AdminLayout>
</template>
