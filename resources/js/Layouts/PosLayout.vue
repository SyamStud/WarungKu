<template>
    <div class="min-h-screen bg-gray-100">
        <div>
            <div class="w-full overflow-auto bg-white fixed top-0 z-10">
                <div class="w-full flex justify-between">
                    <nav class="flex items-center px-5 gap-8">
                        <div>
                            <Link v-if="isAdmin" href="/admin/dashboard" class="font-semibold text-sm text-black flex gap-1">
                            <img width="20" height="20" src="https://img.icons8.com/color/48/dashboard-layout.png"
                                alt="dashboard-layout" />
                            Dashboard
                            </Link>
                        </div>
                        <div>
                            <Link href="/pos" class="font-semibold text-sm text-black flex gap-1">
                            <img width="20" height="20"
                                src="https://img.icons8.com/?size=100&id=13225&format=png&color=000000"
                                alt="dashboard-layout" />
                            Halaman Kasir
                            </Link>
                        </div>
                        <div>
                            <Link href="/pos/debt-payments" class="font-semibold text-sm text-black flex gap-1">
                            <img width="20" height="20"
                                src="https://img.icons8.com/?size=100&id=zblJvoWkP1B7&format=png&color=000000"
                                alt="dashboard-layout" />
                            Pembayaran Hutang
                            </Link>
                        </div>
                        <div>
                            <Link href="/settings" class="font-semibold text-sm text-black flex gap-1">
                            <img width="20" height="20" src="https://img.icons8.com/color/48/gear.png" alt="gear" />
                            Pengaturan
                            </Link>
                        </div>
                    </nav>

                    <div class="flex gap-4 items-center text-sm text-gray-500 font-medium">
                        <!-- <p>Petunjuk Keyboard? Tekan [B]</p> -->
                        <Button @click="openLogoutModal" type="button"
                            class="bg-transparent hover:bg-transparent text-sm text-black flex gap-1">
                            <img width="20" height="20"
                                src="https://img.icons8.com/?size=100&id=13925&format=png&color=000000"
                                alt="refund-2--v1" />
                            Logout
                        </Button>
                    </div>
                </div>
            </div>
        </div>

        <slot />
    </div>

    <Sheet v-model:open="isHelperOpen">
        <SheetContent side="left">
            <SheetHeader>
                <SheetTitle>Bantuan</SheetTitle>
                <SheetDescription>
                    Perintah keyboard yang tersedia :
                </SheetDescription>
            </SheetHeader>
            <div class="grid gap-4 py-4">
                <div class="flex mt-2 items-center gap-4">
                    <div
                        class="w-16 h-10 flex text-center items-center justify-center border-2 rounded-md bg-gray-900 text-white">
                        <p class="text-sm">Shift</p>
                    </div>
                    <p class="text-sm text-gray-500 font-semibold">keluar dari mode apapun</p>
                </div>
                <div class="flex mt-2 items-center gap-4">
                    <div
                        class="w-10 h-10 flex text-center items-center justify-center border-2 rounded-md bg-gray-900 text-white">
                        <p>1</p>
                    </div>
                    <p class="text-sm text-gray-500 font-semibold">mode input sku dengan scanner</p>
                </div>
                <div class="flex mt-2 items-center gap-4">
                    <div
                        class="w-10 h-10 flex text-center items-center justify-center border-2 rounded-md bg-gray-900 text-white">
                        <p>2</p>
                    </div>
                    <p class="text-sm text-gray-500 font-semibold">mode input sku dengan mencari produk</p>
                </div>
                <div class="flex mt-2 items-center gap-4">
                    <div
                        class="w-10 h-10 flex text-center items-center justify-center border-2 rounded-md bg-gray-900 text-white">
                        <p>k</p>
                    </div>
                    <p class="text-sm text-gray-500 font-semibold">mengubah kuantitas</p>
                </div>
                <div class="flex mt-2 items-center gap-4">
                    <div
                        class="w-10 h-10 flex text-center items-center justify-center border-2 rounded-md bg-gray-900 text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" width="1.1em" height="1.1em" viewBox="0 0 24 24">
                            <path fill="currentColor" d="M7.03 9.97h4v8.92l2.01.03V9.97h3.99l-5-5Z" />
                        </svg>
                    </div>
                    <p class="text-sm text-gray-500 font-semibold">merubah pilihan kuantitas item yang diubah</p>
                </div>
                <div class="flex mt-2 items-center gap-4">
                    <div
                        class="w-10 h-10 flex text-center items-center justify-center border-2 rounded-md bg-gray-900 text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" width="1.1em" height="1.1em" viewBox="0 0 24 24">
                            <path fill="currentColor" d="M7.03 13.92h4V5l2.01-.03v8.95h3.99l-5 5Z" />
                        </svg>
                    </div>
                    <p class="text-sm text-gray-500 font-semibold">merubah pilihan kuantitas item yang diubah</p>
                </div>
                <div class="flex mt-2 items-center gap-4">
                    <div
                        class="w-10 h-10 flex text-center items-center justify-center border-2 rounded-md bg-gray-900 text-white">
                        <p>b</p>
                    </div>
                    <p class="text-sm text-gray-500 font-semibold">membuka / menutup bantuan</p>
                </div>
                <div class="flex mt-2 items-center gap-4">
                    <div
                        class="w-10 h-10 flex text-center items-center justify-center border-2 rounded-md bg-gray-900 text-white">
                        <p>h</p>
                    </div>
                    <p class="text-sm text-gray-500 font-semibold">menghapus item</p>
                </div>
                <div class="flex mt-2 items-center gap-4">
                    <div
                        class="w-10 h-10 flex text-center items-center justify-center border-2 rounded-md bg-gray-900 text-white">
                        <svg class="rotate-90" xmlns="http://www.w3.org/2000/svg" width="1.1em" height="1.1em"
                            viewBox="0 0 24 24">
                            <path fill="currentColor" d="M7.03 9.97h4v8.92l2.01.03V9.97h3.99l-5-5Z" />
                        </svg>
                    </div>
                    <p class="text-sm text-gray-500 font-semibold">navigasi konfirmasi hapus item</p>
                </div>
                <div class="flex mt-2 items-center gap-4">
                    <div
                        class="w-10 h-10 flex text-center items-center justify-center border-2 rounded-md bg-gray-900 text-white">
                        <svg class="rotate-90" xmlns="http://www.w3.org/2000/svg" width="1.1em" height="1.1em"
                            viewBox="0 0 24 24">
                            <path fill="currentColor" d="M7.03 13.92h4V5l2.01-.03v8.95h3.99l-5 5Z" />
                        </svg>
                    </div>
                    <p class="text-sm text-gray-500 font-semibold">navigasi konfirmasi hapus item</p>
                </div>
            </div>
            <SheetFooter>
                <SheetClose as-child>
                    <Button type="submit" class="mt-5">
                        Tutup
                    </Button>
                </SheetClose>
            </SheetFooter>
        </SheetContent>
    </Sheet>

    <!-- Logout Modal -->
    <DialogWrapper v-model:open="isLogoutModalOpen" title="Logout" desc="">
        <p>Anda yakin akan logout?</p>
        <DialogFooter>
            <Button ref="cancelButton" @click="isLogoutModalOpen = false"
                @keydown.right.prevent="$refs.dialogLogoutButton.$el.focus()"
                @keydown.left.prevent="$refs.dialogLogoutButton.$el.focus()" tabindex="0" variant="outline">
                Batal
            </Button>
            <Link href="/logout" method="post">
            <Button ref="dialogLogoutButton" @keydown.right.prevent="$refs.cancelButton.$el.focus()"
                @keydown.left.prevent="$refs.cancelButton.$el.focus()" tabindex="0"
                :class="{ 'bg-gray-500': isLoading, 'bg-red-500 hover:bg-red-700': !isLoading }" :disabled="isLoading">
                {{ isLoading ? 'Memproses...' : 'Ya, Logout' }}
            </Button>
            </Link>
        </DialogFooter>
    </DialogWrapper>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import {
    Sheet,
    SheetClose,
    SheetContent,
    SheetDescription,
    SheetFooter,
    SheetHeader,
    SheetTitle,
} from '@/Components/ui/sheet'

import DialogWrapper from '@/Components/ui/dialog/DialogWrapper.vue';
import DialogFooter from '@/Components/ui/dialog/DialogFooter.vue';


import { Link, usePage } from '@inertiajs/vue3';
import Button from '@/components/ui/button/Button.vue';

const { props } = usePage();
const user = reactive(props.auth.user);

const isAdmin = ref(false);

onMounted(() => {
    if (user.roles[0].name === 'admin') {
        isAdmin.value = true;
    } else {
        isAdmin.value = false;
    }
});

const cancelButton = ref(null);

const isLoading = ref(false);

const isHelperOpen = ref(false);

const isLogoutModalOpen = ref(false);

const openLogoutModal = () => {
    isLogoutModalOpen.value = true;
};
</script>