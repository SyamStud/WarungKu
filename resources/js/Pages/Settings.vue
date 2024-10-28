<script setup>
import { FormField } from '@/Components/ui/form';
import FormControl from '@/Components/ui/form/FormControl.vue';
import FormInput from '@/Components/ui/form/FormInput.vue';
import FormItem from '@/Components/ui/form/FormItem.vue';
import FormLabel from '@/Components/ui/form/FormLabel.vue';
import FormMessage from '@/Components/ui/form/FormMessage.vue';
import Textarea from '@/Components/ui/textarea/Textarea.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { toTypedSchema } from '@vee-validate/zod';
import { useForm } from 'vee-validate';
import { onMounted, ref } from 'vue';
import { z } from 'zod';
import axios from 'axios';
import Switch from '@/Components/ui/switch/Switch.vue';
import PosLayout from '@/Layouts/PosLayout.vue';
import Button from '@/components/ui/button/Button.vue';
import { useToast } from '@/Composables/useToast';
import Spinner from '@/Components/Spinner.vue';
import Input from '@/Components/ui/input/Input.vue';
import Label from '@/components/ui/label/Label.vue';

// Menggunakan composable useToast untuk menampilkan notifikasi
const Toast = useToast();

// Mendefinisikan state untuk customer, isLoading, globalSettings, dan userSettings
const isLoading = ref(false);
const globalSettings = ref({});
const userSettings = ref({});
const storeSettings = ref({});

// Mendefinisikan skema validasi menggunakan zod
const formSchema = toTypedSchema(z.object({
    store_name: z.string().min(2).max(50),
    store_address: z.string().min(5).max(255),
    sound_product_not_found: z.boolean(),
    sound_payment_method: z.boolean(),
    sound_alert_qris_payment: z.boolean(),
    sound_change: z.boolean(),
    sound_cancel_transaction: z.boolean(),
    sound_success_transaction: z.boolean(),
}));

// Menggunakan useForm dari vee-validate untuk mengelola form
const form = useForm({
    validationSchema: formSchema,
    initialValues: {
        store_name: storeSettings.value.store_name,
        store_address: storeSettings.value.store_address,
        sound_product_not_found: userSettings.value.sound_product_not_found,
        sound_payment_method: userSettings.value.sound_payment_method,
        sound_alert_qris_payment: userSettings.value.sound_alert_qris_payment,
        sound_change: userSettings.value.sound_change,
        sound_cancel_transaction: userSettings.value.sound_cancel_transaction,
        sound_success_transaction: userSettings.value.sound_success_transaction,
    },
});

// Mengambil props dari usePage
const { props } = usePage();

// Menggunakan lifecycle hook onMounted untuk melakukan fetch data saat komponen dimuat
onMounted(async () => {
    isLoading.value = true;
    await fetchData();

    // Set nilai awal setelah data di-fetch
    form.setValues({
        store_name: storeSettings.value.store_name,
        store_address: storeSettings.value.store_address,
        sound_product_not_found: userSettings.value.sound_product_not_found,
        sound_payment_method: userSettings.value.sound_payment_method,
        sound_alert_qris_payment: userSettings.value.sound_alert_qris_payment,
        sound_change: userSettings.value.sound_change,
        sound_cancel_transaction: userSettings.value.sound_cancel_transaction,
        sound_success_transaction: userSettings.value.sound_success_transaction,
    });

    isLoading.value = false;
});

// Fungsi untuk menangani submit form
const onSubmit = async () => {
    try {
        const response = await axios.post('/settings', form.values);

        if (response.data.status === 'success') {
            Toast.fire({
                icon: "success",
                title: 'Pengaturan berhasil disimpan',
            });
        } else {
            Toast.fire({
                icon: "error",
                title: 'Gagal menyimpan pengaturan',
            });
        }

    } catch (error) {
        console.error('Error updating settings:', error);
    }
};

// Fungsi untuk mengubah user settings
const changeUserSettings = async (key, value) => {
    try {
        userSettings.value[key] = value;

        const response = await axios.post('/userSettings', { key, value });

        if (response.data.status === 'success') {
            Toast.fire({
                icon: "success",
                title: 'Pengaturan berhasil diubah',
            });
        } else {
            Toast.fire({
                icon: "error",
                title: 'Gagal mengubah pengaturan',
            });
        }
    } catch (error) {
        console.error('Error updating user settings:', error);
    };
};

// Fungsi untuk mengubah global settings
const changeGlobalSettings = async (key, value) => {
    try {
        globalSettings.value[key] = value;

        const response = await axios.post('/globalSettings', { key, value });

        if (response.data.status === 'success') {
            if (value) {
                Toast.fire({
                    icon: "success",
                    title: 'Pengaturan berhasil diubah',
                });
            } else {
                Toast.fire({
                    icon: "success",
                    title: 'Pengaturan berhasil diubah',
                });
            }
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

// Fungsi untuk melakukan fetch data settings dari server
const fetchData = async () => {
    try {
        const response = await axios.get('/settings/getSettings');

        globalSettings.value = response.data.global_settings.reduce((acc, setting) => {
            if (setting.value === "1") {
                acc[setting.key] = true;
            } else if (setting.value === "0") {
                acc[setting.key] = false;
            } else {
                acc[setting.key] = setting.value;
            }
            return acc;
        }, {});

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

        userSettings.value = response.data.user_settings.reduce((acc, setting) => {
            if (setting.value === "1") {
                acc[setting.key] = true;
            } else if (setting.value === "0") {
                acc[setting.key] = false;
            } else {
                acc[setting.key] = setting.value;
            }
            return acc;
        }, {});

        console.log('storeSettings', storeSettings.value);
    } catch (error) {
        console.error('Error fetching settings:', error);
    }
};
</script>

<template>

    <Head title="Dashboard" />

    <PosLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dashboard</h2>
        </template>

        <div class="pt-20" v-if="!isLoading">
            <!-- <div class="py-5">
                <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6 text-gray-900">
                            <h2 class="text-lg font-medium text-gray-900">Pengaturan Toko</h2>

                            <p class="mt-1 text-sm text-gray-600">
                                Ubah informasi toko
                            </p>

                            <form @submit.prevent="onSubmit" class="mt-6 space-y-6">
                                <FormInput name="store_name" label="Nama Toko" type="text" />

                                <FormField v-slot="{ field }" name="store_address">
                                    <FormItem>
                                        <FormLabel>Alamat Toko</FormLabel>
                                        <FormControl>
                                            <Textarea placeholder="Masukkan alamat" class="resize-none"
                                                v-bind="field" />
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
            </div> -->

            <div>
                <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
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
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
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

            <div class="pb-12 mt-5">
                <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6 text-gray-900">
                            <h2 class="text-2xl font-bold text-gray-900 mb-4">Pengaturan Suara</h2>

                            <p class="text-sm text-gray-600 mb-6">
                                Atur preferensi suara untuk pengalaman yang lebih baik
                            </p>

                            <form @submit.prevent="" class="space-y-6">
                                <div class="space-y-4">
                                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                        <label class="text-sm font-medium text-gray-900">Suara peringatan produk tidak
                                            ditemukan</label>
                                        <Switch :checked="userSettings.sound_product_not_found"
                                            :modelValue="userSettings.sound_product_not_found"
                                            @update:checked="changeUserSettings('sound_product_not_found', $event)" />
                                    </div>

                                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                        <label class="text-sm font-medium text-gray-900">Suara konfirmasi metode
                                            pembayaran</label>
                                        <Switch :checked="userSettings.sound_payment_method"
                                            :modelValue="userSettings.sound_payment_method"
                                            @update:checked="changeUserSettings('sound_payment_method', $event)" />
                                    </div>

                                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                        <label class="text-sm font-medium text-gray-900">Suara peringatan pembayaran
                                            qris</label>
                                        <Switch :checked="userSettings.sound_alert_qris_payment"
                                            :modelValue="userSettings.sound_alert_qris_payment"
                                            @update:checked="changeUserSettings('sound_alert_qris_payment', $event)" />
                                    </div>

                                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                        <label class="text-sm font-medium text-gray-900">Suara penyebutan nominal
                                            kembali</label>
                                        <Switch :checked="userSettings.sound_change"
                                            :modelValue="userSettings.sound_change"
                                            @update:checked="changeUserSettings('sound_change', $event)" />
                                    </div>

                                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                        <label class="text-sm font-medium text-gray-900">Suara konfirmasi transaksi
                                            dibatalkan</label>
                                        <Switch :checked="userSettings.sound_cancel_transaction"
                                            :modelValue="userSettings.sound_cancel_transaction"
                                            @update:checked="changeUserSettings('sound_cancel_transaction', $event)" />
                                    </div>

                                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                        <label class="text-sm font-medium text-gray-900">Suara konfirmasi transaksi
                                            berhasil</label>
                                        <Switch :checked="userSettings.sound_success_transaction"
                                            :modelValue="userSettings.sound_success_transaction"
                                            @update:checked="changeUserSettings('sound_success_transaction', $event)" />
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-else class="pt-36 justify-center flex">
            <Spinner size="lg" class="m-auto" />
        </div>
    </PosLayout>
</template>