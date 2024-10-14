<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import axios from 'axios';
import * as z from 'zod';
import { useForm } from 'vee-validate';
import { toTypedSchema } from '@vee-validate/zod';
import { Head } from '@inertiajs/vue3';

import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Input } from '@/Components/ui/input/index.js';
import { useToast } from '@/Composables/useToast';
import FormInput from '@/Components/ui/form/FormInput.vue';
import Spinner from '@/Components/Spinner.vue';
import FormMessage from '@/Components/ui/form/FormMessage.vue';
import { FormField } from '@/Components/ui/form';
import FormItem from '@/Components/ui/form/FormItem.vue';
import FormLabel from '@/Components/ui/form/FormLabel.vue';
import FormControl from '@/Components/ui/form/FormControl.vue';
import DialogWrapper from '@/Components/ui/dialog/DialogWrapper.vue';
import Textarea from '@/Components/ui/textarea/Textarea.vue';
import Button from '@/components/ui/button/Button.vue';

const Toast = useToast();

const sku = ref('');
const formReady = ref(false);
const errors = ref({});
const selectedCategories = ref([]);
const units = ref([]);


// VALIDATION FRONT END FORM
const addFormSchema = toTypedSchema(z.object({
    product_id: z.string(),
    supplier_id: z.string(),
    quantity: z.string(),
    price: z.string(),
    note: z.any().optional(),
}));

const form = useForm({
    validationSchema: computed(() => addFormSchema),

    initialValues: {
        product_id: '',
        supplier_id: '',
        quantity: '',
        price: '',
        note: '',
    },
});

let isLoading = ref(false);

// ACTION FORM 
const onSubmit = async () => {
    try {
        isLoading.value = true;

        const response = await axios.post('/admin/restocks', form.values);

        if (response.data.status === 'error') {
            Toast.fire({
                icon: "error",
                title: "Error saat menambahkan produk",
            });
        } else {
            Toast.fire({
                icon: "success",
                title: response.data.message,
            });

            // Reset the form and clear inputs
            form.resetForm();

            identifier.value = '';
            selectedProduct.value = null;

            identifierSupplier.value = '';
            selectedSupplier.value = null;
        }

        formReady.value = true;
    } catch (error) {
        console.error('Error submitting form:', error);
        Toast.fire({
            icon: "error",
            title: "An error occurred while submitting the form",
        });
    } finally {
        isLoading.value = false;
    }
};

onMounted(async () => {
    formReady.value = true;
});

const identifier = ref('');
const isProductModalOpen = ref(false);
const searchingProduct = ref([]);
const selectedProduct = ref(null);

const identifierSupplier = ref('');
const isSupplierModalOpen = ref(false);
const searchingSupplier = ref([]);
const selectedSupplier = ref(null);

const handleSearchProduct = async () => {
    try {
        const response = await axios.post(`/products/getVariantByName`, { name: identifier.value });

        console.log(response.data.product);
        isProductModalOpen.value = true;
        searchingProduct.value = response.data.product;

        // if (response.data.status === 'success') {
        //     form.setFieldValue('name', response.data.data.name);
        // } else {
        //     form.setFieldValue('name', '');
        // }
    } catch (error) {
        console.error('Error searching product:', error);
    }
};

const handleSearchSupplier = async () => {
    try {
        const response = await axios.post(`/admin/suppliers/getByName`, { name: identifierSupplier.value });

        console.log(response.data.data);
        isSupplierModalOpen.value = true;
        searchingSupplier.value = response.data.data;

        // if (response.data.status === 'success') {
        //     form.setFieldValue('name', response.data.data.name);
        // } else {
        //     form.setFieldValue('name', '');
        // }
    } catch (error) {
        console.error('Error searching supplier:', error);
    }
};

const handleSelect = (product) => {
    selectedProduct.value = product;
    isProductModalOpen.value = false;

    form.setFieldValue('product_id', product.id);
};

const handleSelectSupplier = (supplier) => {
    selectedSupplier.value = supplier;
    isSupplierModalOpen.value = false;

    form.setFieldValue('supplier_id', supplier.id);
};

</script>

<style scope src="vue-multiselect/dist/vue-multiselect.css"></style>

<template>

    <Head title="Form Restock" />

    <AdminLayout>
        <h1 class="text-2xl font-semibold text-gray-900">Tambah Restock Supplier</h1>
        <hr class="my-5 border-[1.5px] bg-gray-300">

        <div v-if="formReady">
            <div class="mx-auto p-6 bg-white rounded-lg">
                <form @submit.prevent="onSubmit" enctype="multipart/form-data" class="space-y-4">
                    <div class="bg-white p-6 border border-gray-200 rounded-lg shadow-sm">
                        <h2 class="text-xl font-semibold text-gray-800 mb-4">Cari Produk</h2>
                        <div class="w-full mb-4">
                            <label for="productSearch" class="block text-sm font-medium text-gray-700 mb-1"
                                :class="{ 'text-red-500': errors['identifier'] }">
                                Masukkan Nama Produk
                            </label>
                            <div class="mt-2 relative rounded-md shadow-sm">
                                <Input id="productSearch" v-model="identifier" type="text" name="identifier"
                                    class="block w-full pr-10 border-gray-300 rounded-md sm:text-sm"
                                    :class="{ 'border-red-300': errors['identifier'] }" placeholder=""
                                    @keyup.enter="handleSearchProduct" @keydown.enter.prevent />
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd"
                                            d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </div>
                            <p v-if="errors['identifier']" class="mt-2 text-sm text-red-600">
                                {{ errors['identifier'][0] }}
                            </p>
                        </div>

                        <div v-if="selectedProduct" class="mt-6 bg-gray-50 p-4 rounded-md border border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900 mb-3">Informasi Produk</h3>
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <p class="text-gray-500 font-medium">SKU</p>
                                    <p class="text-gray-900">{{ selectedProduct.sku || '-' }}</p>
                                </div>
                                <div>
                                    <p class="text-gray-500 font-medium">Nama</p>
                                    <p class="text-gray-900">{{ selectedProduct.name || '-' }}</p>
                                </div>
                                <div>
                                    <p class="text-gray-500 font-medium">Variasi</p>
                                    <p class="text-gray-900">{{ selectedProduct.variant || '-' }}</p>
                                </div>
                                <div>
                                    <p class="text-gray-500 font-medium">Harga</p>
                                    <p class="text-gray-900">{{ selectedProduct.price ? `Rp
                                        ${selectedProduct.price.toLocaleString('id-ID')}` : '-' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>


                    <div class="bg-white p-6 border border-gray-200 rounded-lg shadow-sm">
                        <h2 class="text-xl font-semibold text-gray-800 mb-4">Cari Supplier</h2>
                        <div class="w-full mb-4">
                            <label for="productSearch" class="block text-sm font-medium text-gray-700 mb-1"
                                :class="{ 'text-red-500': errors['identifierSupplier'] }">
                                Masukkan Nama Supplier
                            </label>
                            <div class="mt-2 relative rounded-md shadow-sm">
                                <Input id="productSearch" v-model="identifierSupplier" type="text"
                                    name="identifierSupplier"
                                    class="block w-full pr-10 border-gray-300 rounded-md sm:text-sm"
                                    :class="{ 'border-red-300': errors['identifierSupplier'] }" placeholder=""
                                    @keyup.enter="handleSearchSupplier" @keydown.enter.prevent />
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd"
                                            d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </div>
                            <p v-if="errors['identifier']" class="mt-2 text-sm text-red-600">
                                {{ errors['identifier'][0] }}
                            </p>
                        </div>

                        <div v-if="selectedSupplier" class="mt-6 bg-gray-50 p-4 rounded-md border border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900 mb-3">Informasi Produk</h3>
                            <div class="grid grid-cols-3 gap-4 text-sm">
                                <div>
                                    <p class="text-gray-500 font-medium">Nama Supplier</p>
                                    <p class="text-gray-900">{{ selectedSupplier.name || '-' }}</p>
                                </div>
                                <div>
                                    <p class="text-gray-500 font-medium">Alamat</p>
                                    <p class="text-gray-900">{{ selectedSupplier.address || '-' }}</p>
                                </div>
                                <div>
                                    <p class="text-gray-500 font-medium">Narahubung</p>
                                    <p class="text-gray-900">{{ selectedSupplier.contact_name || '-' }}</p>
                                </div>
                                <div>
                                    <p class="text-gray-500 font-medium">Telepon Narahubung</p>
                                    <p class="text-gray-900">{{ selectedSupplier.contact_phone || '-' }}</p>
                                </div>
                                <div>
                                    <p class="text-gray-500 font-medium">Email Narahubung</p>
                                    <p class="text-gray-900">{{ selectedSupplier.contact_email || '-' }}</p>
                                </div>
                                <div>
                                    <p class="text-gray-500 font-medium">Status Supplier</p>
                                    <p class="text-gray-900">{{ (selectedSupplier.status == `active` ? `Aktif` : `Tidak
                                        Aktif`) || '-' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="p-6 rounded-md border border-gray-200">
                        <div class="flex gap-4">
                            <FormInput name="quantity" label="Jumlah Barang" type="text" />
                            <div class="w-full">
                                <FormInput name="price" label="Total Harga" type="text" />
                            </div>
                        </div>

                        <div class="w-full mt-5">
                            <FormField v-slot="{ componentField }" name="note">
                                <FormItem>
                                    <FormLabel>Catatan</FormLabel>
                                    <FormControl>
                                        <Textarea placeholder="Masukkan Catatan" class="resize-none"
                                            v-bind="componentField" />
                                    </FormControl>
                                    <FormMessage />
                                </FormItem>
                            </FormField>
                        </div>
                    </div>

                    <Button style="margin-top: 25px;" type="submit" :class="{ 'bg-slate-500': isLoading }"
                        :disabled="isLoading">
                        <span :class="{ 'opacity-0': isLoading }">
                            {{ isLoading ? 'Mohon tunggu ...' : 'Tambah Produk' }}
                        </span>
                        <Spinner v-if="isLoading" size="sm" class="absolute inset-0 m-auto" />
                    </Button>
                </form>
            </div>
        </div>

        <div v-else class="mt-20 justify-center flex">
            <Spinner size="lg" class="m-auto" />
        </div>
    </AdminLayout>

    <!-- Add Modal -->
    <DialogWrapper v-model:open="isProductModalOpen" title="Pilih Produk" desc="" custom-class="md:w-[70rem]">
        <div class="space-y-4">
            <table class="w-full border-collapse">
                <thead>
                    <tr>
                        <th class="text-left p-2 border-b">SKU</th>
                        <th class="text-left p-2 border-b">Nama Produk</th>
                        <th class="text-left p-2 border-b">Variasi</th>
                        <th class="text-left p-2 border-b">Harga</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="product in searchingProduct" :key="product.id" @click="handleSelect(product)" :class="{
                        'bg-blue-100': selectedProduct?.id === product.id,
                        'hover:bg-gray-100': selectedProduct?.id !== product.id
                    }" class="cursor-pointer transition-colors duration-150 ease-in-out" tabindex="0"
                        @keydown.enter="handleSelect(product)" @keydown.space.prevent="handleSelect(product)">
                        <td class="p-2 border-b">{{ product.sku }}</td>
                        <td class="p-2 border-b">{{ product.name }}</td>
                        <td class="p-2 border-b">{{ product.variant }}</td>
                        <td class="p-2 border-b">{{ product.price }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </DialogWrapper>

    <!-- Add Modal -->
    <DialogWrapper v-model:open="isSupplierModalOpen" title="Pilih Supplier" desc="" custom-class="md:w-[70rem]">
        <div class="space-y-4">
            <table class="w-full border-collapse">
                <thead>
                    <tr>
                        <th class="text-left p-2 border-b">Nama</th>
                        <th class="text-left p-2 border-b">Alamat</th>
                        <th class="text-left p-2 border-b">Nama Narahubung</th>
                        <th class="text-left p-2 border-b">Telepon Narahubung</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="supplier in searchingSupplier" :key="supplier.id" @click="handleSelectSupplier(supplier)"
                        :class="{
                            'bg-blue-100': selectedSupplier?.id === supplier.id,
                            'hover:bg-gray-100': selectedSupplier?.id !== supplier.id
                        }" class="cursor-pointer transition-colors duration-150 ease-in-out" tabindex="0"
                        @keydown.enter="handleSelectSupplier(supplier)"
                        @keydown.space.prevent="handleSelectSupplier(supplier)">
                        <td class="p-2 border-b">{{ supplier.name }}</td>
                        <td class="p-2 border-b">{{ supplier.address }}</td>
                        <td class="p-2 border-b">{{ supplier.contact_name }}</td>
                        <td class="p-2 border-b">{{ supplier.contact_phone }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </DialogWrapper>
</template>