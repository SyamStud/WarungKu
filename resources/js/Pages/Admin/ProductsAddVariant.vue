<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import axios from 'axios';
import debounce from 'lodash/debounce';
import * as z from 'zod';
import { useForm } from 'vee-validate';
import { toTypedSchema } from '@vee-validate/zod';
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
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
import Separator from '@/Components/ui/separator/Separator.vue';
import Button from '@/components/ui/button/Button.vue';

const Toast = useToast();

const sku = ref('');
const formReady = ref(false);
const errors = ref({});
const selectedProducts = ref([]);
const units = ref([]);

const isCost = ref(true);
const isStock = ref(true);

// Fungsi untuk menangani perubahan pada switch "Harga Modal"
const handleCost = (value) => {
    isCost.value = value;
};

// Fungsi untuk menangani perubahan pada switch "Stok"
const handleStock = (value) => {
    isStock.value = value;
};

// Skema validasi form menggunakan zod dan vee-validate
const addFormSchema = toTypedSchema(z.object({
    product_id: z.number(),
    status: z.string().min(2).max(50),

    variantInputs: z.array(z.object({
        quantity: z.string().min(1).max(50),
        unit_id: z.any(),
        price: z.number().min(1),
        cost: z.any().optional(),
        stock: z.any().optional()
    }))
}));

// Inisialisasi form dengan skema validasi dan nilai awal
const form = useForm({
    validationSchema: computed(() => addFormSchema),

    initialValues: {
        product_id: selectedProducts ? selectedProducts.id : null,
        status: 'active',
        variantInputs: [{ quantity: '1', unit_id: '1', price: '', cost: '', stock: '' }]
    },
});

let isLoading = ref(false);

// Fungsi untuk menangani submit form
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

        form.values.variantInputs.forEach((variant, index) => {
            const quantity = variant.quantity.replace(/\s/g, '');
            form.setFieldValue(`variantInputs.${index}.quantity`, quantity);
        });

        const values = form.values;

        console.log('values', values);

        const response = await axios.post('/admin/products/add-variant', values);

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

            // Reset form dan bersihkan input
            form.resetForm();
            form.setFieldValue('product_id', selectedProducts._rawValue.id);

            sku.value = '';

            const nameInput = document.querySelector('input[name="name"]');
            if (nameInput) {
                nameInput.focus();
            }
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

// Fungsi untuk menghasilkan SKU unik
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

// Fungsi yang dijalankan saat komponen dimuat
onMounted(async () => {
    formReady.value = true;

    await fetchOptions();
});

// Watcher untuk memantau perubahan pada sku dan memperbarui form
watch(sku, (newValue) => {
    form.setFieldValue('sku', newValue);
});

const options = ref([])

// Fungsi untuk mengambil data produk dan unit dari API
const fetchOptions = async () => {
    try {
        const response = await axios.get('/api/products')

        options.value = response.data.data.map((product) => ({
            id: product.id,
            name: product.name,
            value: product.id
        }))

        const unitResponse = await axios.get('/api/units')

        units.value = unitResponse.data.data.map((unit) => ({
            id: unit.id,
            name: unit.name,
            value: unit.id
        }))
    } catch (error) {
        console.error('Error fetching options:', error)
    }
}

// Fungsi untuk memperbarui ID produk yang dipilih
const updateIdProduct = (value) => {
    selectedProducts.value = value;
    form.setFieldValue('product_id', value.id);
};

// Fungsi untuk menambahkan input variasi produk
const addVariantInput = () => {
    const currentVariants = [...form.values.variantInputs];
    currentVariants.push({ quantity: '1', unit_id: '1', price: '', cost: '', stock: '' });
    form.setFieldValue('variantInputs', currentVariants);
};

// Fungsi untuk menghapus input variasi produk
const removeVariantInput = (index) => {
    if (form.values.variantInputs && form.values.variantInputs.length > 1) {
        const currentVariants = [...form.values.variantInputs];
        currentVariants.splice(index, 1);
        form.setFieldValue('variantInputs', currentVariants);
    }
};

// Fungsi untuk memperbarui nilai input variasi produk
const updateVariantInput = (index, field, event) => {
    const currentVariants = [...form.values.variantInputs];
    let value;

    if (event instanceof InputEvent) {
        value = event.target.value;
    } else {
        value = event;
    }

    if (['price', 'cost', 'stock'].includes(field)) {
        value = value === '' ? '' : Number(value);
    } if (field === 'quantity') {
        value = value === '' ? '' : value.toString();
    }

    currentVariants[index] = { ...currentVariants[index], [field]: value };
    form.setFieldValue(`variantInputs.${index}.${field}`, value);
};
</script>

<style scope src="vue-multiselect/dist/vue-multiselect.css"></style>

<template>
    <!-- Mengatur judul halaman -->

    <Head title="Daftar Produk" />

    <AdminLayout>
        <!-- Judul Halaman -->
        <h1 class="text-2xl font-semibold text-gray-900">Tambah Variasi Produk</h1>
        <hr class="my-5 border-[1.5px] bg-gray-300">
        <div class="flex items-center gap-8">
            <div class="flex items-center gap-4">
                <label class="text-md font-bold text-gray-900">Harga Modal</label>
                <Switch :checked="isCost" :modelValue="isCost" @update:checked="handleCost" />
            </div>

            <Separator orientation="vertical" class="h-5 w-[2px] bg-gray-300" />
            <div class="flex items-center gap-4">
                <label class="text-md font-bold text-gray-900 ">Stok</label>
                <Switch :checked="isStock" :modelValue="isStock" @update:checked="handleStock" />
            </div>
        </div>
        <hr class="my-5 border-[1.5px] bg-gray-300">

        <div v-if="formReady">
            <div class="mx-auto p-6 bg-white rounded-lg shadow-lg">
                <form @submit.prevent="onSubmit" enctype="multipart/form-data" class="space-y-4">
                    <div class="flex flex-col md:flex-row gap-4">
                        <div class="w-full">
                            <FormField v-slot="{ field }" name="product_id">
                                <FormItem>
                                    <FormLabel>Nama Produk</FormLabel>
                                    <Multiselect class="h-5" v-model="selectedProducts" :options="options"
                                        @update:modelValue="updateIdProduct" :close-on-select="false"
                                        :preserve-search="true" placeholder="Pilih produk" label="name" track-by="name"
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

                    <div class="py-2 flex items-center mb-4 pt-5">
                        <hr class="mt-1 mr-2 w-10 border-gray-300" />
                        <p class="text-md font-bold text-gray-500">Variasi Produk</p>
                        <hr class="mt-1 flex-grow ml-2 border-gray-300" />
                    </div>

                    <div v-for="(input, index) in form.values.variantInputs" :key="index"
                        class="mb-6 p-4 border border-gray-200 rounded-md">
                        <div class="flex gap-4">
                            <div>
                                <Label for="">Satuan Unit</Label>
                                <div class="w-max flex gap-1">
                                    <div class="w-14">
                                        <FormInput :errors="errors" :name="`variantInputs.${index}.quantity`" label=""
                                            type="text" @input="(e) => updateVariantInput(index, 'quantity', e)" />
                                    </div>

                                    <div class="min-w-max w-28 mt-2">
                                        <FormField v-slot="{ componentField }" :name="`variantInputs.${index}.unit_id`">
                                            <FormItem>
                                                <Select v-model="input.unit_id" v-bind="componentField"
                                                    @update:modelValue="(e) => updateVariantInput(index, 'unit_id', e)">
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
                                <p class="text-sm mt-2 italic text-gray-500">Contoh pecahan : 1/2 KG</p>
                            </div>

                            <div class="w-full">
                                <FormInput :name="`variantInputs.${index}.price`" :value="input.price" :errors="errors"
                                    label="Harga Jual" type="number"
                                    @input="(e) => updateVariantInput(index, 'price', e)" />
                            </div>

                            <div v-if="isCost" class="w-full">
                                <FormInput :name="`variantInputs.${index}.cost`" :id="`cost-${index}`"
                                    :value="input.cost" :errors="errors" label="Harga Modal" type="number"
                                    @input="(e) => updateVariantInput(index, 'cost', e)" />
                            </div>

                            <div v-if="isStock" class="w-full">
                                <FormInput :name="`variantInputs.${index}.stock`" :id="`stock-${index}`"
                                    :value="input.stock" :errors="errors" label="Stok" type="number"
                                    @input="(e) => updateVariantInput(index, 'stock', e)" />
                            </div>
                            <Button type="button" v-if="index > 0" variant="destructive" size="sm"
                                class="mt-8 flex gap-2" @click="removeVariantInput(index)">
                                <svg xmlns="http://www.w3.org/2000/svg" width="1.2em" height="1.2em"
                                    viewBox="0 0 24 24">
                                    <path fill="currentColor"
                                        d="M20 6a1 1 0 0 1 .117 1.993L20 8h-.081L19 19a3 3 0 0 1-2.824 2.995L16 22H8c-1.598 0-2.904-1.249-2.992-2.75l-.005-.167L4.08 8H4a1 1 0 0 1-.117-1.993L4 6zm-9.489 5.14a1 1 0 0 0-1.218 1.567L10.585 14l-1.292 1.293l-.083.094a1 1 0 0 0 1.497 1.32L12 15.415l1.293 1.292l.094.083a1 1 0 0 0 1.32-1.497L13.415 14l1.292-1.293l.083-.094a1 1 0 0 0-1.497-1.32L12 12.585l-1.293-1.292l-.094-.083zM14 2a2 2 0 0 1 2 2a1 1 0 0 1-1.993.117L14 4h-4l-.007.117A1 1 0 0 1 8 4a2 2 0 0 1 1.85-1.995L10 2z" />
                                </svg>
                                Hapus
                            </Button>
                        </div>
                    </div>

                    <Button type="button" @click="addVariantInput"
                        class="mt-4 gap-2 flex bg-green-600 hover:bg-green-700">
                        <svg xmlns="http://www.w3.org/2000/svg" width="1.2em" height="1.2em" viewBox="0 0 24 24">
                            <g fill="none" fill-rule="evenodd">
                                <path
                                    d="m12.594 23.258l-.012.002l-.071.035l-.02.004l-.014-.004l-.071-.036q-.016-.004-.024.006l-.004.01l-.017.428l.005.02l.01.013l.104.074l.015.004l.012-.004l.104-.074l.012-.016l.004-.017l-.017-.427q-.004-.016-.016-.018m.264-.113l-.014.002l-.184.093l-.01.01l-.003.011l.018.43l.005.012l.008.008l.201.092q.019.005.029-.008l.004-.014l-.034-.614q-.005-.019-.02-.022m-.715.002a.02.02 0 0 0-.027.006l-.006.014l-.034.614q.001.018.017.024l.015-.002l.201-.093l.01-.008l.003-.011l.018-.43l-.003-.012l-.01-.01z" />
                                <path fill="currentColor"
                                    d="M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v4h4a2 2 0 0 1 2 2v2a2 2 0 0 1-2 2h-4v4a2 2 0 0 1-2 2h-2a2 2 0 0 1-2-2v-4H5a2 2 0 0 1-2-2v-2a2 2 0 0 1 2-2h4z" />
                            </g>
                        </svg>
                        Tambah Variasi
                    </Button>

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
</template>