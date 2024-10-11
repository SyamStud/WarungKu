<template>

    <Head title="Welcome" />

    <PosLayoutMobile>
        <div class="flex flex-col min-h-screen">
            <div class="flex-grow">
                <div class="sticky top-0 bg-white z-20">
                    <div class="bg-gray-100 w-full py-5 px-5">
                        <div class="w-full space-y-2">
                            <div class="flex items-center space-x-2 justify-center text-center">
                                <span class="text-gray-600 font-semibold text-sm">Kode Transaksi:</span>
                                <h3 class="text-md font-bold text-gray-900">{{ transactionCode }}</h3>
                            </div>
                            <p class="text-sm text-center text-gray-500">
                                {{ currentDay }}, {{ currentDate }} - {{ currentTime }}
                            </p>
                        </div>

                        <form @submit.prevent="addProduct" class="mt-5">
                            <Input autofocus ref="identifierInputRef" v-model="identifierInput"
                                @keyup.enter="addProduct" @keyup="handleKeyUp" @blur="handleBlur"
                                placeholder="Scan atau cari nama produk"
                                class="w-full px-4 h-16 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" />
                        </form>
                    </div>
                </div>

                <div class="w-full px-5 pb-32 mt-5">
                    <ProductTableMobile ref="productTableRef" :items="cartItems" @update-variant="updateVariant"
                        :newly-added-item-id="newlyAddedItemId" @update-quantity="updateQuantity"
                        @remove-item="removeItem" :updating-item-id="updatingItemId" />
                </div>
            </div>

            <div class="bg-gray-100 w-full fixed z-10 bottom-0 left-0 pt-5 px-5">
                <div class="w-full space-y-2 mt-30">
                    <div class="flex items-center space-x-2 justify-center text-center mb-5">
                        <span class="text-gray-600 font-semibold text-sm">Total:</span>
                        <h3 class="text-lg font-bold text-gray-900">{{ formatRupiah(grandTotal) }}</h3>
                    </div>
                </div>

                <div class="flex gap-2 item-center mb-5">
                    <Button v-if="grandTotal" @click="openRevokeModal" class="w-1/2 h-14 bg-red-500 hover:bg-red-600">
                        Batalkan
                    </Button>

                    <Button v-if="grandTotal" @click="openPaymentModal"
                        class="w-full h-14 bg-blue-500 hover:bg-blue-600">
                        Proses Pembayaran
                    </Button>
                </div>

                <div class="flex justify-center items-center p-2 w-full text-center space-x-1">
                    <p class="text-xs text-gray-500">
                        © 2024 - <span class="font-semibold">Product Activated</span> -
                    </p>
                    <a href="https://icons8.com/" class="text-xs text-blue-500 hover:underline hover:text-blue-600">
                        Icons by Icons8
                    </a>
                </div>
            </div>
        </div>

    </PosLayoutMobile>

    <!-- Add Modal -->
    <DialogWrapper v-model:open="isAddModalOpen" title="Pilih Produk" desc="" custom-class="md:w-[70rem]">
        <div class="space-y-4">
            <table class="w-full border-collapse">
                <thead>
                    <tr>
                        <th class="text-left p-2 border-b">Nama Produk</th>
                        <th class="text-left p-2 border-b">Variasi</th>
                        <th class="text-left p-2 border-b">Harga</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="product in searchingProduct" :key="product.id" @click="handleSelect(product)" :class="{
                        'bg-blue-100': selectedProduct?.id === product.id,
                        'hover:bg-gray-100': selectedProduct?.id !== product.id
                    }" class="cursor-pointer transition-colors duration-150 ease-in-out" tabindex="0"
                        @keydown.enter="handleSelect(product)" @keydown.space.prevent="handleSelect(product)">
                        <td class="p-2 border-b">{{ product.name }}</td>
                        <td class="p-2 border-b">{{ product.variant }}</td>
                        <td class="p-2 border-b">{{ product.price }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </DialogWrapper>


    <AlertDialog v-model:open="isChangeModalOpen">
        <AlertDialogContent>
            <AlertDialogHeader>
                <div class="bg-blue-50 p-6 rounded-lg shadow-inner" style="margin-top: 5px;">
                    <h5 class="text-sm font-semibold text-gray-600 mb-2">Nominal Kembali</h5>
                    <h5 :class="[{ 'font-extrabold text-3xl': kembali > 0, 'font-bold text-3xl': kembali <= 0 }]">
                        <span v-if="kembali > 0">{{ formatRupiah(kembali) }}</span>
                        <span v-else-if="kembali == 0">Uang pas</span>
                        <span v-else-if="kembali == null">Masukkan nominal pembayaran</span>
                        <span v-else class="text-red-500">Nominal pembayaran kurang</span>
                    </h5>
                    <p class="text-sm text-gray-600 mt-2">Terbilang :
                        <span v-if="kembali > 0" class="font-bold">{{ useTerbilang(kembali) }}</span>
                    </p>
                </div>
            </AlertDialogHeader>
            <AlertDialogFooter>
                <AlertDialogAction @click="kembali = null">Tutup</AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>

    <!-- Revoke Modal -->
    <DialogWrapper v-model:open="isRevokeModalOpen" title="Batalkan Transaksi" desc="Batalkan transaksi">
        <DialogFooter>
            <Button ref="cancelButton" @click="isRevokeModalOpen = false"
                @keydown.right.prevent="$refs.dialogRevokeButton.$el.focus()"
                @keydown.left.prevent="$refs.dialogRevokeButton.$el.focus()" tabindex="0" variant="outline">
                Batal
            </Button>
            <Button ref="dialogRevokeButton" @click="revokeTransaction"
                @keydown.right.prevent="$refs.cancelButton.$el.focus()"
                @keydown.left.prevent="$refs.cancelButton.$el.focus()" tabindex="0"
                :class="{ 'bg-gray-500': isLoading, 'bg-red-500 hover:bg-red-700': !isLoading }" :disabled="isLoading">
                {{ isLoading ? 'Memproses...' : 'Batalkan Transaksi' }}
            </Button>
        </DialogFooter>
    </DialogWrapper>

    <!-- Payment Modal -->
    <DialogWrapper v-model:open="isPaymentModalOpen" title="Proses Pembayaran" desc="">
        <div class="space-y-6">
            <!-- Total Belanja Section -->
            <div class="bg-gray-100 p-6 rounded-lg shadow-inner">
                <h5 class="text-sm font-semibold text-gray-600 mb-2">Total Belanja</h5>
                <h3 class="font-extrabold text-3xl text-gray-900">{{ formatRupiah(grandTotal) }}</h3>
            </div>

            <!-- Payment Form -->
            <form @submit.prevent="onSubmit" enctype="multipart/form-data" class="space-y-6">

                <!-- Payment Methods -->
                <div>
                    <h6 class="text-sm font-semibold mb-3">Metode Pembayaran</h6>
                    <div class="flex flex-col gap-2">
                        <Button type="button" :class="[
                            'flex items-center space-x-2 py-6 rounded-md w-full',
                            selectedPayment === 'cash' ? 'bg-gray-800' : 'bg-white border border-gray-300 text-gray-900 hover:text-white'
                        ]" @click="selectPaymentMethod('cash')">

                            <input id="cash" name="payment-method" type="radio" class="hidden"
                                :checked="selectedPayment === 'cash'">

                            <span class="material-icons mr-2 flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" width="2em" height="2em" viewBox="0 0 256 256">
                                    <path fill="currentColor"
                                        d="M244.24 60a8 8 0 0 0-7.75-.4c-42.93 21-73.59 11.16-106 .78c-34-10.89-69.25-22.14-117.95 1.64A8 8 0 0 0 8 69.24v119.93a8 8 0 0 0 11.51 7.19c42.93-21 73.59-11.16 106.05-.78c19.24 6.15 38.84 12.42 61 12.42c17.09 0 35.73-3.72 56.91-14.06a8 8 0 0 0 4.49-7.18V66.83a8 8 0 0 0-3.72-6.83M232 181.67c-40.6 18.17-70.25 8.69-101.56-1.32c-19.24-6.15-38.84-12.42-61-12.42a122 122 0 0 0-45.4 9V74.33c40.6-18.17 70.25-8.69 101.56 1.32S189.14 96 232 79.09ZM128 96a32 32 0 1 0 32 32a32 32 0 0 0-32-32m0 48a16 16 0 1 1 16-16a16 16 0 0 1-16 16M56 96v48a8 8 0 0 1-16 0V96a8 8 0 1 1 16 0m144 64v-48a8 8 0 1 1 16 0v48a8 8 0 1 1-16 0" />
                                </svg>
                                Pembayaran Tunai
                            </span>
                        </Button>

                        <Button type="button" :class="[
                            'flex items-center space-x-2 py-6 rounded-md w-full',
                            selectedPayment === 'qris' ? 'bg-gray-800' : 'bg-white border border-gray-300 text-gray-900 hover:text-white'
                        ]" @click="selectPaymentMethod('qris')">

                            <input id="qris" name="payment-method" type="radio" class="hidden"
                                :checked="selectedPayment === 'qris'">

                            <span class="material-icons mr-2 flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" width="1.8em" height="1.8em"
                                    viewBox="0 0 16 16">
                                    <g fill="currentColor">
                                        <path
                                            d="M0 .5A.5.5 0 0 1 .5 0h3a.5.5 0 0 1 0 1H1v2.5a.5.5 0 0 1-1 0zm12 0a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-1 0V1h-2.5a.5.5 0 0 1-.5-.5M.5 12a.5.5 0 0 1 .5.5V15h2.5a.5.5 0 0 1 0 1h-3a.5.5 0 0 1-.5-.5v-3a.5.5 0 0 1 .5-.5m15 0a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1 0-1H15v-2.5a.5.5 0 0 1 .5-.5M4 4h1v1H4z" />
                                        <path d="M7 2H2v5h5zM3 3h3v3H3zm2 8H4v1h1z" />
                                        <path d="M7 9H2v5h5zm-4 1h3v3H3zm8-6h1v1h-1z" />
                                        <path
                                            d="M9 2h5v5H9zm1 1v3h3V3zM8 8v2h1v1H8v1h2v-2h1v2h1v-1h2v-1h-3V8zm2 2H9V9h1zm4 2h-1v1h-2v1h3zm-4 2v-1H8v1z" />
                                        <path d="M12 9h2V8h-2z" />
                                    </g>
                                </svg>
                                Pembayaran Qris
                            </span>
                        </Button>

                        <Button type="button" :class="[
                            'flex items-center space-x-2 py-6 rounded-md w-full',
                            selectedPayment === 'debt' ? 'bg-gray-800' : 'bg-white border border-gray-300 text-gray-900 hover:text-white'
                        ]" @click="selectPaymentMethod('debt')">

                            <input id="debt" name="payment-method" type="radio" class="hidden"
                                :checked="selectedPayment === 'debt'">

                            <span class="material-icons mr-2 flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" width="1.8em" height="1.8em"
                                    viewBox="0 0 48 48">
                                    <path fill="none" stroke="currentColor" stroke-linecap="round"
                                        stroke-linejoin="round" stroke-width="4"
                                        d="M10 6a2 2 0 0 1 2-2h24a2 2 0 0 1 2 2v38l-7-5l-7 5l-7-5l-7 5zm8 16h12m-12 8h12M18 14h12" />
                                </svg>
                                Catat Hutang
                            </span>
                        </Button>
                    </div>
                </div>

                <div v-if="selectedPayment == 'cash'" class="space-y-4">
                    <FormInput name="bayar" label="Nominal Bayar" type="number" v-model="bayar" @input="hitungKembali"
                        placeholder="Masukkan jumlah pembayaran" class="text-lg" :row="false">
                        <p class="text-sm text-gray-600">Terbilang : <span v-if="bayar" class="font-bold">{{
                            useTerbilang(parseFloat(bayar)) }}</span></p>
                    </FormInput>

                    <FormInput name="kembali" label="" type="hidden" :disabled="true" v-model="kembali"
                        :value="formatRupiah(kembali)" class="text-lg font-semibold text-blue-600" />

                    <div class="bg-blue-50 p-6 rounded-lg shadow-inner" style="margin-top: 5px;">
                        <h5 class="text-sm font-semibold text-gray-600 mb-2">Nominal Kembali</h5>
                        <h5 :class="[{ 'font-extrabold text-xl': kembali > 0, 'font-bold text-xl': kembali <= 0 }]">
                            <span v-if="kembali > 0">{{ formatRupiah(kembali) }}</span>
                            <span v-else-if="kembali == 0">Uang pas</span>
                            <span v-else-if="kembali == null">Masukkan nominal pembayaran</span>
                            <span v-else class="text-red-500">Nominal pembayaran kurang</span>
                        </h5>
                        <p class="text-sm text-gray-600 mt-2">Terbilang :
                            <span v-if="kembali > 0" class="font-bold">{{ useTerbilang(kembali) }}</span>
                        </p>
                    </div>
                </div>

                <div v-if="selectedPayment == 'qris'" class="bg-red-100 p-6 rounded-lg shadow-inner flex gap-2 mt-5">
                    <svg xmlns="http://www.w3.org/2000/svg" width="4em" height="4em" viewBox="0 0 32 32">
                        <g fill="none">
                            <path fill="#ffb02e"
                                d="m14.839 5.668l-12.66 21.93c-.51.89.13 2.01 1.16 2.01h25.32c1.03 0 1.67-1.11 1.16-2.01l-12.66-21.93c-.52-.89-1.8-.89-2.32 0" />
                            <path fill="#000"
                                d="M14.599 21.498a1.4 1.4 0 1 0 2.8-.01v-9.16c0-.77-.62-1.4-1.4-1.4c-.77 0-1.4.62-1.4 1.4zm2.8 3.98a1.4 1.4 0 1 1-2.8 0a1.4 1.4 0 0 1 2.8 0" />
                        </g>
                    </svg>
                    <h5 class="font-bold text-md text-center">Cek mutasi dan pastikan uang sudah masuk sebelum
                        menyelesaikan pembayaran</h5>
                    <svg xmlns="http://www.w3.org/2000/svg" width="4em" height="4em" viewBox="0 0 32 32">
                        <g fill="none">
                            <path fill="#ffb02e"
                                d="m14.839 5.668l-12.66 21.93c-.51.89.13 2.01 1.16 2.01h25.32c1.03 0 1.67-1.11 1.16-2.01l-12.66-21.93c-.52-.89-1.8-.89-2.32 0" />
                            <path fill="#000"
                                d="M14.599 21.498a1.4 1.4 0 1 0 2.8-.01v-9.16c0-.77-.62-1.4-1.4-1.4c-.77 0-1.4.62-1.4 1.4zm2.8 3.98a1.4 1.4 0 1 1-2.8 0a1.4 1.4 0 0 1 2.8 0" />
                        </g>
                    </svg>
                </div>

                <div v-if="selectedPayment == 'debt'">
                    <p class="text-sm font-semibold">Nama Customer</p>
                    <Multiselect class="h-5 mt-2" v-model="selectedCustomer" :options="customer"
                        @update:modelValue="updateIdCustomer" :close-on-select="false" :preserve-search="true"
                        placeholder="Pilih customer" label="name" track-by="name" :preselect-first="false" />
                </div>

                <DialogFooter>
                    <Button type="submit" class="w-full py-3" :class="{ 'bg-gray-400': isLoading || !isFormValid }"
                        :disabled="isLoading || !isFormValid">
                        {{ isLoading ? 'Memproses Pembayaran...' : 'Bayar Sekarang' }}
                    </Button>
                </DialogFooter>
            </form>
        </div>
    </DialogWrapper>
</template>

<script setup>
import PosLayout from '@/Layouts/PosLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    }
});

function handleImageError() {
    document.getElementById('screenshot-container')?.classList.add('!hidden');
    document.getElementById('docs-card')?.classList.add('!row-span-1');
    document.getElementById('docs-card-content')?.classList.add('!flex-row');
    document.getElementById('background')?.classList.add('!hidden');
}

import { ref, computed, onMounted, onUnmounted, reactive } from 'vue';
import VueMultiselect, { Multiselect } from 'vue-multiselect';
import ProductTable from '@/Components/ProductTable.vue';
import OrderSummary from '@/Components/OrderSummary.vue';
import axios from 'axios';
import Input from '@/Components/ui/input/Input.vue';
import { Label } from '@/components/ui/label'
import {
    Sheet,
    SheetClose,
    SheetContent,
    SheetDescription,
    SheetFooter,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/Components/ui/sheet'
import Button from '@/Components/ui/button/Button.vue';
import Separator from '@/Components/ui/separator/Separator.vue';
import DialogWrapper from '@/Components/ui/dialog/DialogWrapper.vue';
import DialogFooter from '@/Components/ui/dialog/DialogFooter.vue';
import useSpeak from '@/Composables/useSpeak';
import { useToast } from '@/Composables/useToast';
import { add } from 'lodash';
import { get } from '@vueuse/core';
import useTerbilang from '@/Composables/useTerbilang';
import FormInput from '@/Components/ui/form/FormInput.vue';
import {
    Menubar,
    MenubarContent,
    MenubarItem,
    MenubarMenu,
    MenubarSeparator,
    MenubarShortcut,
    MenubarTrigger,
} from '@/Components/ui/menubar'
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/Components/ui/alert-dialog'

import { Link } from '@inertiajs/vue3';
import NavigationMenu from '@/Components/ui/navigation-menu/NavigationMenu.vue';
import NavigationMenuList from '@/Components/ui/navigation-menu/NavigationMenuList.vue';
import NavigationMenuItem from '@/Components/ui/navigation-menu/NavigationMenuItem.vue';
import NavigationMenuTrigger from '@/Components/ui/navigation-menu/NavigationMenuTrigger.vue';
import NavigationMenuContent from '@/Components/ui/navigation-menu/NavigationMenuContent.vue';
import NavigationMenuLink from '@/Components/ui/navigation-menu/NavigationMenuLink.vue';
import { navigationMenuTriggerStyle } from '@/Components/ui/navigation-menu';
import { FormField } from '@/Components/ui/form';
import FormItem from '@/Components/ui/form/FormItem.vue';
import FormLabel from '@/Components/ui/form/FormLabel.vue';
import FormMessage from '@/Components/ui/form/FormMessage.vue';
import Select from '@/Components/ui/select/Select.vue';
import FormControl from '@/Components/ui/form/FormControl.vue';
import SelectTrigger from '@/Components/ui/select/SelectTrigger.vue';
import SelectValue from '@/Components/ui/select/SelectValue.vue';
import SelectContent from '@/Components/ui/select/SelectContent.vue';
import SelectGroup from '@/Components/ui/select/SelectGroup.vue';
import SelectItem from '@/Components/ui/select/SelectItem.vue';
import PosLayoutMobile from '@/Layouts/PosLayoutMobile.vue';
import ProductTableMobile from '@/Components/ProductTableMobile.vue';
// import useTerbilang from '@/Composables/useTerbilang';

const isAddModalOpen = ref(false);

const Toast = useToast();
const dialogRevokeButton = ref(null);
const isRevokeModalOpen = ref(false);
const cancelButton = ref(null);

const identifierInput = ref('');
const selectedProduct = ref(null);
const products = ref([]);
const cartItems = ref([]);
const transactionCode = ref('');


const identifierInputRef = ref(null);
const productSelectRef = ref(null);
const updatingItemId = ref(null);

const isLoading = ref(false);
const currentFocusIndex = ref(-1);
const productTableRef = ref(null);

const isHelperOpen = ref(false);
const isPaymentModalOpen = ref(false);

const searchingProduct = ref(null);

const openRevokeModal = () => {
    isRevokeModalOpen.value = true;
};

const isChangeModalOpen = ref(false);

onMounted(() => {
    isLoading.value = true;
    timer = setInterval(updateDateTime, 1000);
    window.addEventListener('keydown', handleGlobalKeydown);

    fetchCart();
    fetchCustomer();
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleGlobalKeydown);
    clearInterval(timer);
});

const speakTotal = () => {
    useSpeak('Total belanja, ' + formatRupiah(grandTotal.value));
};

const revokeTransaction = () => {
    isLoading.value = true;

    axios.post('/pos/carts/revoke', {
        transaction_code: transactionCode.value
    }).then(response => {
        if (response.data && response.data.data) {
            cartItems.value = response.data.data;
        }

        fetchCart();
        isRevokeModalOpen.value = false;

        isLoading.value = false;
        generateTransactionCode();

        useSpeak('Transaksi dibatalkan');
    }).catch(error => {
        console.error('Error revoking transaction:', error);
    });

};

const grandTotal = ref(0);
const cart = ref([]);

const fetchCart = async () => {
    try {
        isLoading.value = true;
        const response = await axios.get('/pos/carts/getUserCart');
        cartItems.value = response.data.data;
        grandTotal.value = response.data.grand_total > 0 ? response.data.grand_total : 0;
        cart.value = response.data.cart;

        if (cartItems.value.length > 0) {
            transactionCode.value = response.data.transaction_code;
        } else {
            transactionCode.value = generateTransactionCode();
        }
    } catch (error) {
        console.error('Error fetching user cart:', error);
    } finally {
        isLoading.value = false;
    }
};

const customer = ref([]);
const selectedCustomer = ref(null);

const fetchCustomer = async () => {
    try {
        isLoading.value = true;
        const response = await axios.get('/api/customers');
        console.log('response', response.data);
        customer.value = response.data.data;
    } catch (error) {
        console.error('Error fetching customer:', error);
    } finally {
        isLoading.value = false;
    }
};

const updateIdCustomer = (value) => {
    selectedCustomer.value = value;

    console.log('selectedCustomer', selectedCustomer.value);
};

const generateTransactionCode = () => {
    const now = () => {
        const date = new Date();
        return date.getFullYear().toString().slice(-2) +
            date.getDate().toString().padStart(2, '0') +
            (date.getMonth() + 1).toString().padStart(2, '0') +
            date.getDate().toString().padStart(2, '0') +
            date.getHours().toString().padStart(2, '0') +
            date.getMinutes().toString().padStart(2, '0');
    };

    return "TRX" + now() + "-" + Math.random().toString(36).substr(2, 5).toUpperCase();
};

const successAudio = new Audio('/success.mp3');
const errorAudio = new Audio('/error.mp3');

// Preload the audio files
successAudio.load();
errorAudio.load();

const updateVariant = (itemId, variantId) => {
    const item = cartItems.value.find(item => item.id === itemId);

    if (item) {
        const variant = item.product_variants.find(variant => variant.id === parseInt(variantId));

        console.log('variant', variant);

        if (variant) {
            // Optimistic update
            const existingItem = cartItems.value.find(
                i => i.product_id === item.product_id &&
                    i.product_variant_id === parseInt(variantId) &&
                    i.id !== itemId
            );

            console.log('cartItems', cartItems.value);

            console.log('existingItem', existingItem);

            if (existingItem) {
                existingItem.quantity += item.quantity;
                existingItem.total_price = existingItem.quantity * variant.price;

                cartItems.value = cartItems.value.filter(i => i.id !== itemId);
            } else {
                item.product_variant_id = parseInt(variantId);
                item.price = variant.price;
                item.total_price = item.quantity * variant.price;
            }
        }
    }

    // Optimistic update
    updatingItemId.value = itemId;

    axios.post('/pos/carts/updateVariant', {
        id: itemId,
        variant_id: variantId,
        transaction_code: transactionCode.value
    }).then(response => {
        if (response.data && response.data.data) {
            cartItems.value = response.data.data;
            grandTotal.value = response.data.grand_total;
            cart.value = response.data.cart;

            console.log('cartItems', cartItems.value);
        }
    }).catch(error => {
        console.error('Error updating variant:', error);
        fetchCartItems();
        // You might want to show an error message to the user here
    }).finally(() => {
        updatingItemId.value = null;
    });
};

const updateQuantity = (id, quantity) => {
    const item = cartItems.value.find(item => item.id === id);
    if (item) {
        item.quantity = quantity;
    }

    // Optimistic update
    updatingItemId.value = id;

    if (quantity === 0) {
        removeItem(id);
        return;
    } else {
        axios.post('/pos/carts/updateProduct', {
            id: id,
            quantity: quantity,
            transaction_code: transactionCode.value
        }).then(response => {
            grandTotal.value = response.data.grand_total;
            cartItems.value = response.data.data.original.data;
            cart.value = response.data.cart;
            console.log('cartItems', cartItems.value);
        }).catch(error => {
            console.error('Error updating quantity:', error);
            // Revert the optimistic update
            item.quantity = item.quantity - quantity;
            // You might want to show an error message to the user here
        }).finally(() => {
            updatingItemId.value = null;
        });
    }
};

const removeItem = (id) => {
    cartItems.value = cartItems.value.filter(item => item.id !== id);

    axios.post('/pos/carts/removeProduct', {
        id: id,
        transaction_code: transactionCode.value
    }).then(response => {
        if (response.data && response.data.data) {
            cartItems.value = response.data.data;
        }

        if (cartItems.value.length === 0) {
            revokeTransaction();
            transactionCode.value = generateTransactionCode();
        }
    }).catch(error => {
        console.error('Error removing item:', error);
        // You might want to show an error message to the user here
    });
};

const handleGlobalKeydown = (event) => {
    const isFocusedOnInput = () => {
        const activeElement = document.activeElement;
        return activeElement.tagName === 'INPUT' || activeElement.tagName === 'TEXTAREA';
    };

    if (event.key === '1' && !isFocusedOnInput() && document.activeElement !== identifierInputRef.value.$el) {
        event.preventDefault();
        identifierInputRef.value.$el.focus();
    }

    if (event.key === '2' && !isFocusedOnInput() && document.activeElement !== productSelectRef.value.$el) {
        event.preventDefault();
        productSelectRef.value.$el.querySelector('input').focus();
    }

    if (event.code === 'ShiftRight' && document.activeElement === identifierInputRef.value.$el) {
        identifierInputRef.value.$el.blur();
        identifierInput.value = '';
    } else if (event.code === 'ShiftRight' && document.activeElement === productSelectRef.value.$el.querySelector('input')) {
        productSelectRef.value.$el.querySelector('input').blur();
        selectedProduct.value = null;
    }

    if (event.key === 'b' && !isFocusedOnInput() && isHelperOpen.value) {
        event.preventDefault();
        isHelperOpen.value = !isHelperOpen.value;
    } else if (event.key === 'b' && !isFocusedOnInput() && !isHelperOpen.value) {
        event.preventDefault();
        isHelperOpen.value = !isHelperOpen.value;
    }

    const focusNextQuantityInput = () => {
        if (productTableRef.value) {
            productTableRef.value.focusNextQuantityInput();
        }
    };

    const focusPreviousQuantityInput = () => {
        if (productTableRef.value) {
            productTableRef.value.focusPreviousQuantityInput();
        }
    };

    const focusQuantityInput = (index) => {
        // This function will be implemented in the ProductTable component
        // We'll emit an event to the parent component to handle the focus
    };

    // Handle arrow down key for quantity input navigation
    if (event.key === 'k' && !isFocusedOnInput() && productTableRef.value) {
        event.preventDefault();

        focusNextQuantityInput();
    } else if (event.key === 'Shift') {
        // Menghilangkan fokus dari elemen yang sedang aktif
        if (document.activeElement instanceof HTMLElement) {
            document.activeElement.blur();
        }
    }
};

const selectedItem = ref(null);

const handleSelect = (product) => {
    selectedItem.value = product;

    addToCart(selectedItem.value.identifier, selectedItem.value.variant_id);
    isAddModalOpen.value = false;
};

const addProduct = async () => {
    if (identifierInput.value) {
        addToCart(identifierInput.value);

        identifierInput.value = '';
    }
};

const newlyAddedItemId = ref(null);

const addToCart = async (identifier, variant_id = 0) => {
    console.log('cartItems', cartItems.value);
    const item = cartItems.value.find(item => item.name === identifier && item.product_variant_id === variant_id || item.sku === identifier && item.product_variant_id === variant_id);

    try {
        newlyAddedItemId.value = item ? item.id : null;
        const response = await axios.post('/pos/carts/addItem', {
            identifier: identifier,
            transaction_code: transactionCode.value,
            variant_id: variant_id
        });

        if (response.data && response.data.data) {
            console.log('response', response.data.data);

            cartItems.value = response.data.data;
            grandTotal.value = response.data.grand_total;
            cart.value = response.data.cart;
        } else if (response.data && response.data.product.length > 0) {
            console.log('response', response.data.product);
            searchingProduct.value = response.data.product;
            isAddModalOpen.value = true;
        } else {
            errorAudio.play();
            useSpeak('Produk tidak ditemukan');

            Toast.fire({
                icon: "error",
                title: 'Produk tidak ditemukan',
                position: 'bottom-right',
            });
        }
    } catch (error) {
        console.error('Error adding product to cart:', error);
    } finally {
        newlyAddedItemId.value = null;
    }
};

function formatRupiah(value) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value);
}

const openPaymentModal = () => {
    isPaymentModalOpen.value = true;
    console.log('Grand Total:', grandTotal.value);

    useTerbilang(grandTotal.value);
};

const currentDay = ref('')
const currentDate = ref('')
const currentTime = ref('')

const updateDateTime = () => {
    const now = new Date()
    const options = { timeZone: 'Asia/Jakarta' }

    currentDay.value = now.toLocaleDateString('id-ID', { ...options, weekday: 'long' })
    currentDate.value = now.toLocaleDateString('id-ID', {
        ...options,
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    })
    const timeString = now.toLocaleTimeString('id-ID', {
        ...options,
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false,
        hourCycle: 'h23'
    });

    currentTime.value = timeString.replace(/\./g, ':');
}

let timer

const selectedPayment = ref(null);

const selectPaymentMethod = (method) => {
    selectedPayment.value = method;

    if (selectedPayment.value !== 'cash') {
        bayar.value = grandTotal.value;
    } else {
        bayar.value = '';
    }
};

const bayar = ref('');
const kembali = ref(null);
const computedBayarValue = computed(() => bayar.value);
console.log('computedBayarValue:', computedBayarValue.value);

const hitungKembali = () => {
    const bayarValue = computedBayarValue.value;
    kembali.value = bayarValue - grandTotal.value;
    console.log('Kembali:', kembali.value);
};

const isFormValid = computed(() => {
    return bayar.value >= grandTotal.value;
});

const onSubmit = async () => {
    if (!isFormValid.value) return;

    isLoading.value = true;
    try {
        const response = await axios.post('/pos/carts/store-transaction', {
            transaction_code: transactionCode.value,
            payment_method: selectedPayment.value,
            total_payment: bayar.value,
            customer_id: selectedCustomer.value ? selectedCustomer.value.id : null,
        });

        if (response.data) {
            fetchCart();
            useSpeak('Transaksi berhasil');

            Toast.fire({
                icon: "success",
                title: 'Transaksi berhasil',
                position: 'bottom-right',
            });

            if (selectedPayment.value === 'cash') {
                isChangeModalOpen.value = true;
                useSpeak('Kembali, ' + useTerbilang(kembali.value));
            }

            bayar.value = '';
            computedBayarValue.value = '';
            selectedPayment.value = null;
        }

        isPaymentModalOpen.value = false;
    } catch (error) {
        console.error('Payment processing failed', error);
    } finally {
        isLoading.value = false;
    }
};

const isDiscountModalOpen = ref(false);

const openDiscountModal = () => {
    isDiscountModalOpen.value = true;
};
</script>

<style scope src="vue-multiselect/dist/vue-multiselect.css"></style>

<style scoped>
tr:focus {
    outline: 2px solid #4299e1;
    outline-offset: -2px;
    background-color: #b9defd;
}

tr:hover {
    outline: 2px solid #4299e1;
    outline-offset: -2px;
}
</style>
