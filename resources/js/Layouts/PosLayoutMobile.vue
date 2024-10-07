<template>
    <div class="min-h-screen bg-gray-100">
        <header class="w-full bg-white fixed top-0 z-50 shadow-sm">
            <div class="container mx-auto px-4">
                <div class="flex justify-between items-center py-4">
                    <!-- Burger menu for small screens -->
                    <button @click="toggleMobileMenu"
                        class="lg:hidden text-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-400 p-2 rounded-md">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16m-7 6h7" />
                        </svg>
                    </button>

                    <!-- Logo (if you have one) -->
                    <div class="text-xl font-bold text-gray-800 lg:hidden">WarungKu</div>

                    <!-- Main Navigation Links -->
                    <nav
                        :class="['lg:flex items-center space-y-4 lg:space-y-0 lg:space-x-8', isMobileMenuOpen ? 'block absolute top-full left-0 right-0 bg-white shadow-md py-4 px-6 lg:py-0 lg:px-0 lg:shadow-none lg:static' : 'hidden lg:flex']">
                        <NavLink v-for="link in navLinks" :key="link.href" v-bind="link" />
                    </nav>

                    <!-- Logout Button -->
                    <Button @click="openLogoutModal" type="button"
                        class="bg-transparent hover:bg-gray-100 text-sm text-black flex items-center gap-1 px-3 py-2 rounded transition-colors">
                        <img width="20" height="20"
                            src="https://img.icons8.com/?size=100&id=13925&format=png&color=000000" alt="logout" />
                        <span class="hidden sm:inline">Logout</span>
                    </Button>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="pt-12">
            <div class="mx-auto py-8">
                <slot />
            </div>
        </main>

        <!-- Logout Modal -->
        <DialogWrapper v-model:open="isLogoutModalOpen" title="Logout" desc="">
            <p class="text-center mb-4">Anda yakin akan logout?</p>
            <DialogFooter class="flex flex-col sm:flex-row justify-center gap-4">
                <Button ref="cancelButton" @click="isLogoutModalOpen = false" variant="outline"
                    class="w-full sm:w-auto">
                    Batal
                </Button>
                <Link href="/logout" method="post" class="w-full sm:w-auto">
                <Button ref="dialogLogoutButton"
                    :class="{ 'bg-gray-500': isLoading, 'bg-red-500 hover:bg-red-700': !isLoading }"
                    :disabled="isLoading" class="w-full">
                    {{ isLoading ? 'Memproses...' : 'Ya, Logout' }}
                </Button>
                </Link>
            </DialogFooter>
        </DialogWrapper>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import Button from '@/components/ui/button/Button.vue';
import DialogWrapper from '@/Components/ui/dialog/DialogWrapper.vue';
import DialogFooter from '@/Components/ui/dialog/DialogFooter.vue';
import NavLink from '@/Components/NavLink.vue';

const navLinks = [
    { href: "/admin/dashboard", icon: "https://img.icons8.com/color/48/dashboard-layout.png", alt: "dashboard-layout", text: "Dashboard" },
    { href: "/pos", icon: "https://img.icons8.com/?size=100&id=13225&format=png&color=000000", alt: "pos", text: "Halaman Kasir" },
    { href: "/pos/debt-payments", icon: "https://img.icons8.com/?size=100&id=zblJvoWkP1B7&format=png&color=000000", alt: "debt-payments", text: "Pembayaran Hutang" },
    { href: "/settings", icon: "https://img.icons8.com/color/48/gear.png", alt: "gear", text: "Pengaturan" },
];

const cancelButton = ref(null);
const isLoading = ref(false);
const isMobileMenuOpen = ref(false);
const isLogoutModalOpen = ref(false);

const toggleMobileMenu = () => {
    isMobileMenuOpen.value = !isMobileMenuOpen.value;
};

const openLogoutModal = () => {
    isLogoutModalOpen.value = true;
};
</script>

<style scoped>
@media (max-width: 1023px) {
    .container {
        max-width: 100%;
    }
}
</style>