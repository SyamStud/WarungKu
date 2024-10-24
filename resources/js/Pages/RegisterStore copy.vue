<script setup>
import { FormField } from '@/Components/ui/form';
import FormControl from '@/Components/ui/form/FormControl.vue';
import FormInput from '@/Components/ui/form/FormInput.vue';
import FormItem from '@/Components/ui/form/FormItem.vue';
import FormLabel from '@/Components/ui/form/FormLabel.vue';
import FormMessage from '@/Components/ui/form/FormMessage.vue';
import Textarea from '@/Components/ui/textarea/Textarea.vue';
import { useForm, useField } from 'vee-validate';
import { ref } from 'vue';
import { z } from 'zod';

// Form steps
const steps = ['Informasi Pemilik', 'Informasi Dasar', 'Informasi Pelengkap'];
const currentStep = ref(0);

// Form schema validation
const formSchema = z.object({
    nik: z.string().min(16, 'NIK harus berisi minimal 16 karakter'),
    owner_phone: z.string().min(10, 'Nomor telepon minimal 10 digit'),
    owner_address: z.string().min(10, 'Alamat minimal 10 karakter'),
    name: z.string().min(5, 'Nama minimal 5 karakter'),
    email: z.string().email('Email tidak valid'),
    address: z.string().min(10, 'Alamat minimal 10 karakter'),
    phone: z.string().min(10, 'Nomor telepon minimal 10 digit'),
    website: z.string().url('Website tidak valid'),
});

// Menggunakan useForm dari vee-validate untuk mengelola form
const { handleSubmit, values, errors } = useForm({
    validationSchema: formSchema,
    initialValues: {
        nik: '',
        owner_phone: '',
        owner_address: '',
        name: '',
        email: '',
        address: '',
        phone: '',
        website: '',
    },
});

// Mengirim data
const submit = handleSubmit((formValues) => {
    console.log('Form submitted with:', formValues);
});

const handleFileUpload = (event) => {
    values.ktp = event.target.files[0];
};
</script>

<template>
    <div class="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-4xl mx-auto">
            <!-- Header Section -->
            <div class="text-center mb-12">
                <h2 class="text-3xl font-extrabold text-gray-900 sm:text-4xl">Daftarkan Toko Anda</h2>
                <p class="mt-4 text-lg text-gray-600">Isi informasi dibawah ini untuk memulai perjalanan bisnis Anda</p>
            </div>

            <!-- Progress Steps -->
            <div class="mb-8">
                <div class="flex items-center justify-center">
                    <div v-for="(step, index) in steps" :key="index" class="flex items-center">
                        <div :class="[
                            'w-8 h-8 rounded-full flex items-center justify-center font-semibold',
                            currentStep > index
                                ? 'bg-green-500 text-white'
                                : currentStep === index
                                    ? 'bg-blue-500 text-white'
                                    : 'bg-gray-200 text-gray-600'
                        ]">
                            {{ index + 1 }}
                        </div>
                        <div v-if="index < steps.length - 1" :class="[
                            'h-1 w-12 mx-2',
                            currentStep > index ? 'bg-green-500' : 'bg-gray-200'
                        ]"></div>
                    </div>
                </div>
                <div class="flex justify-center mt-2">
                    <span class="text-sm font-medium text-gray-900">{{ steps[currentStep] }}</span>
                </div>
            </div>

            <!-- Form Section -->
            <div class="bg-white py-8 px-6 shadow rounded-lg sm:px-10">
                <form @submit.prevent="submit" class="mb-0 space-y-6">
                    <!-- Step 1: Informasi Pemilik -->
                    <div class="flex flex-col gap-4" v-if="currentStep === 0">
                        <FormInput v-model="values.nik" placeholder="Toko Saya" label="NIK Anda" type="text" />
                        <FormInput v-model="values.owner_phone" placeholder="Toko Saya" label="Nomor Telepon Anda"
                            type="text" />
                        <FormField v-slot="{ componentField }" name="owner_address">
                            <FormItem>
                                <FormLabel>Alamat Anda</FormLabel>
                                <FormControl>
                                    <Textarea v-model="values.owner_address" placeholder="Masukkan alamat"
                                        class="resize-none" v-bind="componentField" />
                                </FormControl>
                                <FormMessage v-if="errors.owner_address" />
                            </FormItem>
                        </FormField>
                    </div>

                    <!-- Step 2: Informasi Dasar -->
                    <div class="flex flex-col gap-4" v-if="currentStep === 1">
                        <FormInput v-model="values.name" placeholder="Toko Saya" label="Nama Toko" type="text" />
                        <FormField v-slot="{ componentField }" name="address">
                            <FormItem>
                                <FormLabel>Alamat Toko</FormLabel>
                                <FormControl>
                                    <Textarea v-model="values.address" placeholder="Masukkan alamat" class="resize-none"
                                        v-bind="componentField" />
                                </FormControl>
                                <FormMessage v-if="errors.address" />
                            </FormItem>
                        </FormField>
                    </div>

                    <!-- Step 3: Dokumen Legal -->
                    <div class="flex flex-col gap-4" v-if="currentStep === 2">
                        <FormInput v-model="values.email" placeholder="example@gmail.com" label="Email Toko"
                            type="email" />
                        <FormInput v-model="values.phone" placeholder="08xxx" label="Nomor Telepon Toko" type="text" />
                        <FormInput v-model="values.website" placeholder="www.contoh.example" label="Alamat Website Toko"
                            type="text" />
                    </div>

                    <!-- Navigation Buttons -->
                    <div class="flex justify-between">
                        <button type="button" v-if="currentStep > 0" @click="currentStep--"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Sebelumnya
                        </button>
                        <button type="button" v-if="currentStep < steps.length - 1" @click="currentStep++"
                            class="ml-auto inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Selanjutnya
                        </button>
                        <button type="submit" v-if="currentStep === steps.length - 1"
                            class="ml-auto inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            Daftarkan Toko
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
