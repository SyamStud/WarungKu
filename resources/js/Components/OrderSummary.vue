<template>
    <div class="h-full flex flex-col">
        <div class="space-y-4 flex justify-between flex-col h-full">
            <div>
                <h5 class="font-bold text-xl">Total :</h5>
                <div class="mt-2 bg-blue-100 p-5 rounded-lg shadow-inner border border-gray-300">
                    <h2 class="text-5xl font-bold">{{ formatRupiah(grandTotal) }}</h2>
                </div>
                <p class="text-sm text-gray-600 mt-2">Terbilang : <span v-if="grandTotal > 0" class="font-bold">{{
                    useTerbilang(parseFloat(grandTotal)) }}</span></p>


            </div>
            <div>

                <button v-if="grandTotal" @click="openPaymentModal"
                    class="w-full bg-blue-500 text-white py-2 px-4 rounded-md hover:bg-blue-600 transition duration-300">
                    Proses Pembayaran
                </button>
            </div>
        </div>
    </div>

    <!-- <div class="space-y-2">
        <div class="flex justify-between">
            <span>Subtotal:</span>
            <span>{{ formatRupiah(total) }}</span>
        </div>
        <div class="flex justify-between">
            <span>Pajak (0%):</span>
            <span>{{ formatRupiah(tax) }}</span>
        </div>
        <div class="flex justify-between">
            <span>Diskon (0%):</span>
            <span>{{ formatRupiah(tax) }}</span>
        </div>
    </div> -->

    <!-- <Separator class="my-4" />
    <div class="flex justify-between text-lg font-bold">
        <span>Grand Total:</span>
        <span>{{ formatRupiah(grandTotal) }}</span>
    </div> -->

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
                    <div class="flex gap-2">
                        <Button :class="[
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

                        <Button :class="[
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

                        <Button :class="[
                            'flex items-center space-x-2 py-6 rounded-md w-full',
                            selectedPayment === 'hutang' ? 'bg-gray-800' : 'bg-white border border-gray-300 text-gray-900 hover:text-white'
                        ]" @click="selectPaymentMethod('hutang')">

                            <input id="hutang" name="payment-method" type="radio" class="hidden"
                                :checked="selectedPayment === 'hutang'">

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
                        <h5 class="font-extrabold text-3xl">
                            <span v-if="kembali >= 0">{{ computedBayarValue ? formatRupiah(kembali) : `Masukkan Nominal
                                Bayar` }}</span>
                            <span v-else class="text-red-500">Nominal pembayaran kurang</span>
                        </h5>
                        <p class="text-sm text-gray-600 mt-2">Terbilang : <span v-if="kembali > 0" class="font-bold">{{
                            useTerbilang(parseFloat(kembali)) }}</span></p>
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

                <div v-if="selectedPayment == 'hutang'">
                    <FormInput name="kembali" label="Nama Terhutang" type="text" :disabled="true" v-model="kembali"
                        :value="formatRupiah(kembali)" class="text-lg font-semibold text-blue-600" />
                </div>

                <DialogFooter>
                    <Button type="submit" class="w-full py-3 text-lg"
                        :class="{ 'bg-gray-400': isLoading || !isFormValid }" :disabled="isLoading || !isFormValid">
                        {{ isLoading ? 'Memproses Pembayaran...' : 'Bayar Sekarang' }}
                    </Button>
                </DialogFooter>
            </form>
        </div>
    </DialogWrapper>
</template>

<script setup>
import { defineProps, defineEmits, ref, computed } from 'vue';
import Button from './ui/button/Button.vue';
import Separator from './ui/separator/Separator.vue';
import DialogWrapper from './ui/dialog/DialogWrapper.vue';
import FormInput from './ui/form/FormInput.vue';
import DialogFooter from './ui/dialog/DialogFooter.vue';
import useTerbilang from '@/Composables/useTerbilang';
import useSpeak from '@/Composables/useSpeak';

const props = defineProps({
    total: {
        type: Number,
        required: true
    },
    tax: {
        type: Number,
        required: true
    },
    grandTotal: {
        type: Number,
        required: true
    }
});

const bayar = ref('');
const kembali = ref(0);
const isLoading = ref(false);
const selectedPaymentMethod = ref('');

const selectedPayment = ref(null);

const selectPaymentMethod = (method) => {
    selectedPayment.value = method;
};

const isPaymentModalOpen = ref(false);
const computedBayarValue = computed(() => bayar.value);

const openPaymentModal = () => {
    isPaymentModalOpen.value = true;
    console.log('Grand Total:', props.grandTotal);

    useTerbilang(props.grandTotal);
};

const hitungKembali = () => {
    const bayarValue = computedBayarValue.value;
    kembali.value = bayarValue - props.grandTotal;
    console.log('Kembali:', kembali.value);
};

const isFormValid = computed(() => {
    return bayar.value && bayar.value >= props.grandTotal && selectedPaymentMethod.value;
});

const onSubmit = async () => {
    if (!isFormValid.value) return;

    isLoading.value = true;
    try {
        // Implement your payment processing logic here
        await new Promise(resolve => setTimeout(resolve, 2000));  // Simulating API call
        console.log('Payment processed successfully');
        isPaymentModalOpen.value = false;
    } catch (error) {
        console.error('Payment processing failed', error);
    } finally {
        isLoading.value = false;
    }
};

const emit = defineEmits(['processPayment']);

const processPayment = () => {
    emit('processPayment');
};

function formatRupiah(value) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value);
}
</script>