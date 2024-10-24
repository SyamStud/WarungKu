<script setup>
import GuestLayout from "@/Layouts/GuestLayout.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import Button from "@/components/ui/button/Button.vue";

const form = useForm({
    name: "",
    email: "",
    password: "",
    password_confirmation: "",
});

const submit = () => {
    form.post(route("register"), {
        onFinish: () => form.reset("password", "password_confirmation"),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Register" />

        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <h2
                class="text-center text-2xl font-bold tracking-tight text-gray-900 mb-8"
            >
                Buat Akun Anda
            </h2>
        </div>

        <div class="bg-white">
            <form @submit.prevent="submit" class="space-y-6">
                <div>
                    <InputLabel
                        for="name"
                        value="Nama Lengkap"
                        class="text-gray-700 font-medium"
                    />

                    <TextInput
                        id="name"
                        type="text"
                        class="text-sm mt-2 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        v-model="form.name"
                        required
                        autofocus
                        autocomplete="name"
                        placeholder="Masukkan nama lengkap"
                    />

                    <InputError class="mt-2" :message="form.errors.name" />
                </div>

                <div>
                    <InputLabel
                        for="email"
                        value="Email"
                        class="text-gray-700 font-medium"
                    />

                    <TextInput
                        id="email"
                        type="email"
                        class="text-sm mt-2 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        v-model="form.email"
                        required
                        autocomplete="username"
                        placeholder="Masukkan email"
                    />

                    <InputError class="mt-2" :message="form.errors.email" />
                </div>

                <div>
                    <InputLabel
                        for="password"
                        value="Password"
                        class="text-gray-700 font-medium"
                    />

                    <TextInput
                        id="password"
                        type="password"
                        class="text-sm mt-2 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        v-model="form.password"
                        required
                        autocomplete="new-password"
                        placeholder="Masukkan password"
                    />

                    <InputError class="mt-2" :message="form.errors.password" />
                </div>

                <div>
                    <InputLabel
                        for="password_confirmation"
                        value="Konfirmasi Password"
                        class="text-gray-700 font-medium"
                    />

                    <TextInput
                        id="password_confirmation"
                        type="password"
                        class="text-sm mt-2 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        v-model="form.password_confirmation"
                        required
                        autocomplete="new-password"
                        placeholder="Masukkan ulang password"
                    />

                    <InputError
                        class="mt-2"
                        :message="form.errors.password_confirmation"
                    />
                </div>

                <div>
                    <Button
                        class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                    >
                        Daftar Sekarang
                    </Button>
                </div>
            </form>

            <div class="mt-6">
                <div class="relative">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-300"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-2 bg-white text-gray-500">Atau</span>
                    </div>
                </div>

                <div class="mt-6">
                    <Link
                        :href="route('login')"
                        class="w-full flex justify-center py-3 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        Sudah Mempunyai Akun
                    </Link>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>
