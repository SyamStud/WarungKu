<script setup>
import Input from "@/Components/ui/input/Input.vue";
import Label from "@/components/ui/label/Label.vue";
import { router } from '@inertiajs/vue3'
import axios from "axios";
import { ref } from "vue";

// Steps and form states
const steps = ["Informasi Pemilik", "Informasi Dasar", "Informasi Pelengkap"];
const currentStep = ref(0);
const errors = ref({});


// Initialize form values
const formValues = ref({
    nik: "",
    owner_phone: "",
    owner_address: "",
    name: "",
    email: "",
    address: "",
    phone: "",
    website: "",
});

// Validate form input for the current step
const validateStep = () => {
    errors.value = {};

    if (currentStep.value === 0) {
        if (!formValues.value.nik) errors.value.nik = "NIK wajib diisi";
        if (!formValues.value.owner_phone)
            errors.value.owner_phone = "Nomor telepon wajib diisi";
        if (!formValues.value.owner_address)
            errors.value.owner_address = "Alamat wajib diisi";
    }

    if (currentStep.value === 1) {
        if (!formValues.value.name) errors.value.name = "Nama toko wajib diisi";
        if (!formValues.value.address)
            errors.value.address = "Alamat toko wajib diisi";
    }

    if (currentStep.value === 2) {
        if (!formValues.value.email) {
            errors.value.email = "Email toko wajib diisi";
        } else if (!/\S+@\S+\.\S+/.test(formValues.value.email)) {
            errors.value.email = "Format email tidak valid";
        }

        if (!formValues.value.phone)
            errors.value.phone = "Nomor telepon toko wajib diisi";
    }

    return Object.keys(errors.value).length === 0;
};

// Handle form submit
const submit = async () => {
    if (!validateStep()) {
        console.error("Form validation failed:", errors.value);
        return;
    }

    try {
        const response = await axios.post("/stores", formValues.value);
        console.log("Form submitted with:", formValues.value);

        router.visit('/waiting-approval');
    } catch (error) {
        console.error("Error submitting form:", error);
    }
};

// Handle next step
const nextStep = () => {
    if (validateStep()) {
        currentStep.value++;
    }
};

// Handle previous step
const prevStep = () => {
    if (currentStep.value > 0) {
        currentStep.value--;
    }
};

// Handle input change
const handleInputChange = (event) => {
    const { name, value } = event.target;
    formValues.value[name] = value;
};
</script>

<template>
    <div class="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-4xl mx-auto">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-extrabold text-gray-900 sm:text-4xl">
                    Daftarkan Toko Anda
                </h2>
                <p class="mt-4 text-lg text-gray-600">
                    Isi informasi dibawah ini untuk memulai perjalanan bisnis
                    Anda
                </p>
            </div>

            <!-- Progress Steps -->
            <div class="mb-8">
                <div class="flex items-center justify-center">
                    <div
                        v-for="(step, index) in steps"
                        :key="index"
                        class="flex items-center"
                    >
                        <div
                            :class="[
                                'w-8 h-8 rounded-full flex items-center justify-center font-semibold',
                                currentStep > index
                                    ? 'bg-green-500 text-white'
                                    : currentStep === index
                                    ? 'bg-blue-500 text-white'
                                    : 'bg-gray-200 text-gray-600',
                            ]"
                        >
                            {{ index + 1 }}
                        </div>
                        <div
                            v-if="index < steps.length - 1"
                            :class="[
                                'h-1 w-12 mx-2',
                                currentStep > index
                                    ? 'bg-green-500'
                                    : 'bg-gray-200',
                            ]"
                        ></div>
                    </div>
                </div>
                <div class="flex justify-center mt-2">
                    <span class="text-sm font-medium text-gray-900">{{
                        steps[currentStep]
                    }}</span>
                </div>
            </div>

            <!-- Form Section -->
            <div class="bg-white py-8 px-6 shadow rounded-lg sm:px-10">
                <form @submit.prevent="submit" class="mb-0 space-y-6">
                    <!-- Step 1: Informasi Pemilik -->
                    <div class="flex flex-col gap-4" v-if="currentStep === 0">
                        <div>
                            <Label for="nik">NIK Anda</Label>
                            <Input
                                v-model="formValues.nik"
                                @input="handleInputChange"
                                type="text"
                                name="nik"
                                id="nik"
                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none sm:text-sm"
                            />
                            <span
                                v-if="errors.nik"
                                class="text-red-500 text-sm"
                                >{{ errors.nik }}</span
                            >
                        </div>

                        <div>
                            <Label for="owner_phone">Nomor Telepon Anda</Label>
                            <Input
                                v-model="formValues.owner_phone"
                                @input="handleInputChange"
                                type="text"
                                name="owner_phone"
                                id="owner_phone"
                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none sm:text-sm"
                            />
                            <span
                                v-if="errors.owner_phone"
                                class="text-red-500 text-sm"
                                >{{ errors.owner_phone }}</span
                            >
                        </div>

                        <div>
                            <Label for="owner_address">Alamat Anda</Label>
                            <textarea
                                v-model="formValues.owner_address"
                                @input="handleInputChange"
                                name="owner_address"
                                id="owner_address"
                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none sm:text-sm resize-none"
                            ></textarea>
                            <span
                                v-if="errors.owner_address"
                                class="text-red-500 text-sm"
                                >{{ errors.owner_address }}</span
                            >
                        </div>
                    </div>

                    <!-- Step 2: Informasi Dasar -->
                    <div class="flex flex-col gap-4" v-if="currentStep === 1">
                        <div>
                            <Label for="name">Nama Toko</Label>
                            <Input
                                v-model="formValues.name"
                                @input="handleInputChange"
                                type="text"
                                name="name"
                                id="name"
                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none sm:text-sm"
                            />
                            <span
                                v-if="errors.name"
                                class="text-red-500 text-sm"
                                >{{ errors.name }}</span
                            >
                        </div>

                        <div>
                            <Label for="address">Alamat Toko</Label>
                            <textarea
                                v-model="formValues.address"
                                @input="handleInputChange"
                                name="address"
                                id="address"
                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none sm:text-sm resize-none"
                            ></textarea>
                            <span
                                v-if="errors.address"
                                class="text-red-500 text-sm"
                                >{{ errors.address }}</span
                            >
                        </div>
                    </div>

                    <!-- Step 3: Informasi Pelengkap -->
                    <div class="flex flex-col gap-4" v-if="currentStep === 2">
                        <div>
                            <Label for="email">Email Toko</Label>
                            <Input
                                v-model="formValues.email"
                                @input="handleInputChange"
                                type="email"
                                name="email"
                                id="email"
                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none sm:text-sm"
                            />
                            <span
                                v-if="errors.email"
                                class="text-red-500 text-sm"
                                >{{ errors.email }}</span
                            >
                        </div>

                        <div>
                            <Label for="phone">Nomor Telepon Toko</Label>
                            <Input
                                v-model="formValues.phone"
                                @input="handleInputChange"
                                type="text"
                                name="phone"
                                id="phone"
                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none sm:text-sm"
                            />
                            <span
                                v-if="errors.phone"
                                class="text-red-500 text-sm"
                                >{{ errors.phone }}</span
                            >
                        </div>

                        <div>
                            <Label for="website">Alamat Website Toko</Label>
                            <Input
                                v-model="formValues.website"
                                @input="handleInputChange"
                                type="text"
                                name="website"
                                id="website"
                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none sm:text-sm"
                            />
                        </div>
                    </div>

                    <!-- Navigation Buttons -->
                    <div class="flex justify-between">
                        <button
                            type="button"
                            v-if="currentStep > 0"
                            @click="prevStep"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                        >
                            Sebelumnya
                        </button>
                        <button
                            type="button"
                            v-if="currentStep < steps.length - 1"
                            @click="nextStep"
                            class="ml-auto inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                        >
                            Selanjutnya
                        </button>
                        <button
                            type="submit"
                            v-if="currentStep === steps.length - 1"
                            class="ml-auto inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                        >
                            Daftarkan Toko
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
