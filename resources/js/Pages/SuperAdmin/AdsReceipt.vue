<script setup>
import { ref, computed, onMounted } from 'vue'
import SuperAdminLayout from '@/Layouts/SuperAdminLayout.vue';
import { FormField } from '@/Components/ui/form';
import FormItem from '@/Components/ui/form/FormItem.vue';
import FormLabel from '@/Components/ui/form/FormLabel.vue';
import Select from '@/Components/ui/select/Select.vue';
import FormControl from '@/Components/ui/form/FormControl.vue';
import SelectTrigger from '@/Components/ui/select/SelectTrigger.vue';
import SelectValue from '@/Components/ui/select/SelectValue.vue';
import SelectContent from '@/Components/ui/select/SelectContent.vue';
import SelectGroup from '@/Components/ui/select/SelectGroup.vue';
import SelectItem from '@/Components/ui/select/SelectItem.vue';
import FormMessage from '@/Components/ui/form/FormMessage.vue';
import FormInput from '@/Components/ui/form/FormInput.vue';
import Textarea from '@/Components/ui/textarea/Textarea.vue';
import Button from '@/components/ui/button/Button.vue';
import { useForm } from 'vee-validate';
import { toTypedSchema } from '@vee-validate/zod';
import { z } from 'zod';
import axios from 'axios';
import { useToast } from '@/Composables/useToast';

const Toast = useToast();

// Store Information
const storeName = ref('TOKO SEMPURNA')
const storeAddress = ref('Jl. Raya No. 123, Jakarta')

// Transaction Items (Example)
const items = ref([
    { name: 'Product 1', qty: 2, price: 150000 },
    { name: 'Product 2', qty: 1, price: 300000 },
])

const FormSchema = toTypedSchema(z.object({
    sponsorType: z.string(),
    sponsorName: z.string(),
    sponsorDescription: z.string(),
}));

// Form State
const form = useForm({
    validationSchema: computed(() => FormSchema),
});

const calculateSubtotal = computed(() => {
    return items.value.reduce((total, item) => total + (item.price * item.qty), 0)
})

const calculateTax = computed(() => {
    return calculateSubtotal.value * 0.11
})

const calculateTotal = computed(() => {
    return calculateSubtotal.value + calculateTax.value
})

// Methods
const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(value)
}

const handleSubmit = async () => {
    try {
        let response = await axios.post('/super-admin/ads/receipts', form.values)

        if (response.data.status === 'success') {
            Toast.fire({
                icon: "success",
                title: response.data.message,
            });
        } else {
            Toast.fire({
                icon: "error",
                title: response.data.message,
            });
        }
    } catch (error) {
        console.error('Error:', error)
    }
}

const fetchAds = async () => {
    const response = await axios.get('/api/ads');


    const ads = response.data.data.filter(ad => ad.type === 'receipt');
    console.log(ads);

    form.setValues({
        sponsorType: ads.length > 0 ? ads[0].sponsor_type : 'csr',
        sponsorName: ads.length > 0 ? ads[0].sponsor_name : 'PT. NAMA PERUSAHAAN',
        sponsorDescription: ads.length > 0 ? ads[0].sponsor_description : 'Deskripsi Program',
    })
}

onMounted(() => {
    fetchAds();
})
</script>

<template>
    <SuperAdminLayout>
        <div class="flex md:flex-row flex-col gap-8 md:gap-0">
            <!-- Preview Struk (Left Side) -->
            <div class="md:w-1/2 md:p-6">
                <div class="bg-white rounded-lg border-2 border-gray-200 p-6 max-w-md mx-auto">
                    <!-- Detail Toko -->
                    <div class="text-center mb-4">
                        <h3 class="font-bold">{{ storeName }}</h3>
                        <p class="text-sm">{{ storeAddress }}</p>
                    </div>

                    <div class="border-t pt-2 border-dashed flex justify-between">
                        <p class="font-semibold">Barang</p>
                        <p class="font-semibold">Total</p>
                    </div>

                    <!-- Detail Transaksi -->
                    <div class="border-t border-b border-dashed py-4 mt-2 mb-4">
                        <div v-for="(item, index) in items" :key="index" class="flex justify-between mb-2">
                            <div>
                                <p class="font-sm">{{ item.name }}</p>
                                <p class="text-sm text-gray-600">{{ item.qty }}x @ {{ formatCurrency(item.price) }}</p>
                            </div>
                            <p class="font-sm">{{ formatCurrency(item.qty * item.price) }}</p>
                        </div>
                    </div>

                    <!-- Total -->
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <p class="text-sm">Subtotal</p>
                            <p class="text-sm">{{ formatCurrency(calculateSubtotal) }}</p>
                        </div>
                        <div class="flex justify-between">
                            <p class="text-sm">PPN (11%)</p>
                            <p class="text-sm">{{ formatCurrency(calculateTax) }}</p>
                        </div>
                        <div class="flex justify-between font-bold text-lg">
                            <p class="text-sm">TOTAL</p>
                            <p class="text-sm">{{ formatCurrency(calculateTotal) }}</p>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="mt-4 pt-4 border-t border-dashed text-center text-sm">
                        <p>Terima Kasih Atas Kunjungan Anda!</p>
                    </div>

                    <!-- Sponsor/Credit Text -->
                    <div class="mt-4">
                        <div v-if="form.values.sponsorType === 'csr'" class="text-center space-y-1">
                            <p class="text-sm">CSR By {{ form.values.sponsorName || 'PT. NAMA PERUSAHAAN' }}</p>
                            <p class="text-sm">{{ form.values.sponsorDescription || 'Deskripsi Program' }}</p>
                        </div>
                        <div v-else-if="form.values.sponsorType === 'sponsored'" class="text-center space-y-1">
                            <p class="text-sm">Sponsored By {{ form.values.sponsorName || 'PT. NAMA PERUSAHAAN' }}</p>
                            <p class="text-sm">{{ form.values.sponsorDescription || 'Deskripsi Program' }}</p>
                        </div>
                        <div v-else-if="form.values.sponsorType === 'credit'" class="text-center space-y-1">
                            <p class="text-sm">Credit By {{ form.values.sponsorName || 'PT. NAMA PERUSAHAAN' }}</p>
                            <p class="text-sm">{{ form.values.sponsorDescription || 'Deskripsi Program' }}</p>
                        </div>
                    </div>


                </div>
            </div>

            <!-- Form (Right Side) -->
            <div class="md:w-1/2 md:p-6">
                <div class="bg-white rounded-lg border-2 border-gray-200 p-6">
                    <h2 class="text-lg text-center md:text-start md:text-2xl font-bold mb-6">Pengaturan Teks Sponsor/Kredit</h2>

                    <form @submit.prevent="handleSubmit" class="space-y-6">
                        <!-- Basic Information -->
                        <div class="space-y-4">
                            <FormField v-slot="{ componentField }" name="sponsorType">
                                <FormItem>
                                    <FormLabel>Jenis Teks</FormLabel>
                                    <Select v-bind="componentField">
                                        <FormControl>
                                            <SelectTrigger>
                                                <SelectValue placeholder="Pilih Jenis Teks" />
                                            </SelectTrigger>
                                        </FormControl>
                                        <SelectContent>
                                            <SelectGroup>
                                                <SelectItem value="csr">
                                                    CSR Program
                                                </SelectItem>
                                                <SelectItem value="sponsored">
                                                    Sponsored By
                                                </SelectItem>
                                                <SelectItem value="credit">
                                                    Credit By
                                                </SelectItem>
                                            </SelectGroup>
                                        </SelectContent>
                                    </Select>
                                    <FormMessage />
                                </FormItem>
                            </FormField>

                            <FormInput name="sponsorName" label="Nama Perusahaan" type="text" />

                            <FormField v-slot="{ componentField }" name="sponsorDescription">
                                <FormItem>
                                    <FormLabel>Deskripsi Program</FormLabel>
                                    <FormControl>
                                        <Textarea class="resize-none" v-bind="componentField" />
                                    </FormControl>
                                    <FormMessage />
                                </FormItem>
                            </FormField>
                        </div>

                        <!-- Buttons -->
                        <div class="flex justify-end space-x-4">
                            <Button type="submit"
                                class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white">
                                Simpan
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </SuperAdminLayout>
</template>
