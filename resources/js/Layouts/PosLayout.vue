<template>
    <div class="flex h-screen bg-gray-100" @keydown="handleGlobalKeydown">
        <!-- Left Panel: Product List -->
        <div class="w-full p-6 overflow-auto">
            <div class="flex justify-between items-center mb-6 text-xl font-extrabold text-gray-900">
                <div class="flex gap-4">
                    <img class="w-40" src="/assets/logo-2.svg" alt="" srcset="">

                </div>

                <div class="flex gap-4 items-center">
                    <h3><span class="font-bold text-lg text-gray-600">Kode Transaksi :</span> {{ transactionCode }}</h3>
                    <Separator orientation="vertical" class="h-5 w-[2px] bg-gray-300" />
                    <Button v-if="cartItems.length > 0" @click="openRevokeModal"
                        class="bg-red-500 hover:bg-red-700">Batalkan Transaksi</Button>
                </div>
            </div>

            <div class="mb-4 flex space-x-4">
                <Input autofocus ref="skuInputRef" v-model="skuInput" @keyup.enter="addProduct"
                    placeholder="Scan or enter SKU"
                    class="w-1/2 px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" />

                <vue-multiselect ref="productSelectRef" v-model="selectedProduct" :options="products" :searchable="true"
                    :close-on-select="true" :show-labels="false" placeholder="Search products" label="name"
                    track-by="id" @select="addProductBySelection" class="w-1/2 h-2" />
            </div>
            <product-table ref="productTableRef" :items="cartItems" @update-variant="updateVariant"
                @update-quantity="updateQuantity" @remove-item="removeItem" :updating-item-id="updatingItemId" />
        </div>

        <!-- Right Panel: Order Summary -->
        <div class="w-[30rem] p-6 bg-white shadow-lg">
            <order-summary :total="total" :tax="tax" :grand-total="grandTotal" @process-payment="processPayment" />
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
    </div>

    <!-- Add Modal -->
    <DialogWrapper v-model:open="isAddModalOpen" title="Pilih Produk" desc="" custom-class="md:w-[70rem]">
        <div class="space-y-4">
            <table class="w-full border-collapse">
                <thead>
                    <tr>
                        <th class="text-left p-2 border-b">Name</th>
                        <th class="text-left p-2 border-b">Variant</th>
                        <th class="text-left p-2 border-b">Price</th>
                        <th class="text-left p-2 border-b">Stock</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="product in selectedItem" :key="product.id" @click="handleSelect(product)" :class="{
                        'bg-blue-100': selectedProduct?.id === product.id,
                        'hover:bg-gray-100': selectedProduct?.id !== product.id
                    }" class="cursor-pointer transition-colors duration-150 ease-in-out" tabindex="0"
                        @keydown.enter="handleSelect(product)" @keydown.space.prevent="handleSelect(product)">
                        <td class="p-2 border-b">{{ product.name }}</td>
                        <td class="p-2 border-b">{{ product.variant }}</td>
                        <td class="p-2 border-b">{{ product.price }}</td>
                        <td class="p-2 border-b">{{ product.stock }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </DialogWrapper>


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
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, reactive } from 'vue';
import VueMultiselect from 'vue-multiselect';
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
// import useTerbilang from '@/Composables/useTerbilang';

const isAddModalOpen = ref(false);

const Toast = useToast();
const dialogRevokeButton = ref(null);
const isRevokeModalOpen = ref(false);
const cancelButton = ref(null);

const skuInput = ref('');
const selectedProduct = ref(null);
const products = ref([]);
const cartItems = ref([]);
const transactionCode = ref('');


const skuInputRef = ref(null);
const productSelectRef = ref(null);
const updatingItemId = ref(null);

const isLoading = ref(false);
const currentFocusIndex = ref(-1);
const productTableRef = ref(null);

const isHelperOpen = ref(false);

const searchingProduct = ref(null);

const openRevokeModal = () => {
    isRevokeModalOpen.value = true;
};

onMounted(() => {
    window.addEventListener('keydown', handleGlobalKeydown);

    fetchCart();
    fetchProducts();

});

onUnmounted(() => {
    window.removeEventListener('keydown', handleGlobalKeydown);
});

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

const fetchCart = async () => {
    try {
        isLoading.value = true;
        const response = await axios.get('/pos/carts/getUserCart');


        cartItems.value = response.data.data;

        if (cartItems.value.length > 0) {
            transactionCode.value = response.data.transaction_code;
        } else {
            transactionCode.value = generateTransactionCode();
        }

        fetchProducts();
    } catch (error) {
        console.error('Error fetching user cart:', error);
    } finally {
        isLoading.value = false;
    }

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

const fetchProducts = async () => {
    try {
        const response = await axios.get('/api/productVariants');
        products.value = response.data.data.map(product => ({
            ...product,
            name: product.name + ' - ' + product.price.toLocaleString('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).replace('IDR', '').trim()
        }));

    } catch (error) {
        console.error('Error fetching products:', error);
    }
};

const successAudio = new Audio('/success.mp3');
const errorAudio = new Audio('/error.mp3');

// Preload the audio files
successAudio.load();
errorAudio.load();

const productQueue = reactive([]);
const isProcessingQueue = ref(false);
const productCache = new Map();
let debounceTimer;

const getProduct = async (sku) => {
    if (productCache.has(sku)) {
        return productCache.get(sku);
    }

    isLoading.value = true;
    try {
        const response = await axios.post('/products/get', { sku });
        productCache.set(sku, response.data.product);
        return response.data.product;
    } catch (error) {
        console.error('Error fetching product:', error);
        throw error;
    } finally {
        isLoading.value = false;
    }
};

///////!
// const debouncedGetProduct = (sku) => {
//     clearTimeout(debounceTimer);
//     debounceTimer = setTimeout(async () => {
//         try {
//             const product = await getProduct(sku);

//             if (!product) {

//                 const product = await getProductByName(sku);

//                 console.log('product', product);

//                 isAddModalOpen.value = true;
//                 return;
//             } else {
//                 addToCart(product);
//                 fetchCart();
//             }
//         } catch (error) {
//             // Handle error (e.g., show error message to user)
//         }
//     }, 300); // 300ms debounce time
// };

// const addProduct = async () => {

//     if (skuInput.value) {
//         debouncedGetProduct(skuInput.value);
//         skuInput.value = ''; // Clear input after sending
//     }

//     // try {
//     //     const response = await axios.post(`/products/get`, { sku: skuInput.value });
//     //     // Output: product & product variant

//     //     console.log('get')
//     //     if (response.data.product === null) {
//     //         errorAudio.play();

//     //         Toast.fire({
//     //             icon: "error",
//     //             title: 'Produk tidak ditemukan',
//     //             position: 'bottom-right',
//     //         });

//     //         skuInputRef.value.$el.focus();
//     //         skuInput.value = '';
//     //         return;
//     //     }

//     //     successAudio.play();

//     //     addToCart(response.data.product);

//     //     console.log('success');
//     //     skuInputRef.value.$el.focus();
//     //     skuInput.value = '';
//     //     fetchCart();
//     // } catch (error) {
//     //     console.error('Error adding product by SKU:', error);
//     // }
// };



const addProductBySelection = (product) => {
    product.name = product.name.split(' - ')[0];
    addToCart(product);

    selectedProduct.value = null;

    fetchCart();
    productSelectRef.value.$el.querySelector('input').focus();
};


const addToCart = async (product) => {
    // Optimistic update
    const existingItemIndex = cartItems.value.findIndex(item => item.id === product.id);

    if (existingItemIndex !== -1) {
        // If the product already exists in the cart, increase its quantity
        cartItems.value[existingItemIndex].quantity += 1;
        updatingItemId.value = product.id;
    } else {
        // If it's a new product, add it to the cart
        const newItem = { ...product, quantity: 1, price: product.product_variants[0].price, };
        cartItems.value.push(newItem);

        console.log('newItem', newItem);
        updatingItemId.value = newItem.id;
    }

    try {
        isLoading.value = true;
        const response = await axios.post('/pos/carts/addProduct', {
            product: product,
            variant_id: product.product_variants[0].id,
            quantity: 1,
            transaction_code: transactionCode.value
        });

        // Update the cart with the response from the server
        if (response.data && response.data.data) {
            cartItems.value = response.data.data;
        }

    } catch (error) {
        console.error('Error adding product to cart:', error);
        // Revert the optimistic update if there's an error
        if (existingItemIndex !== -1) {
            cartItems.value[existingItemIndex].quantity -= 1;
        } else {
            cartItems.value.pop();
        }
        // You might want to show an error message to the user here
    } finally {
        isLoading.value = false;
        // updatingItemId.value = null;
    }
};

const updateCartItemsOptimistically = (product) => {
    const existingItemIndex = cartItems.value.findIndex(item => item.id === product.id);

    if (existingItemIndex !== -1) {
        // If the product already exists in the cart, increase its quantity
        cartItems.value[existingItemIndex].quantity += 1;
        updatingItemId.value = product.id;
    } else {
        // If it's a new product, add it to the cart
        const newItem = { ...product, quantity: 1, price: product.product_variants[0].price, };
        cartItems.value.push(newItem);
        updatingItemId.value = newItem.id;
    }
};

const processQueue = async () => {
    if (productQueue.length === 0) {
        isProcessingQueue.value = false;
        return;
    }

    isProcessingQueue.value = true;
    const product = productQueue.shift();

    try {
        await axios.post('/pos/carts/addProduct', {
            product: product,
            variant_id: product.product_variants[0].id,
            quantity: 1,
            transaction_code: transactionCode.value
        });
    } catch (error) {
        console.error('Error adding product to cart:', error);
        // Handle error (e.g., revert optimistic update, show error message)
    }

    // Process next item in queue
    processQueue();
};

const updateVariant = (itemId, variantId) => {

    console.log('itemId', itemId);
    console.log('variantId', variantId);

    const item = cartItems.value.find(item => item.id === itemId);

    console.log('item', item);

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
            if (response.data && response.data.data) {
                cartItems.value = response.data.data;
            }
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

const total = computed(() => {
    return cartItems.value.reduce((sum, item) => sum + item.price * item.quantity, 0);
});

const tax = computed(() => total.value * 0);
const grandTotal = computed(() => total.value + tax.value);

const processPayment = async () => {
    try {
        const order = {
            items: cartItems.value,
            total: total.value,
            tax: tax.value,
            grandTotal: grandTotal.value
        };
        const response = await axios.post('/api/orders', order);
        cartItems.value = [];
    } catch (error) {
        console.error('Error processing payment:', error);
    }
};

const handleGlobalKeydown = (event) => {
    const isFocusedOnInput = () => {
        const activeElement = document.activeElement;
        return activeElement.tagName === 'INPUT' || activeElement.tagName === 'TEXTAREA';
    };

    if (event.key === '1' && !isFocusedOnInput() && document.activeElement !== skuInputRef.value.$el) {
        event.preventDefault();
        skuInputRef.value.$el.focus();
    }

    if (event.key === '2' && !isFocusedOnInput() && document.activeElement !== productSelectRef.value.$el) {
        event.preventDefault();
        productSelectRef.value.$el.querySelector('input').focus();
    }

    if (event.code === 'ShiftRight' && document.activeElement === skuInputRef.value.$el) {
        skuInputRef.value.$el.blur();
        skuInput.value = '';
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



const debouncedGetProductByName = (name) => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(async () => {
        try {
            const product = await getProductByName(name);

            console.log('product', product);

            if (!product) {
                errorAudio.play();

                Toast.fire({
                    icon: "error",
                    title: 'Produk tidak ditemukan',
                    position: 'bottom-right',
                });

                skuInputRef.value.$el.focus();
                skuInput.value = '';

                return;
            }

            addToCart(product);
            fetchCart();
        } catch (error) {
            // Handle error (e.g., show error message to user)
        }
    }, 300); // 300ms debounce time
};

const getProductByName = async (name) => {
    console.log('name', name);

    if (productCache.has(name)) {
        return productCache.get(name);
    }

    console.log('productCache', productCache);

    isLoading.value = true;

    try {
        console.log('response');
        const response = await axios.post('/products/getByName', { name });
        productCache.set(name, response.data.product);

        searchingProduct.value = response.data.product;
        console.log('searchingProduct', searchingProduct.value);
        return response.data.product;
    } catch (error) {
        console.error('Error fetching product:', error);
        throw error;
    } finally {
        isLoading.value = false;
    }
};

const selectedItem = ref(null);

const handleSelect = (product) => {
    selectedItem.value = product;
};


//
const addProduct = async () => {
    if (skuInput.value) {
        debouncedGetProduct(skuInput.value);
        skuInput.value = '';
    }
};

const debouncedGetProduct = (identifier) => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(async () => {
        try {
            const product = await getProductByIdentifier(identifier);
            console.log('product', product);

            if (product.source === 'sku') {
                addToCart(product.product);
                fetchCart();
            } else {
                isAddModalOpen.value = true;
            }

            if (product.source) {
                selectedItem.value = product.product;
            } else {
                selectedItem.value = product;
            }
        } catch (error) {
            // Handle error (e.g., show error message to user)
            console.error('Error in debouncedGetProduct:', error);
        }
    }, 300); // 300ms debounce time
};

const getProductByIdentifier = async (identifier) => {
    isLoading.value = true;

    try {
        // Try to get by SKU first
        let response = await axios.post('/products/getBySku', { sku: identifier });

        // If not found by SKU, try by name
        if (!response.data.product) {
            console.log('Searching by name');
            response = await axios.post('/products/getByName', { name: identifier });
        }

        if (response.data.product || response.data.products) {
            // searchingProduct.value = response.data.product || response.data.products;
            return response.data || response.data;
        }

        return null;
    } catch (error) {
        console.error('Error fetching product:', error);
        throw error;
    } finally {
        isLoading.value = false;
    }
};


// const getProductByIdentifier = async (identifier) => {
//     console.log('identifier', identifier);

//     const now = new Date();
//     const cachedData = JSON.parse(localStorage.getItem(`product_${identifier}`));

//     if (cachedData && new Date(cachedData.expires_at) > now) {
//         console.log('Using cached data');
//         searchingProduct.value = cachedData.product;
//         return cachedData;
//     }

//     isLoading.value = true;

//     try {
//         // Try to get by SKU first
//         let response = await axios.post('/products/getBySku', { sku: identifier });

//         // If not found by SKU, try by name
//         if (!response.data.product) {
//             console.log('Searching by name');
//             response = await axios.post('/products/getByName', { name: identifier });
//         }

//         if (response.data.product || response.data.products) {
//             console.log('Product(s) found');
//             const cacheData = {
//                 product: response.data.product || response.data.products,
//                 expires_at: response.data.cache_info.expires_at,
//                 source: response.data.cache_info.source,
//             };
//             localStorage.setItem(`product_${identifier}`, JSON.stringify(cacheData));

//             searchingProduct.value = cacheData.product;
//             return cacheData.product;
//         }

//         return null; // Return null if product not found
//     } catch (error) {
//         console.error('Error fetching product:', error);
//         throw error;
//     } finally {
//         isLoading.value = false;
//     }
// };

// Function to clear expired cache
const clearExpiredProductCache = () => {
    const now = new Date();
    Object.keys(localStorage).forEach(key => {
        if (key.startsWith('product_')) {
            const cachedData = JSON.parse(localStorage.getItem(key));
            if (new Date(cachedData.expires_at) <= now) {
                localStorage.removeItem(key);
            }
        }
    });
};

</script>

<style scoped>
tr:focus {
    outline: 2px solid #4299e1;
    outline-offset: -2px;
}
</style>

<style src="vue-multiselect/dist/vue-multiselect.css"></style>