<template>
    <div class="min-h-screen bg-white">
        <!-- Hero Section -->
        <div class="mx-auto px-4 py-16 sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden">
                <div class="p-8 text-center">
                    <!-- Illustration -->
                    <div class="flex justify-center mb-8 w-full">
                        <img width="100" height="100"
                            src="https://img.icons8.com/ios-filled/100/228BE6/online-store.png" alt="online-store" />
                    </div>

                    <!-- Content -->
                    <h2 class="text-3xl font-bold text-gray-900 mb-4">
                        Anda Belum Memiliki Toko
                    </h2>

                    <p class="text-lg text-gray-600 mb-8">
                        Mulai perjalanan bisnis Anda sekarang. Buat toko dan kelola penjualan
                        dengan mudah.
                    </p>

                    <!-- Features Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="p-4 bg-blue-50 rounded-lg">
                            <div class="text-blue-500 mb-2">
                                <svg class="w-8 h-8 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                            <h3 class="font-semibold text-gray-900">Mudah Digunakan</h3>
                            <p class="text-sm text-gray-600">Setup toko dalam hitungan menit</p>
                        </div>

                        <div class="p-4 bg-blue-50 rounded-lg">
                            <div class="text-blue-500 mb-2 flex justify-center">
                                <img width="32" height="32"
                                    src="https://img.icons8.com/?size=100&id=sTRJqnhCLCHb&format=png&color=228BE6"
                                    alt="note" />
                            </div>
                            <h3 class="font-semibold text-gray-900">Pencatatan Terstruktur</h3>
                            <p class="text-sm text-gray-600">Pengelolaan penjualan dan inventaris yang rapi</p>
                        </div>


                        <div class="p-4 bg-blue-50 rounded-lg">
                            <div class="text-blue-500 mb-2">
                                <svg class="w-8 h-8 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <h3 class="font-semibold text-gray-900">Aman & Terpercaya</h3>
                            <p class="text-sm text-gray-600">Platform berlisensi dan teruji</p>
                        </div>
                    </div>

                    <Link :href="route('registerStore')">
                    <Button class="mt-10 bg-blue-500 rounded-full p-6">
                        Buat Toko Sekarang
                    </Button>
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import Button from '@/components/ui/button/Button.vue';
import { Link } from '@inertiajs/vue3';

</script>