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
import FormLabel from '@/Components/ui/form/FormLabel.vue';
import Textarea from '@/Components/ui/textarea/Textarea.vue';

// Inisialisasi Toast untuk notifikasi
const Toast = useToast();

/* ------------------- MODAL ------------------- */
const isAddModalOpen = ref(false);
const isEditModalOpen = ref(false);
const isDeleteModalOpen = ref(false);
const selectedAds = ref(null);
const isEdit = ref(false);
const isPhotoModalOpen = ref(false);

// Buka modal tambah toko
const openAddModal = () => {
    form.setValues({
        title: '',
        image: '',
        logo: '',
        link: '',
        status: '',
        description: '',
    });
    isEdit.value = false;
    isAddModalOpen.value = true;
};

// Buka modal edit toko
const openEditModal = (ads) => {
    isEdit.value = true;
    selectedAds.value = ads;
    form.resetForm();
    form.setValues({
        id: ads.id,
        title: ads.title,
        image: ads.image,
        logo: ads.logo,
        link: ads.link,
        status: ads.status,
        description: ads.description,
    });
    isEditModalOpen.value = true;
};

const openDeleteModal = (ads) => {
    selectedAds.value = ads;
    isDeleteModalOpen.value = true;
};

const imageLink = ref('');
// Fungsi untuk membuka modal foto pengguna
const openPhotoModal = (ads, type) => {
    imageLink.value = type === 'image' ? ads.image : ads.logo;
    isPhotoModalOpen.value = true;
};

/* -------------- VALIDASI FRONT-END FORM -------------- */
// Skema validasi untuk tambah toko
const addFormSchema = toTypedSchema(z.object({
    title: z.string().min(5).max(255),
    image: z.any(),
    logo: z.any(),
    link: z.string().min(5).max(255),
    status: z.string().min(5).max(255),
    description: z.string().min(5).max(255),
}));

// Skema validasi untuk edit toko
const editFormSchema = toTypedSchema(z.object({
    title: z.string().min(5).max(255),
    image: z.any(),
    logo: z.any(),
    link: z.string().min(5).max(255),
    status: z.string().min(5).max(255),
    description: z.string().min(5).max(255),
}));

// Gunakan vee-validate untuk validasi form
const form = useForm({
    validationSchema: computed(() => isEdit.value ? editFormSchema : addFormSchema),
});

let isLoading = ref(false); // State untuk loading saat submit form

/* --------------- ACTION SUBMIT FORM --------------- */
const onSubmit = async () => {
    try {
        isLoading.value = true;

        let response;

        console.log(form.values);

        if (isEdit.value) {
            response = await axios.post(`/super-admin/ads/slides/${selectedAds.value.id}?_method=PUT`, form.values, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });
        } else {
            response = await axios.post('/super-admin/ads/slides', form.values, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });
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

/* --------------- ACTION DELETE ADS --------------- */
const deleteAds = async () => {
    if (selectedAds.value) {
        try {
            const response = await axios.post(`/super-admin/ads/slides/${selectedAds.value.id}?_method=DELETE`);
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
            console.error('Error deleting ads:', error);
        }
    }
};

/* ----------------- TABLE ----------------- */
// Konfigurasi kolom tabel
const columns = [
    { accessorKey: 'title', header: 'Judul Iklan' },
    { accessorKey: 'link', header: 'Link' },
    { accessorKey: 'status', header: 'Status' },
    { accessorKey: 'description', header: 'Deskripsi' },
    { accessorKey: 'created_at', header: 'Dibuat Pada' },
    { accessorKey: 'image', header: 'Gambar' },
    { accessorKey: 'logo', header: 'Logo' },
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
        const response = await axios.get('/api/ads', {
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

const onFileChange = (event, type) => {
    const file = event.target.files[0];

    if (type === 'image') {
        form.setValues({ image: file });
    } else {
        form.setValues({ logo: file });
    }
};
</script>



<template>
    <!-- Mengatur judul halaman -->

    <Head title="Daftar Toko" />

    <SuperAdminLayout>
        <!-- Judul Halaman -->
        <h1 class="text-2xl font-semibold text-gray-900">Daftar Slide Iklan</h1>

        <!-- Button Tambah Toko dan Input Pencarian -->
        <div class="flex flex-col md:flex-row justify-start my-4">
            <Button @click="openAddModal"
                class="w-full md:w-max mt-4 bg-green-700 hover:bg-green-800 flex items-center gap-1">
                <img class="w-5" src="https://img.icons8.com/?size=100&id=FnGQHvpuVbBr&format=png&color=FFFFFF" alt="">
                Tambah Slide
            </Button>
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
                                <template v-if="cell.column.id === 'image'">
                                    <Button @click="() => openPhotoModal(row.original, 'image')">Lihat</Button>
                                </template>

                                <template v-else-if="cell.column.id === 'logo'">
                                    <Button @click="() => openPhotoModal(row.original, 'logo')">Lihat</Button>
                                </template>

                                <template v-else>
                                    {{ cell.getValue() }}
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
            <DialogWrapper v-model:open="isAddModalOpen" title="Tambah Slide" desc="Tambah slide iklan">
                <form @submit.prevent="onSubmit" enctype="multipart/form-data" class="space-y-2">

                    <FormInput name="title" label="Judul Iklan" type="text" />

                    <FormInput name="image" label="Gambar" type="file">
                        <Input class="w-full" type="file" name="image"
                            @change="(event) => onFileChange(event, 'image')" />
                    </FormInput>

                    <FormInput name="logo" label="Logo" type="file">
                        <Input class="w-full" type="file" name="logo"
                            @change="(event) => onFileChange(event, 'logo')" />
                    </FormInput>

                    <FormInput name="link" label="Link" type="text" />

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
                                    </SelectGroup>
                                </SelectContent>
                            </Select>
                            <FormMessage />
                        </FormItem>
                    </FormField>

                    <FormField v-slot="{ componentField }" name="description">
                        <FormItem>
                            <FormLabel>Deskripsi</FormLabel>
                            <FormControl>
                                <Textarea class="resize-none" v-bind="componentField" />
                            </FormControl>
                            <FormMessage />
                        </FormItem>
                    </FormField>

                    <DialogFooter>
                        <Button type="submit" :class="{ 'bg-slate-500': isLoading, }" :disabled="isLoading">
                            {{ isLoading ? 'Mohon tunggu ...' : 'Tambah Slide' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogWrapper>

            <!-- Edit Modal -->
            <DialogWrapper v-model:open="isEditModalOpen" title="Ubah Slide" desc="Ubah slide iklan">
                <form @submit.prevent="onSubmit" enctype="multipart/form-data" class="space-y-2">

                    <FormInput name="title" label="Judul Iklan" type="text" />

                    <FormInput name="image" label="Gambar" type="file">
                        <Input class="w-full" type="file" name="image"
                            @change="(event) => onFileChange(event, 'image')" />
                    </FormInput>

                    <FormInput name="logo" label="Logo" type="file">
                        <Input class="w-full" type="file" name="logo"
                            @change="(event) => onFileChange(event, 'logo')" />
                    </FormInput>

                    <FormInput name="link" label="Link" type="text" />

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
                                    </SelectGroup>
                                </SelectContent>
                            </Select>
                            <FormMessage />
                        </FormItem>
                    </FormField>

                    <FormField v-slot="{ componentField }" name="description">
                        <FormItem>
                            <FormLabel>Deskripsi</FormLabel>
                            <FormControl>
                                <Textarea class="resize-none" v-bind="componentField" />
                            </FormControl>
                            <FormMessage />
                        </FormItem>
                    </FormField>

                    <DialogFooter>
                        <Button type="submit" :class="{ 'bg-slate-500': isLoading, }" :disabled="isLoading">
                            {{ isLoading ? 'Mohon tunggu ...' : 'Tambah Slide' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogWrapper>

            <!-- Reject Modal -->
            <DialogWrapper v-model:open="isDeleteModalOpen" title="Hapus Slide" desc="">
                <p>Anda yakin akan menghapus slide iklan ini?</p>

                <DialogFooter>
                    <Button @click="deleteAds"
                        :class="{ 'bg-slate-500': isLoading, 'bg-red-600 hover:bg-red-700': !isLoading }"
                        :disabled="isLoading">
                        {{ isLoading ? 'Mohon tunggu ...' : 'Ya, Hapus' }}
                    </Button>
                </DialogFooter>
            </DialogWrapper>

            <!-- Photo Modal -->
            <DialogWrapper v-model:open="isPhotoModalOpen" title="Photo" desc="">
                <div class="flex gap-2">
                    <img :src="`http://127.0.0.1:8000/storage/${imageLink}`"
                        class="h-72 p-1 w-full rounded-xl border-dashed border-2">
                </div>
                <DialogFooter>
                    <Button @click="isPhotoModalOpen = false" variant="outline">Tutup</Button>
                </DialogFooter>
            </DialogWrapper>
        </div>
    </SuperAdminLayout>
</template>
