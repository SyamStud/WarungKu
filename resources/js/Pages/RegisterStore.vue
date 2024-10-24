<script setup>
import Input from '@/Components/ui/input/Input.vue';
import Label from '@/components/ui/label/Label.vue';
import axios from 'axios';
import { ref } from 'vue'
import { Inertia } from '@inertiajs/inertia';

const steps = ['Informasi Pemilik', 'Informasi Dasar', 'Informasi Pelengkap']
const currentStep = ref(0)

// Inisialisasi nilai form sebagai objek ref
const formValues = ref({
    nik: '',
    owner_phone: '',
    owner_address: '',
    name: '',
    email: '',
    address: '',
    phone: '',
    website: ''
})

// Fungsi untuk handle form submit
const submit = async () => {
    try {
        const response = await axios.post('/stores', formValues.value);
        console.log('Form submitted with:', formValues.value);

        Inertia.visit('/admin/dashboard');
    } catch (error) {
        console.error('Error submitting form:', error);
    }
};


// Fungsi untuk handle input change
const handleInputChange = (event) => {
    const { name, value } = event.target;
    formValues.value[name] = value;
}
</script>

<template>
    <div class="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-4xl mx-auto">
            <!-- Header Section -->
            <div class="text-center mb-12">
                <h2 class="text-3xl font-extrabold text-gray-900 sm:text-4xl">
                    Daftarkan Toko Anda
                </h2>
                <p class="mt-4 text-lg text-gray-600">
                    Isi informasi dibawah ini untuk memulai perjalanan bisnis Anda
                </p>
            </div>

            <!-- Progress Steps -->
            <div class="mb-8">
                <div class="flex items-center justify-center">
                    <div v-for="(step, index) in steps" :key="index" class="flex items-center">
                        <div :class="['w-8 h-8 rounded-full flex items-center justify-center font-semibold',
                            currentStep > index ? 'bg-green-500 text-white' :
                                currentStep === index ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-600']">
                            {{ index + 1 }}
                        </div>
                        <div v-if="index < steps.length - 1" :class="['h-1 w-12 mx-2',
                            currentStep > index ? 'bg-green-500' : 'bg-gray-200']"></div>
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
                        <div>
                            <Label for="nik">NIK Anda</Label>
                            <Input v-model="formValues.nik" @input="handleInputChange" type="text" name="nik" id="nik"
                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3
                                   focus:outline-none sm:text-sm" />
                        </div>

                        <div>
                            <Label for="owner_phone">Nomor Telepon
                                Anda</Label>
                            <Input v-model="formValues.owner_phone" @input="handleInputChange" type="text"
                                name="owner_phone" id="owner_phone" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3
                                   focus:outline-none sm:text-sm" />
                        </div>

                        <div>
                            <Label for="owner_address">Alamat
                                Anda</Label>
                            <textarea v-model="formValues.owner_address" @input="handleInputChange" name="owner_address"
                                id="owner_address" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3
                                      focus:outline-none sm:text-sm resize-none"></textarea>
                        </div>
                    </div>

                    <!-- Step 2: Informasi Dasar -->
                    <div class="flex flex-col gap-4" v-if="currentStep === 1">
                        <div>
                            <Label for="name">Nama Toko</Label>
                            <Input v-model="formValues.name" @input="handleInputChange" type="text" name="name"
                                id="name" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3
                                   focus:outline-none sm:text-sm" />
                        </div>

                        <div>
                            <Label for="address">Alamat Toko</Label>
                            <textarea v-model="formValues.address" @input="handleInputChange" name="address"
                                id="address" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3
                                      focus:outline-none sm:text-sm resize-none"></textarea>
                        </div>
                    </div>

                    <!-- Step 3: Informasi Pelengkap -->
                    <div class="flex flex-col gap-4" v-if="currentStep === 2">
                        <div>
                            <Label for="email">Email Toko</Label>
                            <Input v-model="formValues.email" @input="handleInputChange" type="email" name="email"
                                id="email" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3
                                   focus:outline-none sm:text-sm" />
                        </div>

                        <div>
                            <Label for="phone">Nomor Telepon
                                Toko</Label>
                            <Input v-model="formValues.phone" @input="handleInputChange" type="text" name="phone"
                                id="phone" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3
                                   focus:outline-none sm:text-sm" />
                        </div>

                        <div>
                            <Label for="website">Alamat Website
                                Toko</Label>
                            <Input v-model="formValues.website" @input="handleInputChange" type="text" name="website"
                                id="website" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3
                                   focus:outline-none sm:text-sm" />
                        </div>
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
