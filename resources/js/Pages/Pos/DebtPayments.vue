<template>
    <PosLayout>
        <div class="flex justify-between items-center p-6 shadow-inner bg-white fixed top-10 z-10 w-full">
            <div class="w-full space-y-2">
                <div class="flex items-center space-x-2">
                    <span class="text-gray-600 font-semibold">Kode Pembayaran Hutang:</span>
                    <h3 class="text-2xl font-bold text-gray-900">{{ paymentCode }}</h3>
                </div>
                <p class="text-sm text-gray-500">{{ currentDay }}, {{ currentDate }} - {{ currentTime }}</p>
            </div>

            <div class="w-full">
                <div class="bg-blue-100 p-5 rounded-lg shadow-inner border border-gray-300 text-end">
                    <h2 class="text-5xl font-extrabold">{{ formatRupiah(totalDebt) }}</h2>
                    <p class="text-sm text-gray-600 mt-2">
                        <span v-if="totalDebt > 0" class="text-md text-gray-500 font-medium">{{
                            useTerbilang(parseFloat(totalDebt)) }}</span>
                    </p>
                </div>
            </div>
        </div>

        <div class="flex gap-10 bg-gray-100 w-full p-6 z-10 top-52 fixed ">
            <div class="w-1/3 flex gap-2 items-center">
                <Input autofocus ref="identifierInputRef" v-model="debtorInput" @keyup.enter="handleAddDebtor"
                    placeholder="Cari nama pelanggan"
                    class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" />
                <Button class="bg-blue-500 hover:bg-blue-600">
                    <img width="30" height="30"
                        src="https://img.icons8.com/?size=100&id=LnB6MFtcppUH&format=png&color=000000" alt="payment" />
                </Button>
            </div>
            <div v-if="selectedCustomer" class="flex items-center w-full justify-end gap-4">
                <Button @click="openRevokeModal" class="bg-red-500 hover:bg-red-700 flex gap-1">
                    <img width="20" height="20"
                        src="https://img.icons8.com/?size=100&id=S7l4pxBr45nJ&format=png&color=000000" alt="gear" />
                    Batalkan Transaksi
                </Button>
                <Separator orientation="vertical" class="h-5 w-[2px] bg-gray-300" />
                <Button @click="openPaymentModal"
                    class="bg-blue-500 hover:bg-blue-600 flex gap-2 px-4 py-2 rounded text-white">
                    <img width="20" height="20" src="https://img.icons8.com/?size=100&id=13016&format=png&color=000000"
                        alt="gear" />
                    Proses Pembayaran
                </Button>
            </div>
        </div>

        <div class="w-full pt-56">
            <div class="mt-16 p-6">
                <div v-if="selectedCustomer">
                    <h1 class="text-xl font-bold text-gray-900">Informasi Terhutang</h1>

                    <div class="space-y-2 mt-4">
                        <h5 class="font-semibold text-gray-700"><span class="font-bold">Nama Terhutang:</span>
                            {{ selectedCustomer.name }}
                        </h5>
                        <h5 class="font-semibold text-gray-700"><span class="font-bold">Nomor Telepon Terhutang:</span>
                            {{ selectedCustomer.phone }}
                        </h5>
                        <h5 class="font-semibold text-gray-700"><span class="font-bold">Alamat Terhutang:</span> {{
                            selectedCustomer.address }}
                        </h5>
                    </div>
                </div>

                <table class="mt-5 min-w-full bg-white border rounded-lg overflow-hidden">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="py-2 px-4 text-left">Kode Transaksi</th>
                            <th class="py-2 px-4 text-left">Tanggal Transaksi</th>
                            <th class="py-2 px-4 text-left">Nama Produk</th>
                            <th class="py-2 px-4 text-left">Variasi</th>
                            <th class="py-2 px-4 text-left">Harga</th>
                            <th class="py-2 px-4 text-left">Kuantitas</th>
                            <th class="py-2 px-4 text-left">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(item, index) in debtItems" :key="item.id" class="border-t">
                            <td class="py-2 px-4">{{ item.transaction_code }}</td>
                            <td class="py-2 px-4">{{ new Date(item.created_at).toLocaleDateString('id-ID', {
                                day:
                                    'numeric', month: 'long', year: 'numeric'
                            }) }}</td>
                            <td class="py-2 px-4">{{ item.product ? item.product.name : 'TAX'}}</td>
                            <td class="py-2 px-4">{{ item.variant ? item.variant : 'TAX'}}</td>
                            <td class="py-2 px-4">{{ formatRupiah(item.price) }}</td>
                            <td class="py-2 px-4">{{ item.quantity }}</td>
                            <td class="py-2 px-4">{{ formatRupiah(item.price * item.quantity) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </PosLayout>

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
                    <div class="flex gap-2">
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
                    </div>
                </div>

                <div class="space-y-4">
                    <div>
                        <Label class="text-sm font-semibold">Nominal Bayar</Label>
                        <Input name="paymentAmount" label="Nominal Bayar" type="number" v-model="paymentAmount"
                            @input="countDebtRemaining" placeholder="Masukkan jumlah pembayaran" class="text-lg my-2"
                            :row="false">
                        </Input>
                        <p class="text-sm text-gray-600">Terbilang : <span v-if="paymentAmount" class="font-bold">{{
                            useTerbilang(parseFloat(paymentAmount)) }}</span></p>
                    </div>

                    <Input name="debtRemaining" label="" type="hidden" :disabled="true" v-model="debtRemaining"
                        :value="formatRupiah(debtRemaining)" class="text-lg font-semibold text-blue-600" />

                    <div class="bg-blue-50 p-6 rounded-lg shadow-inner" style="margin-top: 25px;">
                        <h5 class="text-sm font-semibold text-gray-600 mb-2">Sisa Hutang Setelah Dibayar</h5>
                        <h5
                            :class="[{ 'font-extrabold text-3xl': debtRemaining > 0, 'font-bold text-3xl': debtRemaining <= 0 }]">
                            <span v-if="debtRemaining > 0">{{ formatRupiah(debtRemaining) }}</span>
                            <span v-else-if="debtRemaining == 0">Uang pas</span>
                            <span v-else-if="debtRemaining == null">Masukkan nominal pembayaran</span>
                            <span v-else class="text-red-500">Nominal pembayaran kurang</span>
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
                    <h5 class="font-bold text-xl text-center">Cek mutasi dan pastikan uang sudah masuk sebelum
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

const paymentCode = ref('PH-001')
const debtorInput = ref('')
const totalDebt = ref(0)
const paymentAmount = ref(0)
const debtItems = ref([])

const currentDay = ref('')
const currentDate = ref('')
const currentTime = ref('')

const isCustomerModalOpen = ref(false)
const searchingCustomer = ref([])
const selectedCustomer = ref(null)

const isPaymentModalOpen = ref(false)
const isRevokeModalOpen = ref(false)

const selectedPayment = ref(null);
const isLoading = ref(false)

const openPaymentModal = () => {
    isPaymentModalOpen.value = true;
}

const openRevokeModal = () => {
    isRevokeModalOpen.value = true;
}

const selectPaymentMethod = (method) => {
    selectedPayment.value = method;
};

const computedPaymentAmount = computed(() => paymentAmount.value);
const debtRemaining = ref(null);

const countDebtRemaining = () => {
    const paymentAmount = computedPaymentAmount.value;

    debtRemaining.value = totalDebt.value - paymentAmount;
    console.log('DebtRemaining:', debtRemaining.value);
};

const onSubmit = async () => {
    isLoading.value = true;

    // console.log('paymentCode:', paymentCode.value);
    // console.log('paymentAmount:', paymentAmount.value);
    // console.log('selectedPayment:', selectedPayment.value);
    // console.log('selectedCustomer:', selectedCustomer.value);

    try {
        const response = await axios.post('/pos/debt-payments/store-payment', {
            payment_code: paymentCode.value,
            payment_amount: paymentAmount.value,
            payment_method: selectedPayment.value,
            customer_id: selectedCustomer.value.id,
        });

        if (response.data) {
            useSpeak('Transaksi berhasil');

            Toast.fire({
                icon: "success",
                title: 'Transaksi berhasil',
                position: 'bottom-right',
            });

            paymentAmount.value = '';
            computedPaymentAmount.value = '';
            selectedPayment.value = null;

            selectedCustomer.value = null;
            debtItems.value = [];
            debtorInput.value = '';
            totalDebt.value = 0;
        }

        isPaymentModalOpen.value = false;
        generateCode();
    } catch (error) {
        console.error('Payment processing failed', error);
    } finally {
        isLoading.value = false;
    }
};

const Toast = useToast();

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

    return "PH" + now() + "-" + Math.random().toString(36).substr(2, 5).toUpperCase();
};

const successAudio = new Audio('/success.mp3');
const errorAudio = new Audio('/error.mp3');

// Preload the audio files
successAudio.load();
errorAudio.load();

const handleAddDebtor = async () => {
    const response = await axios.post('/customers/getCustomer', {
        name: debtorInput.value
    })

    if (response.data.data.length > 0) {
        isCustomerModalOpen.value = true;
        searchingCustomer.value = response.data.data;
    } else {
        errorAudio.play();

        Toast.fire({
            icon: "error",
            title: 'Pelanggan tidak ditemukan',
            position: 'bottom-right',
        });
    }
}

const handleSelect = (customer) => {
    selectedCustomer.value = customer;
    isCustomerModalOpen.value = false;
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
    }, 0);


    debtorInput.value = '';

    console.log('total_debt', totalDebt.value);
    console.log("Selected customer:", selectedCustomer.value)

    console.log("Debt items:", debtItems.value)
};

const handlePayment = () => {
    // Logic to process the debt payment
    console.log("Processing payment of:", paymentAmount.value)
}

function formatRupiah(value) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value);
}

onMounted(() => {
    timer = setInterval(updateDateTime, 1000);
    paymentCode.value = generateCode()
})

onUnmounted(() => {
    clearInterval(timer);
});
</script>