<script setup>
import Button from '@/Components/ui/button/Button.vue';
import { FormField } from '@/Components/ui/form';
import FormControl from '@/Components/ui/form/FormControl.vue';
import FormInput from '@/Components/ui/form/FormInput.vue';
import FormItem from '@/Components/ui/form/FormItem.vue';
import FormLabel from '@/Components/ui/form/FormLabel.vue';
import FormMessage from '@/Components/ui/form/FormMessage.vue';
import Textarea from '@/Components/ui/textarea/Textarea.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { toTypedSchema } from '@vee-validate/zod';
import { useForm } from 'vee-validate';
import { computed, onMounted, ref } from 'vue';
import { z } from 'zod';
import axios from 'axios';
import Switch from '@/Components/ui/switch/Switch.vue';
import PosLayout from '@/Layouts/PosLayout.vue';

const customer = ref({});

const formSchema = toTypedSchema(z.object({
    shop_name: z.string().min(2).max(50),
    shop_phone: z.string().min(10).max(15),
    shop_address: z.string().min(5).max(255)
}));

const form = useForm({
    validationSchema: formSchema,
    initialValues: {
        shop_name: '',
        shop_phone: '',
        shop_address: ''
    }
});

onMounted(async () => {
    await fetchData();
    form.setValues({
        shop_name: customer.value.shop_name || '',
        shop_phone: customer.value.shop_phone || '',
        shop_address: customer.value.shop_address || '',
    });
});

const onSubmit = async (values) => {
    try {
        await axios.post('/settings/updateSettings', values);
        console.log('Settings updated successfully');
        // Add success message or redirect logic here
    } catch (error) {
        console.error('Error updating settings:', error);
        // Add error handling logic here
    }
};

const fetchData = async () => {
    try {
        const response = await axios.get('/settings/getSettings');
        const settings = response.data.settings;
        customer.value = settings.reduce((acc, setting) => {
            acc[setting.key] = setting.value;
            return acc;
        }, {});
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

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h2 class="text-lg font-medium text-gray-900">Pengaturan Toko</h2>

                        <p class="mt-1 text-sm text-gray-600">
                            Ubah informasi toko
                        </p>

                        <form @submit="form.handleSubmit(onSubmit)" class="mt-6 space-y-6">
                            <div class="flex gap-4">
                                <FormInput v-model="form.values.shop_name" name="shop_name" label="Nama Toko"
                                    type="text" />
                                <FormInput v-model="form.values.shop_phone" name="shop_phone" label="Nomor Telepon Toko"
                                    type="text" />
                            </div>

                            <FormField v-slot="{ field }" name="shop_address">
                                <FormItem>
                                    <FormLabel>Alamat Toko</FormLabel>
                                    <FormControl>
                                        <Textarea v-model="form.values.shop_address" placeholder="Masukkan alamat"
                                            class="resize-none" v-bind="field" />
                                    </FormControl>
                                    <FormMessage />
                                </FormItem>
                            </FormField>

                            <div class="flex items-center gap-4">
                                <Button type="submit" :disabled="form.isSubmitting">
                                    {{ form.isSubmitting ? 'Saving...' : 'Save' }}
                                </Button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="pb-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h2 class="text-2xl font-bold text-gray-900 mb-4">Pengaturan Suara</h2>

                        <p class="text-sm text-gray-600 mb-6">
                            Atur preferensi suara untuk pengalaman yang lebih baik
                        </p>

                        <form @submit="form.handleSubmit(onSubmit)" class="space-y-6">
                            <div class="space-y-4">
                                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                    <label class="text-sm font-medium text-gray-900">Suara konfirmasi item
                                        ditambahkan</label>
                                    <Switch :modelValue="form.soundAddItem"
                                        @update:checked="form.soundAddItem = $event" />
                                </div>

                                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                    <label class="text-sm font-medium text-gray-900">Suara konfirmasi item
                                        dihapus</label>
                                    <Switch :modelValue="form.soundAddItem"
                                        @update:checked="form.soundAddItem = $event" />
                                </div>

                                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                    <label class="text-sm font-medium text-gray-900">Suara peringatan produk tidak
                                        ditemukan</label>
                                    <Switch :modelValue="form.soundItemNotFound"
                                        @update:checked="form.soundItemNotFound = $event" />
                                </div>

                                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                    <label class="text-sm font-medium text-gray-900">Suara konfirmasi metode
                                        pembayaran</label>
                                    <Switch :modelValue="form.soundRepeatItem"
                                        @update:checked="form.soundRepeatItem = $event" />
                                </div>

                                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                    <label class="text-sm font-medium text-gray-900">Suara peringatan pembayaran qris</label>
                                    <Switch :modelValue="form.soundRepeatItem"
                                        @update:checked="form.soundRepeatItem = $event" />
                                </div>

                                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                    <label class="text-sm font-medium text-gray-900">Suara penyebutan nominal
                                        total belanja</label>
                                    <Switch :modelValue="form.soundRepeatItem"
                                        @update:checked="form.soundRepeatItem = $event" />
                                </div>

                                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                    <label class="text-sm font-medium text-gray-900">Suara penyebutan nominal
                                        kembali</label>
                                    <Switch :modelValue="form.soundRepeatItem"
                                        @update:checked="form.soundRepeatItem = $event" />
                                </div>

                                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                    <label class="text-sm font-medium text-gray-900">Suara konfirmasi transaksi
                                        dibatalkan</label>
                                    <Switch :modelValue="form.soundItemNotFound"
                                        @update:checked="form.soundItemNotFound = $event" />
                                </div>

                                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                    <label class="text-sm font-medium text-gray-900">Suara konfirmasi transaksi
                                        berhasil</label>
                                    <Switch :modelValue="form.soundItemNotFound"
                                        @update:checked="form.soundItemNotFound = $event" />
                                </div>
                            </div>

                            <div class="flex items-center justify-end mt-6">
                                <Button type="submit" :disabled="form.isSubmitting" class="w-full sm:w-auto">
                                    {{ form.isSubmitting ? 'Menyimpan...' : 'Simpan Pengaturan' }}
                                </Button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </PosLayout>
</template>