<template>
    <PosLayoutMobile>
        <div class="flex flex-col min-h-screen">
            <div class="flex-grow">
                <div class="sticky top-0 bg-white z-20">
                    <div class="bg-gray-100 w-full py-5 px-5">
                        <div class="w-full space-y-2">
                            <div class="flex items-center space-x-2 justify-center text-center">
                                <span class="text-gray-600 font-semibold text-sm">Kode Pembayaran:</span>
                                <h3 class="text-md font-bold text-gray-900">{{ paymentCode }}</h3>
                            </div>
                            <p class="text-sm text-center text-gray-500">
                                {{ currentDay }}, {{ currentDate }} - {{ currentTime }}
                            </p>
                        </div>

                        <Input autofocus ref="identifierInputRef" v-model="debtorInput" @keyup.enter="handleAddDebtor"
                            placeholder="Cari nama pelanggan"
                            class="mt-5 w-full px-4 h-16 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" />
                    </div>
                </div>

                <div class="w-full">
                    <div class="p-6">
                        <div v-if="selectedCustomer">
                            <h1 class="text-xl font-bold text-gray-900">Informasi Terhutang</h1>

                            <div class="space-y-2 mt-4">
                                <h5 class="font-semibold text-gray-700 text-sm">
                                    <span class="font-bold">Nama Terhutang:</span> {{ selectedCustomer.name }}
                                </h5>
                                <h5 class="font-semibold text-gray-700 text-sm">
                                    <span class="font-bold">Nomor Telepon Terhutang:</span> {{ selectedCustomer.phone }}
                                </h5>
                                <h5 class="font-semibold text-gray-700 text-sm">
                                    <span class="font-bold">Alamat Terhutang:</span> {{ selectedCustomer.address }}
                                </h5>
                            </div>
                        </div>

                        <!-- Membuat tabel menjadi responsif -->
                        <div class="w-full overflow-x-auto mt-5 bg-white border rounded-lg">
                            <table class="min-w-full bg-white">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="text-sm py-2 px-4 text-left whitespace-nowrap">Kode Transaksi</th>
                                        <th class="text-sm py-2 px-4 text-left whitespace-nowrap">Tanggal Transaksi</th>
                                        <th class="text-sm py-2 px-4 text-left whitespace-nowrap">Nama Produk</th>
                                        <th class="text-sm py-2 px-4 text-left whitespace-nowrap">Variasi</th>
                                        <th class="text-sm py-2 px-4 text-left whitespace-nowrap">Harga</th>
                                        <th class="text-sm py-2 px-4 text-left whitespace-nowrap">Kuantitas</th>
                                        <th class="text-sm py-2 px-4 text-left whitespace-nowrap">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(item, index) in debtItems" :key="item.id" class="border-t">
                                        <td class="text-sm py-2 px-4">{{ item.transaction_code }}</td>
                                        <td class="text-sm py-2 px-4">{{ new Date(item.created_at).toLocaleDateString('id-ID', {
                                            day:
                                                'numeric', month: 'long', year: 'numeric'
                                            }) }}</td>
                                        <td class="text-sm py-2 px-4">{{ item.product ? item.product.name : 'TAX' }}</td>
                                        <td class="text-sm py-2 px-4">{{ item.variant ? item.variant : 'TAX' }}</td>
                                        <td class="text-sm py-2 px-4">{{ formatRupiah(item.price) }}</td>
                                        <td class="text-sm py-2 px-4">{{ item.quantity }}</td>
                                        <td class="text-sm py-2 px-4">{{ formatRupiah(item.price * item.quantity) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-gray-100 w-full fixed z-10 bottom-0 left-0 pt-5 px-5">
                <div class="w-full space-y-2 mt-30">
                    <div class="flex items-center space-x-2 justify-center text-center mb-5">
                        <span class="text-gray-600 font-semibold text-sm">Total:</span>
                        <h3 class="text-lg font-bold text-gray-900">{{ formatRupiah(totalDebt) }}</h3>
                    </div>
                </div>

                <div class="flex gap-2 items-center mb-5">
                    <Button v-if="selectedCustomer" @click="openRevokeModal"
                        class="w-1/2 h-14 bg-red-500 hover:bg-red-600">
                        Batalkan
                    </Button>

                    <Button v-if="selectedCustomer" @click="openPaymentModal"
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
                <h5 class="text-sm font-semibold text-gray-600 mb-2">Total Hutang</h5>
                <h3 class="font-extrabold text-3xl text-gray-900">{{ formatRupiah(totalDebt) }}</h3>
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
                                Tunai
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
                                Qris
                            </span>
                        </Button>
                    </div>
                </div>

                <div class="space-y-4">
                    <div>
                        <Label class="text-sm font-semibold">Nominal Bayar</Label>
                        <Input name="paymentAmount" label="Nominal Bayar" type="number" v-model="paymentAmount"
                            @input="countDebtRemaining" placeholder="Masukkan jumlah pembayaran" class="my-2"
                            :row="false">
                        </Input>
                        <p class="text-sm text-gray-600">Terbilang : <span v-if="paymentAmount" class="font-bold">{{
                            useTerbilang(parseFloat(paymentAmount)) }}</span></p>
                    </div>

                    <Input name="debtRemaining" label="" type="hidden" :disabled="true" v-model="debtRemaining"
                        :value="formatRupiah(debtRemaining)" class="font-semibold text-blue-600" />

                    <div class="bg-blue-50 p-6 rounded-lg shadow-inner" style="margin-top: 25px;">
                        <h5 class="text-sm font-semibold text-gray-600 mb-2">Sisa Hutang Setelah Dibayar</h5>
                        <h5
                            :class="[{ 'font-extrabold text-3xl': debtRemaining > 0, 'font-bold text-2xl': debtRemaining <= 0 }]">
                            <span v-if="debtRemaining > 0">{{ formatRupiah(debtRemaining) }}</span>
                            <span v-else-if="debtRemaining == 0">Uang pas</span>
                            <span v-else-if="debtRemaining == null">Masukkan nominal bayar</span>
                            <span v-else class="text-red-500">Nominal bayar melebihi hutang</span>
                        </h5>
                        <p class="text-sm text-gray-600 mt-2">Terbilang :
                            <span v-if="debtRemaining > 0" class="font-bold">{{ useTerbilang(debtRemaining) }}</span>
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
                    <h5 class="font-bold text-md 2xl:text-xl text-center">Cek mutasi dan pastikan uang sudah masuk
                        sebelum
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

                <DialogFooter>
                    <Button type="submit" class="w-full py-3" :disabled="paymentAmount <= 0 || !selectedPayment">
                        {{ isLoading ? 'Memproses Pembayaran...' : 'Bayar Sekarang' }}
                    </Button>
                </DialogFooter>
            </form>
        </div>
    </DialogWrapper>

    <DialogWrapper v-model:open="isCustomerModalOpen" title="Pilih Produk" desc="" custom-class="md:w-[70rem]">
        <div class="space-y-4">
            <table class="w-full border-collapse">
                <thead>
                    <tr>
                        <th class="text-left p-2 border-b">SKU</th>
                        <th class="text-left p-2 border-b">Nomor Telepon</th>
                        <th class="text-left p-2 border-b">Alamat</th>
                        <th class="text-left p-2 border-b">Total Hutang</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="customer in searchingCustomer" :key="customer.id" @click="handleSelect(customer)" :class="{
                        'bg-blue-100': selectedCustomer?.id === customer.id,
                        'hover:bg-gray-100': selectedCustomer?.id !== customer.id
                    }" class="cursor-pointer transition-colors duration-150 ease-in-out" tabindex="0"
                        @keydown.enter="handleSelect(customer)" @keydown.space.prevent="handleSelect(customer)">
                        <td class="p-2 border-b">{{ customer.name }}</td>
                        <td class="p-2 border-b">{{ customer.phone }}</td>
                        <td class="p-2 border-b">{{ customer.address }}</td>
                        <td class="p-2 border-b">{{ totalDebt }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </DialogWrapper>
</template>

<script setup>
import Button from '@/components/ui/button/Button.vue';
import DialogWrapper from '@/Components/ui/dialog/DialogWrapper.vue';
import Input from '@/Components/ui/input/Input.vue';
import PosLayout from '@/Layouts/PosLayout.vue';
import axios from 'axios';
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useToast } from '@/Composables/useToast';
import useTerbilang from '@/Composables/useTerbilang';
import Separator from '@/Components/ui/separator/Separator.vue';
import DebtItems from '../Admin/DebtItems.vue';
import DialogFooter from '@/Components/ui/dialog/DialogFooter.vue';
import FormInput from '@/Components/ui/form/FormInput.vue';
import useSpeak from '@/Composables/useSpeak';
import PosLayoutMobile from '@/Layouts/PosLayoutMobile.vue';

const paymentCode = ref('PH-001') // Kode pembayaran default
const debtorInput = ref('') // Input untuk mencari nama pelanggan
const totalDebt = ref(0) // Total hutang pelanggan
const paymentAmount = ref(0) // Jumlah pembayaran yang diinput
const debtItems = ref([]) // Daftar item hutang

const currentDay = ref('') // Hari saat ini
const currentDate = ref('') // Tanggal saat ini
const currentTime = ref('') // Waktu saat ini

const isCustomerModalOpen = ref(false) // Status modal pencarian pelanggan
const searchingCustomer = ref([]) // Daftar pelanggan yang dicari
const selectedCustomer = ref(null) // Pelanggan yang dipilih

const isPaymentModalOpen = ref(false) // Status modal pembayaran
const isRevokeModalOpen = ref(false) // Status modal pembatalan

const selectedPayment = ref(null); // Metode pembayaran yang dipilih
const isLoading = ref(false) // Status loading

const openPaymentModal = () => {
    isPaymentModalOpen.value = true; // Membuka modal pembayaran
}

const openRevokeModal = () => {
    isRevokeModalOpen.value = true; // Membuka modal pembatalan
}

const selectPaymentMethod = (method) => {
    selectedPayment.value = method; // Memilih metode pembayaran
};

const computedPaymentAmount = computed(() => paymentAmount.value); // Menghitung jumlah pembayaran
const debtRemaining = ref(null); // Sisa hutang setelah pembayaran

const countDebtRemaining = () => {
    const paymentAmount = computedPaymentAmount.value;

    debtRemaining.value = totalDebt.value - paymentAmount; // Menghitung sisa hutang
    console.log('DebtRemaining:', debtRemaining.value);
};

const onSubmit = async () => {
    isLoading.value = true; // Mengaktifkan status loading

    try {
        const response = await axios.post('/pos/debt-payments/store-payment', {
            payment_code: paymentCode.value,
            payment_amount: paymentAmount.value,
            payment_method: selectedPayment.value,
            customer_id: selectedCustomer.value.id,
        });

        if (response.data) {
            useSpeak('Transaksi berhasil'); // Menggunakan suara untuk notifikasi

            Toast.fire({
                icon: "success",
                title: 'Transaksi berhasil',
                position: 'bottom-right',
            });

            // Reset nilai setelah pembayaran berhasil
            paymentAmount.value = '';
            computedPaymentAmount.value = '';
            selectedPayment.value = null;

            selectedCustomer.value = null;
            debtItems.value = [];
            debtorInput.value = '';
            totalDebt.value = 0;
        }

        isPaymentModalOpen.value = false; // Menutup modal pembayaran
        generateCode(); // Mengenerate kode pembayaran baru
    } catch (error) {
        console.error('Payment processing failed', error); // Menangani error
    } finally {
        isLoading.value = false; // Menonaktifkan status loading
    }
};

const revokeTransaction = () => {
    // Reset nilai setelah transaksi dibatalkan
    paymentAmount.value = '';
    computedPaymentAmount.value = '';
    selectedPayment.value = null;

    selectedCustomer.value = null;
    debtItems.value = [];
    debtorInput.value = '';
    totalDebt.value = 0;

    useSpeak('Transaksi dibatalkan'); // Menggunakan suara untuk notifikasi
}

const Toast = useToast(); // Menggunakan toast untuk notifikasi

const updateDateTime = () => {
    const now = new Date()
    const options = { timeZone: 'Asia/Jakarta' }

    currentDay.value = now.toLocaleDateString('id-ID', { ...options, weekday: 'long' }) // Mengupdate hari
    currentDate.value = now.toLocaleDateString('id-ID', {
        ...options,
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    }) // Mengupdate tanggal
    const timeString = now.toLocaleTimeString('id-ID', {
        ...options,
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false,
        hourCycle: 'h23'
    });

    currentTime.value = timeString.replace(/\./g, ':'); // Mengupdate waktu
}

let timer

const generateCode = () => {
    const now = () => {
        const date = new Date();
        return date.getFullYear().toString().slice(-2) +
            date.getDate().toString().padStart(2, '0') +
            (date.getMonth() + 1).toString().padStart(2, '0') +
            date.getDate().toString().padStart(2, '0') +
            date.getHours().toString().padStart(2, '0') +
            date.getMinutes().toString().padStart(2, '0');
    };

    return "PH" + now() + "-" + Math.random().toString(36).substr(2, 5).toUpperCase(); // Mengenerate kode pembayaran
};

const successAudio = new Audio('/success.mp3'); // Audio untuk notifikasi sukses
const errorAudio = new Audio('/error.mp3'); // Audio untuk notifikasi error

// Preload the audio files
successAudio.load();
errorAudio.load();

const handleAddDebtor = async () => {
    const response = await axios.post('/customers/getCustomer', {
        name: debtorInput.value
    })

    if (response.data.data.length > 0) {
        isCustomerModalOpen.value = true; // Membuka modal pencarian pelanggan
        searchingCustomer.value = response.data.data; // Menampilkan hasil pencarian pelanggan
    } else {
        errorAudio.play(); // Memutar audio error

        Toast.fire({
            icon: "error",
            title: 'Pelanggan tidak ditemukan',
            position: 'bottom-right',
        });
    }
}

const handleSelect = (customer) => {
    selectedCustomer.value = customer; // Memilih pelanggan
    isCustomerModalOpen.value = false; // Menutup modal pencarian pelanggan
    console.log('111:', customer.debts);
    debtItems.value = customer.debts.flatMap(debt => {
        return debt.debt_items.map(item => {
            if (!item.transaction_item) {
                return {
                    quantity: 1,
                    transaction_code: item.transaction_code,
                    created_at: item.created_at,
                    variant: `TAX`,
                    price: item.total_amount,
                };
            }

            return {
                ...item.transaction_item.product_variant,
                quantity: item.transaction_item.quantity,
                transaction_code: item.transaction_item.transaction.transaction_code,
                created_at: item.transaction_item.transaction.created_at,
                variant: `${item.transaction_item.product_variant.quantity}  ${item.transaction_item.product_variant.unit.name}`,
            };
        });
    });

    totalDebt.value = customer.debts.reduce((acc, debt) => {
        return acc + debt.remaining_amount;
    }, 0); // Menghitung total hutang

    debtorInput.value = ''; // Reset input pencarian pelanggan

    console.log('total_debt', totalDebt.value);
    console.log("Selected customer:", selectedCustomer.value)

    console.log("Debt items:", debtItems.value)
};

const handlePayment = () => {
    // Logika untuk memproses pembayaran hutang
    console.log("Processing payment of:", paymentAmount.value)
}

function formatRupiah(value) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value); // Format nilai ke dalam Rupiah
}

onMounted(() => {
    timer = setInterval(updateDateTime, 1000); // Mengupdate waktu setiap detik
    paymentCode.value = generateCode() // Mengenerate kode pembayaran saat komponen dimount
})

onUnmounted(() => {
    clearInterval(timer); // Menghentikan timer saat komponen diunmount
});
</script>