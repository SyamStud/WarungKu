<template>
    <div class="min-h-screen flex items-center justify-center bg-red-50 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full bg-white rounded-lg shadow-lg p-8 space-y-5">
            <div class="flex flex-col items-center">
                <svg class="w-16 h-16 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <h2 class="mt-6 text-center text-3xl font-bold text-gray-900">
                    PERINGATAN
                </h2>
                <p class="mt-2 text-center text-sm text-gray-600">
                    Halaman ini tidak dapat diakses melalui perangkat mobile.
                </p>
            </div>
            <div class="text-center text-sm text-gray-600 bg-gray-100 p-5 rounded-md shadow-inner">
                <p>Mohon maaf atas ketidaknyamanannya. Halaman ini hanya dapat diakses melalui perangkat desktop atau
                    laptop.</p>
                <p class="mt-4">Silakan kunjungi situs kami menggunakan komputer atau laptop untuk mengakses halaman ini.</p>
            </div>
            <div class="text-center">
                <button @click="kembaliKeHalamanUtama"
                    class="w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150 ease-in-out">
                    Kembali ke Halaman Utama
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()

const kembaliKeHalamanUtama = () => {
    if (router) {
        router.push({ name: 'home' }).catch(() => {
            console.warn("Navigasi gagal, periksa rute 'home' di router Anda.")
        })
    }
}

onMounted(() => {
    const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)

    if (!isMobile) {
        if (router) {
            router.push({ name: 'konten-desktop' }).catch(() => {
                console.warn("Navigasi gagal, periksa rute 'konten-desktop' di router Anda.")
            })
        }
    }
})
</script>

<style scoped>
svg {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse {

    0%,
    100% {
        opacity: 1;
    }

    50% {
        opacity: 0.5;
    }
}
</style>