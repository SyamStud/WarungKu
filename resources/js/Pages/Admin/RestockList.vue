<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Input } from '@/Components/ui/input/index.js';
import { useToast } from '@/Composables/useToast';
import DialogWrapper from '@/Components/ui/dialog/DialogWrapper.vue';
import Textarea from '@/Components/ui/textarea/Textarea.vue';
import Button from '@/components/ui/button/Button.vue';
import Select from '@/Components/ui/select/Select.vue';
import SelectTrigger from '@/Components/ui/select/SelectTrigger.vue';
import SelectValue from '@/Components/ui/select/SelectValue.vue';
import SelectContent from '@/Components/ui/select/SelectContent.vue';
import SelectGroup from '@/Components/ui/select/SelectGroup.vue';
import SelectItem from '@/Components/ui/select/SelectItem.vue';
import Separator from '@/Components/ui/separator/Separator.vue';

const Toast = useToast();
const errors = ref({});

let isLoading = ref(false);

const identifier = ref('');
const isProductModalOpen = ref(false);
const searchingProduct = ref([]);
const selectedProduct = ref(null);

// Fungsi untuk mencari produk berdasarkan nama
const handleSearchProduct = async () => {
    try {
        const response = await axios.post(`/products/getByName`, { name: identifier.value });

        console.log(response.data.product);
        isProductModalOpen.value = true;
        searchingProduct.value = response.data.product;

    } catch (error) {
        console.error('Error searching product:', error);
    }
};

// Fungsi untuk memilih produk dari hasil pencarian
const handleSelect = (product) => {
    selectedProduct.value = product;
    isProductModalOpen.value = false;

    addToList(product);
};

// Fungsi untuk menambahkan produk ke daftar restock
const addToList = async (product) => {
    try {
        const response = await axios.post('/admin/restock-lists', {
            product_id: product.id,
            quantity: 1,
        });

        if (response.data.status === 'error') {
            Toast.fire({
                icon: "error",
                title: "Error saat menambahkan produk",
            });
        } else {
            Toast.fire({
                icon: "success",
                title: response.data.message,
            });

            identifier.value = '';
            selectedProduct.value = null;
            fetchItems(); // Refresh daftar setelah menambahkan item baru
        }
    } catch (error) {
        console.error('Error submitting form:', error);
        Toast.fire({
            icon: "error",
            title: "An error occurred while submitting the form",
        });
    } finally {
        isLoading.value = false;
    }
};

const restockItems = ref([]);
const updatingItemId = ref(null);
const newlyAddedItemId = ref(null);

// Fungsi untuk mengambil daftar restock dari server
const fetchItems = async () => {
    try {
        const response = await axios.get('/api/restock-lists');

        console.log(response.data.data);
        restockItems.value = response.data.data;
    } catch (error) {
        console.error('Error fetching items:', error);
    }
};

const variants = ref([]);
const selectedVariant = ref(null);

// Fungsi untuk mengambil daftar varian produk dari server
const fetchVariants = async (productId) => {
    try {
        const response = await axios.get(`/api/units`);

        variants.value = response.data.data;
        console.log('variants', response.data.data);
    } catch (error) {
        console.error('Error fetching variants:', error);
    }
};

// Fungsi untuk memperbarui kuantitas produk dalam daftar restock
const updateQuantity = async (id, quantity) => {
    updatingItemId.value = id;

    if (quantity === 0) {
        await removeItem(id);
        return;
    }

    try {
        const response = await axios.post(`/admin/restock-lists/${id}?_method=PUT`, {
            id: id,
            quantity: quantity,
        });

        if (response.data && response.data.data) {
            const updatedItem = response.data.data.find(item => item.id === id);
            if (updatedItem) {
                const index = restockItems.value.findIndex(item => item.id === id);
                if (index !== -1) {
                    restockItems.value[index] = updatedItem;
                }
            }
        }
    } catch (error) {
        console.error('Error updating quantity:', error);
        Toast.fire({
            icon: "error",
            title: "An error occurred while updating the quantity",
        });
    } finally {
        updatingItemId.value = null;
    }
};

// Fungsi untuk memperbarui catatan produk dalam daftar restock
const updateNote = (id, note) => {
    const index = restockItems.value.findIndex(item => item.id === id);
    if (index !== -1) {
        restockItems.value[index].note = note;
    }

    updatingItemId.value = id;

    axios.post(`/admin/restock-lists/${id}?_method=PUT`, {
        note: note,
    }).then(response => {
        if (response.data && response.data.data) {
            const updatedItem = response.data.data.find(item => item.id === id);
            if (updatedItem) {
                const index = restockItems.value.findIndex(item => item.id === id);
                if (index !== -1) {
                    restockItems.value[index] = updatedItem;
                }
            }
        }
    }).catch(error => {
        console.error('Error updating note:', error);
        fetchItems();
    }).finally(() => {
        updatingItemId.value = null;
    });
};

// Fungsi untuk memperbarui varian produk dalam daftar restock
const updateVariant = (itemId, variantId) => {

    console.log('itemId', itemId);
    console.log('variantId', variantId);

    const item = restockItems.value.find(item => item.id === itemId);

    console.log('item', item);

    // Optimistic update
    updatingItemId.value = itemId;

    axios.post(`/admin/restock-lists/${itemId}?_method=PUT`, {
        unit_id: variantId,
    }).then(response => {
        if (response.data && response.data.data) {
            restockItems.value = response.data.data;

            console.log('restockItems', restockItems.value);
        }
    }).catch(error => {
        console.error('Error updating variant:', error);
        fetchItems();
    }).finally(() => {
        updatingItemId.value = null;
    });
};

// Fungsi untuk menghapus produk dari daftar restock
const removeItem = async (id) => {
    try {
        await axios.delete(`/admin/restock-lists/${id}`);
        restockItems.value = restockItems.value.filter(item => item.id !== id);
        Toast.fire({
            icon: "success",
            title: "Item removed successfully",
        });
    } catch (error) {
        console.error('Error removing item:', error);
        Toast.fire({
            icon: "error",
            title: "An error occurred while removing the item",
        });
    }
};

const removeButtons = ref([]);

// Fungsi untuk memfokuskan tombol hapus berikutnya
const focusNextRemoveButton = (currentIndex) => {
    const nextIndex = (currentIndex + 1) % removeButtons.value.length;
    focusRemoveButton(nextIndex);
};

// Fungsi untuk memfokuskan tombol hapus sebelumnya
const focusPreviousRemoveButton = (currentIndex) => {
    const previousIndex = (currentIndex - 1 + removeButtons.value.length) % removeButtons.value.length;
    focusRemoveButton(previousIndex);
};

const pseudoFocusedIndex = ref(null);

// Fungsi untuk mengecek apakah tombol hapus sedang difokuskan
const isPseudoFocused = (index) => pseudoFocusedIndex.value === index;

// Fungsi untuk memfokuskan tombol hapus
const focusRemoveButton = (index) => {
    if (removeButtons.value[index]) {
        removeButtons.value[index].$el.focus();
        pseudoFocusedIndex.value = index;
    }
};

// Fungsi untuk menghilangkan fokus dari tombol hapus
const blurRemoveButton = (index) => {
    if (pseudoFocusedIndex.value === index) {
        pseudoFocusedIndex.value = null;
    }
};

// Fungsi untuk mencetak daftar restock
const print = async () => {
    try {
        const response = await axios.post('/admin/restock-lists/print');
        console.log('response.data', response.data);
    } catch (error) {
        console.error('Error:', error);
    }
};

// Fungsi yang dijalankan saat komponen dimuat
onMounted(() => {
    fetchItems();
    fetchVariants();
});

</script>

<style scope src="vue-multiselect/dist/vue-multiselect.css"></style>

<template>
    <!-- Mengatur judul halaman -->

    <Head title="Form Restock" />

    <AdminLayout>
        <div class="flex w-full gap-5 items-center">
            <!-- Judul Halaman -->
            <h1 class="text-2xl font-semibold text-gray-900">Tambah Daftar Belanja</h1>
            <Separator orientation="vertical" class="h-5 w-[2px] bg-gray-300" />
            <Button @click="print">Cetak Daftar Belanja</Button>
        </div>
        <hr class="mt-5 border-[1.5px] bg-gray-300">

        <div>
            <div class="mx-auto p-6 bg-white rounded-lg">
                <form @submit.prevent="handleSearchProduct" enctype="multipart/form-data" class="space-y-4">
                    <div class="bg-white p-6 border border-gray-200 rounded-lg shadow-sm">
                        <div class="w-full mb-4">
                            <label for="productSearch" class="block text-sm font-medium text-gray-700 mb-1"
                                :class="{ 'text-red-500': errors['identifier'] }">
                                Masukkan Nama Produk
                            </label>
                            <div class="mt-2 relative rounded-md shadow-sm">
                                <Input id="productSearch" v-model="identifier" type="text" name="identifier"
                                    class="block w-full pr-10 border-gray-300 rounded-md sm:text-sm"
                                    :class="{ 'border-red-300': errors['identifier'] }" placeholder=""
                                    @keyup.enter="handleSearchProduct" @keydown.enter.prevent />


                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd"
                                            d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </div>
                            <p v-if="errors['identifier']" class="mt-2 text-sm text-red-600">
                                {{ errors['identifier'][0] }}
                            </p>
                        </div>
                    </div>
                </form>
            </div>

            <div class="p-5">
                <table class="min-w-full bg-white border rounded-lg overflow-hidden">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="text-left p-2 border-b">Nama Produk</th>
                            <th class="text-left p-2 border-b">Kuantitas</th>
                            <th class="text-left p-2 border-b">Catatan</th>
                            <th class="text-left p-2 border-b">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(item, index) in restockItems" :key="item.id" class="border-t"
                            :class="{ 'bg-green-100': item.id === updatingItemId || item.id === newlyAddedItemId }">
                            <td class="p-2 border-b">{{ item.product }}</td>
                            <td class="py-2 px-4">
                                <div class="flex gap-1">
                                    <input type="number" v-model.number="item.quantity"
                                        @change="updateQuantity(item.id, item.quantity)"
                                        class="w-16 px-2 py-1 border rounded-md" min="0" />

                                    <Select v-model="item.unit_id" @update:modelValue="updateVariant(item.id, $event)">
                                        <SelectTrigger class="w-[150px]">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectGroup>
                                                <SelectItem v-for="variant in variants" :key="variant.id"
                                                    :value="variant.id.toString()">
                                                    {{ variant.name }}
                                                </SelectItem>
                                            </SelectGroup>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </td>
                            <td class="py-2 px-4">
                                <Textarea v-model="item.note" @change="updateNote(item.id, item.note)"
                                    class="resize-none" :rows="2" />
                            </td>
                            <td class="py-2 px-4">
                                <Button @click="removeItem(item.id)"
                                    @keydown.down.prevent="focusNextRemoveButton(index)"
                                    @keydown.up.prevent="focusPreviousRemoveButton(index)"
                                    :ref="el => { if (el) removeButtons[index] = el }" :class="{
                                        'flex gap-1 bg-red-500 text-white hover:bg-red-600 hover:ring-4 ring-red-300 focus-visible:ring-4 focus-visible:ring-red-300 focus-visible:ring-offset-0 focus-visible:bg-red-800': true,
                                        'ring-4 ring-red-300': isPseudoFocused(index)
                                    }" @blur="blurRemoveButton(index)">
                                    <img width="20" height="20"
                                        src="https://img.icons8.com/?size=100&id=78581&format=png&color=ffffff"
                                        alt="gear" />
                                    Hapus
                                </Button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AdminLayout>

    <!-- Add Modal -->
    <DialogWrapper v-model:open="isProductModalOpen" title="Pilih Produk" desc="" custom-class="md:w-[70rem]">
        <div class="space-y-4">
            <table class="w-full border-collapse">
                <thead>
                    <tr>
                        <th class="text-left p-2 border-b">SKU</th>
                        <th class="text-left p-2 border-b">Nama Produk</th>
                        <th class="text-left p-2 border-b">Variant</th>
                        <th class="text-left p-2 border-b">Price</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="product in searchingProduct" :key="product.id" @click="handleSelect(product)" :class="{
                        'bg-blue-100': selectedProduct?.id === product.id,
                        'hover:bg-gray-100': selectedProduct?.id !== product.id
                    }" class="cursor-pointer transition-colors duration-150 ease-in-out" tabindex="0"
                        @keydown.enter="handleSelect(product)" @keydown.space.prevent="handleSelect(product)">
                        <td class="p-2 border-b">{{ product.sku }}</td>
                        <td class="p-2 border-b">{{ product.name }}</td>
                        <td class="p-2 border-b">{{ product.variant }}</td>
                        <td class="p-2 border-b">{{ product.price }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </DialogWrapper>
</template>