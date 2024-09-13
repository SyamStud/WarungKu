<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import axios from 'axios';
import debounce from 'lodash/debounce';
import * as z from 'zod';
import { useForm } from 'vee-validate';
import { toTypedSchema } from '@vee-validate/zod';
import { Head, Link } from '@inertiajs/vue3';

import AdminLayout from '@/Layouts/AdminLayout.vue';
import Button from '@/Components/ui/button/Button.vue';
import { Input } from '@/Components/ui/input/index.js';
import { useToast } from '@/Composables/useToast';
import FormInput from '@/Components/ui/form/FormInput.vue';
import Label from '@/components/ui/label/Label.vue';
import Switch from '@/Components/ui/switch/Switch.vue';
import Spinner from '@/Components/Spinner.vue';
import FormMessage from '@/Components/ui/form/FormMessage.vue';
import { FormField } from '@/Components/ui/form';
import FormItem from '@/Components/ui/form/FormItem.vue';
import FormLabel from '@/Components/ui/form/FormLabel.vue';
import Multiselect from 'vue-multiselect';
import Select from '@/Components/ui/select/Select.vue';
import FormControl from '@/Components/ui/form/FormControl.vue';
import SelectTrigger from '@/Components/ui/select/SelectTrigger.vue';
import SelectValue from '@/Components/ui/select/SelectValue.vue';
import SelectContent from '@/Components/ui/select/SelectContent.vue';
import SelectGroup from '@/Components/ui/select/SelectGroup.vue';
import SelectItem from '@/Components/ui/select/SelectItem.vue';
import Textarea from '@/Components/ui/textarea/Textarea.vue';
import Separator from '@/Components/ui/separator/Separator.vue';

const Toast = useToast();

const sku = ref('');
const isScan = ref(false);
const formReady = ref(false);
const errors = ref({});
const selectedCategories = ref([])

const isCost = ref(false);
const isStock = ref(false);
const isDescription = ref(false);

const handleScan = (value) => {
    isScan.value = value;
    console.log(isScan.value);

    if (isScan.value) {
        sku.value = '';
        form.setFieldValue('sku', '');
    } else {
        generateSKU();
    }
};

const handleCost = (value) => {
    isCost.value = value;
};

const handleStock = (value) => {
    isStock.value = value;
};

const handleDescription = (value) => {
    isDescription.value = value;
};

const updateFormSKU = () => {
    form.setFieldValue('sku', sku.value);
};

// VALIDATION FRONT END FORM
const addFormSchema = toTypedSchema(z.object({
    category_id: z.number(),
    sku: z.string().min(2).max(50),
    name: z.string().min(2).max(50),
    price: z.number().min(0),
    cost: z.any(),
    stock: z.any(),
    description: z.any(),
    status: z.string().min(2).max(50),
}));

const form = useForm({
    validationSchema: computed(() => addFormSchema),

    initialValues: {
        category_id: selectedCategories ? selectedCategories.id : null,
        sku: '',
        name: '',
        price: null,
        status: 'active',
    },
});

let isLoading = ref(false);

// ACTION FORM 
const onSubmit = async () => {
    try {
        isLoading.value = true;

        const isValid = await form.validate();

        if (!isValid.valid) {
            Toast.fire({
                icon: "error",
                title: "Lengkapi form terlebih dahulu",
            });

            return;
        }

        const values = form.values;

        const response = await axios.post('/admin/products', values);

        if (response.data.status === 'error') {
            errors.value = response.data.message;
            console.log('error', errors.value);

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
            form.setFieldValue('category_id', selectedCategories._rawValue.id);

            sku.value = '';

            if (!isScan.value) {
                generateSKU();
            }

            const nameInput = document.querySelector('input[name="name"]');
            if (nameInput) {
                nameInput.focus();
            }
        }

        // formReady.value = true;
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

const generateSKU = async () => {
    let uniqueSKU = false;
    let newSKU = '';

    while (!uniqueSKU) {
        newSKU = 'CSTM' + Math.floor(Math.random() * 1000000000).toString();
        const response = await axios.post(`/api/products/check-sku`, { sku: newSKU });
        uniqueSKU = response.data.unique;
    }

    sku.value = newSKU;
    form.setFieldValue('sku', newSKU);
};

onMounted(async () => {
    if (!isScan.value) {
        await generateSKU();
    }
    formReady.value = true;

    await fetchOptions();
});

// Watch for changes in sku and update form
watch(sku, (newValue) => {
    form.setFieldValue('sku', newValue);
});

const options = ref([])


const fetchOptions = async () => {
    try {
        const response = await axios.get('/api/categories')

        options.value = response.data.data.map((category) => ({
            id: category.id,
            name: category.name,
            value: category.id
        }))
    } catch (error) {
        console.error('Error fetching options:', error)
    }
}

const updateIdCategory = (value) => {
    selectedCategories.value = value;
    form.setFieldValue('category_id', value.id);
};
</script>

<style scope src="vue-multiselect/dist/vue-multiselect.css"></style>

<template>

    <Head title="Daftar Produk" />

    <AdminLayout>
        <h1 class="text-2xl font-semibold text-gray-900">Tambah Produk</h1>
        <div class="mt-10 flex items-center gap-4">
            <label class="text-md font-bold text-gray-900">Scan Barcode</label>
            <Switch :modelValue="isScan" @update:checked="handleScan" />
            <p class="text-sm font-semibold text-gray-500 italic">Aktifkan jika memasukkan SKU dengan scanner</p>
        </div>
        <hr class="my-5 border-[1.5px] bg-gray-300">
        <div class="flex items-center gap-8">
            <div class="flex items-center gap-4">
                <label class="text-md font-bold text-gray-900">Harga Modal</label>
                <Switch :modelValue="isCost" @update:checked="handleCost" />
            </div>

            <Separator orientation="vertical" class="h-5 w-[2px] bg-gray-300" />
            <div class="flex items-center gap-4">
                <label class="text-md font-bold text-gray-900 ">Stok</label>
                <Switch :modelValue="isStock" @update:checked="handleStock" />
            </div>

            <Separator orientation="vertical" class="h-5 w-[1.5px] bg-gray-300" />
            <div class="flex items-center gap-4">
                <label class="text-md font-bold text-gray-900">Deksripsi</label>
                <Switch :modelValue="isDescription" @update:checked="handleDescription" />
            </div>
        </div>
        <hr class="my-5 border-[1.5px] bg-gray-300">

        <div v-if="formReady">
            <form @submit.prevent="onSubmit" enctype="multipart/form-data" class="space-y-4 mt-10">
                <div class="flex flex-col md:flex-row gap-4">
                    <div class="w-full">
                        <FormField v-slot="{ field }" name="category_id">
                            <FormItem>
                                <FormLabel>Kategori Produk</FormLabel>
                                <Multiselect v-model="selectedCategories" :options="options"
                                    @update:modelValue="updateIdCategory" :close-on-select="false"
                                    :preserve-search="true" placeholder="Pilih kategori" label="name" track-by="name"
                                    :preselect-first="false" />
                                <FormMessage />
                            </FormItem>
                        </FormField>
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
                    <div class="w-full">
                        <Label :class="{ 'text-red-500': errors['sku'] }">SKU</Label>
                        <Input v-model="sku" :readonly="!isScan" type="text" name="sku" class="mt-2"
                            @input="updateFormSKU"></Input>
                        <p v-if="errors['sku']" class="text-red-500 font-semibold text-sm mt-2">{{ errors['sku'][0] }}
                        </p>
                    </div>
                </div>
                <div class="flex flex-col md:flex-row gap-4 mt-2">
                    <div class="w-full">
                        <FormInput :errors="errors" name="name" label="Nama Produk" type="text" />
                        <p v-if="errors['name']" class="text-red-500 font-semibold text-sm mt-2">{{ errors['name'][0] }}
                        </p>
                    </div>
                    <div class="w-full">
                        <FormInput :errors="errors" name="price" label="Harga Jual" type="number" />
                        <p v-if="errors['price']" class="text-red-500 font-semibold text-sm mt-2">{{ errors['price'][0]
                            }}</p>
                    </div>
                </div>
                <div class="flex flex-col md:flex-row gap-4 mt-2">
                    <div v-if="isCost" class="w-full">
                        <FormInput :errors="errors" name="cost" label="Harga Modal" type="number" />
                        <p v-if="errors['cost']" class="text-red-500 font-semibold text-sm mt-2">{{ errors['cost'][0] }}
                        </p>
                    </div>
                    <div v-if="isStock" class="w-full">
                        <FormInput :errors="errors" name="stock" label="Stok" type="number" />
                        <p v-if="errors['stock']" class="text-red-500 font-semibold text-sm mt-2">{{ errors['stock'][0]
                            }}</p>
                    </div>
                </div>

                <FormField v-if="isDescription" v-slot="{ componentField }" name="description">
                    <FormItem>
                        <FormLabel>Deskripsi</FormLabel>
                        <FormControl>
                            <Textarea placeholder="Masukkan deskripsi saat ini." class="resize-none"
                                v-bind="componentField" />
                        </FormControl>
                        <FormMessage />
                    </FormItem>
                </FormField>

                <Button type="submit" :class="{ 'bg-slate-500': isLoading }" :disabled="isLoading">
                    <span :class="{ 'opacity-0': isLoading }">
                        {{ isLoading ? 'Mohon tunggu ...' : 'Tambah Produk' }}
                    </span>
                    <Spinner v-if="isLoading" size="sm" class="absolute inset-0 m-auto" />
                </Button>
            </form>
        </div>

        <div v-else class="mt-20 justify-center flex">
            <Spinner size="lg" class="m-auto" />
        </div>
    </AdminLayout>
</template>