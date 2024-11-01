<script setup>
import Button from '@/components/ui/button/Button.vue';
import { FormField } from '@/Components/ui/form';
import FormControl from '@/Components/ui/form/FormControl.vue';
import FormInput from '@/Components/ui/form/FormInput.vue';
import FormItem from '@/Components/ui/form/FormItem.vue';
import FormLabel from '@/Components/ui/form/FormLabel.vue';
import FormMessage from '@/Components/ui/form/FormMessage.vue';
import Input from '@/Components/ui/input/Input.vue';
import Label from '@/components/ui/label/Label.vue';
import Switch from '@/Components/ui/switch/Switch.vue';
import Textarea from '@/Components/ui/textarea/Textarea.vue';
import { useToast } from '@/Composables/useToast';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { usePage } from '@inertiajs/vue3';
import { toTypedSchema } from '@vee-validate/zod';
import axios from 'axios';
import { useForm } from 'vee-validate';
import { onMounted, ref } from 'vue';
import { z } from 'zod';

// Menggunakan composable useToast untuk menampilkan notifikasi
const Toast = useToast();

const { props } = usePage();
const user = ref(props.auth.user);

const storeSettings = ref([]);
const storeProfile = ref([]);

const isLoading = ref(false);

const formSchema = toTypedSchema(z.object({
    store_name: z.string(),
    store_address: z.string(),
    store_phone: z.string(),
    store_email: z.string(),
    store_website: z.string(),
}));

const form = useForm({
    validationSchema: formSchema,
    initialValues: {
        store_name: '',
        store_address: '',
        store_phone: '',
        store_email: '',
        store_website: '',
    },
});

// Fungsi untuk melakukan fetch data settings dari server
const fetchData = async () => {
    try {
        const storeData = await axios.get(`/stores/${user.value.store_id}`);

        storeProfile.value = storeData.data.data;

        form.setValues({
            store_name: storeProfile.value.name,
            store_address: storeProfile.value.address,
            store_phone: storeProfile.value.phone,
            store_email: storeProfile.value.email,
            store_website: storeProfile.value.website,
        });

        console.log(form.values);

        const response = await axios.get('/settings/getSettings');

        storeSettings.value = response.data.store_settings.reduce((acc, setting) => {
            if (setting.value === "1") {
                acc[setting.key] = true;
            } else if (setting.value === "0") {
                acc[setting.key] = false;
            } else {
                acc[setting.key] = setting.value;
            }
            return acc;
        }, {});
    } catch (error) {
        console.error('Error fetching settings:', error);
    }
};

const onSubmit = async () => {
    try {
        isLoading.value = true;

        const response = await axios.post(`/stores/${user.value.store_id}?_method=PUT`, form.values);

        if (response.data.status === 'success') {
            Toast.fire({
                icon: "success",
                title: response.data.message,
            });
        } else {
            Toast.fire({
                icon: "error",
                title: 'Pengaturan gagal diubah',
            });
        }

        isLoading.value = false;
    } catch (error) {
        console.error('Error updating store settings:', error);
    }
};

const changeStoreSettings = async (key, value) => {
    try {
        storeSettings.value[key] = value;

        const response = await axios.post('/settings/storeSettings', { key, value });

        if (response.data.status === 'success') {
            Toast.fire({
                icon: "success",
                title: response.data.message,
            });
        } else {
            Toast.fire({
                icon: "error",
                title: 'Pengaturan gagal diubah',
            });
        }
    } catch (error) {
        console.error('Error updating global settings:', error);
    };
};

onMounted(() => {
    fetchData();
});
</script>

<template>
    <AdminLayout>
        <div class="py-5">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-gray-50 border-2 border-gray-100 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h2 class="text-lg font-medium text-gray-900">Pengaturan Toko</h2>

                        <p class="mt-1 text-sm text-gray-600">
                            Ubah informasi toko
                        </p>

                        <form @submit.prevent="onSubmit" class="mt-6 space-y-6">
                            <FormInput name="store_name" label="Nama Toko" type="text" />
                            <FormInput name="store_phone" label="Telepon Toko" type="text" />
                            <FormInput name="store_email" label="Email Toko" type="text" />
                            <FormInput name="store_website" label="Website Toko" type="text" />

                            <FormField v-slot="{ field }" name="store_address">
                                <FormItem>
                                    <FormLabel>Alamat Toko</FormLabel>
                                    <FormControl>
                                        <Textarea placeholder="Masukkan alamat" class="resize-none" v-bind="field" />
                                    </FormControl>
                                    <FormMessage />
                                </FormItem>
                            </FormField>


                            <Button class="w-full" ype="submit" :disabled="isLoading">
                                {{ isLoading ? 'Saving...' : 'Simpan Pengaturan' }}
                            </Button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div>
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-gray-50 border-2 border-gray-100 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <div class="flex justify-between">
                            <div>
                                <h2 class="text-lg font-medium text-gray-900">Pengaturan Pajak</h2>

                                <p class="mt-1 text-sm text-gray-600">
                                    Ubah informasi pajak
                                </p>
                            </div>

                            <Switch :checked="storeSettings.is_tax" :modelValue="storeSettings.is_tax"
                                @update:checked="changeStoreSettings('is_tax', $event)" />
                        </div>

                        <div v-if="storeSettings.is_tax" class="mt-5">
                            <Label class="mt-4" for="tax_percentage">Persentase Pajak</Label>
                            <Input v-model="storeSettings.tax_percentage" class="mt-2" name="tax" type="number" />

                            <Button class="w-full mt-5"
                                @click="changeStoreSettings('tax_percentage', storeSettings.tax_percentage)"
                                :disabled="isLoading">
                                {{ isLoading ? 'Saving...' : 'Simpan Pengaturan' }}
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div>
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-5">
                <div class="bg-gray-50 border-2 border-gray-100 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <div class="flex justify-between">
                            <div>
                                <h2 class="text-lg font-medium text-gray-900">Pengaturan Printer</h2>

                                <p class="mt-1 text-sm text-gray-600">
                                    Ubah informasi printer
                                </p>
                            </div>
                        </div>

                        <div class="mt-5">
                            <Label class="mt-4" for="printer_name">Nama Printer</Label>
                            <Input v-model="storeSettings.printer_name" class="mt-2" name="tax" type="text" />

                            <Button class="w-full mt-5"
                                @click="changeStoreSettings('printer_name', storeSettings.printer_name)"
                                :disabled="isLoading">
                                {{ isLoading ? 'Saving...' : 'Simpan Pengaturan' }}
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>