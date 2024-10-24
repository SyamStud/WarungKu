<script setup>
import Checkbox from "@/Components/Checkbox.vue";
import GuestLayout from "@/Layouts/GuestLayout.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import Button from "@/components/ui/button/Button.vue";

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: "",
    password: "",
    remember: false,
});

const submit = () => {
    axios.get("/sanctum/csrf-cookie").then((response) => {
        form.post(route("login"), {
            onFinish: () => form.reset("password"),
        });
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Log in" />

        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <h2 class="text-center text-2xl font-bold tracking-tight text-gray-900 mb-8">
                Selamat Datang
            </h2>
        </div>

        <div class="bg-white">
            <div v-if="status" class="mb-4 font-medium text-sm text-green-600 bg-green-50 p-4 rounded">
                {{ status }}
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <div>
                    <InputLabel for="email" value="Email" class="text-gray-700 font-medium" />

                    <TextInput
                        id="email"
                        type="email"
                        class="mt-2 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        v-model="form.email"
                        required
                        autofocus
                        autocomplete="username"
                        placeholder="Masukkan Email"
                    />

                    <InputError class="mt-2" :message="form.errors.email" />
                </div>

                <div>
                    <div class="flex items-center justify-between">
                        <InputLabel for="password" value="Password" class="text-gray-700 font-medium" />
                        <Link
                            v-if="canResetPassword"
                            :href="route('password.request')"
                            class="text-sm text-blue-600 hover:text-blue-500"
                        >
                            Lupa Password?
                        </Link>
                    </div>

                    <TextInput
                        id="password"
                        type="password"
                        class="mt-2 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        v-model="form.password"
                        required
                        autocomplete="current-password"
                        placeholder="Masukkan Password"
                    />

                    <InputError class="mt-2" :message="form.errors.password" />
                </div>

                <div class="flex items-center">
                    <label class="flex items-center text-sm">
                        <Checkbox name="remember" v-model:checked="form.remember" class="text-blue-600 focus:ring-blue-500" />
                        <span class="ms-2 text-gray-600">Ingat Saya</span>
                    </label>
                </div>

                <div>
                    <Button
                        class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                    >
                        Masuk
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
                        :href="route('register')"
                        class="w-full flex justify-center py-3 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        Daftar Akun Baru
                    </Link>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>