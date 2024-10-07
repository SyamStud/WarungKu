<template>
    <div class="w-full overflow-x-auto">
        <table class="min-w-full bg-white border rounded-lg">
            <thead class="bg-gray-100">
                <tr>
                    <!-- <th class="py-2 px-4 text-left whitespace-nowrap">SKU</th> -->
                    <th class="py-2 px-4 text-left whitespace-nowrap">Nama Produk</th>
                    <th class="py-2 px-4 text-left whitespace-nowrap">Variasi</th>
                    <th class="py-2 px-4 text-left whitespace-nowrap">Harga</th>
                    <th class="py-2 px-4 text-left whitespace-nowrap">Kuantitas</th>
                    <th class="py-2 px-4 text-left whitespace-nowrap">Subtotal</th>
                    <th class="py-2 px-4 text-left whitespace-nowrap">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(item, index) in items" :key="item.id" class="border-t"
                    :class="{ 'bg-green-100': item.id === updatingItemId || item.id === newlyAddedItemId }">
                    <!-- <td class="py-2 px-4 whitespace-nowrap">{{ item.sku }}</td> -->
                    <td class="py-2 px-4 whitespace-nowrap">{{ item.name }}</td>
                    <td class="py-2 px-4 whitespace-nowrap">
                        <Select v-model="item.variant_id" @update:modelValue="updateVariant(item.id, $event)">
                            <SelectTrigger class="w-[150px]">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectGroup>
                                    <SelectItem v-for="variant in item.product_variants" :key="variant.id"
                                        :value="variant.id.toString()">
                                        {{ variant.variant }}
                                    </SelectItem>
                                </SelectGroup>
                            </SelectContent>
                        </Select>
                    </td>
                    <td class="py-2 px-4 whitespace-nowrap">{{ formatRupiah(item.price) }}</td>
                    <td class="py-2 px-4 whitespace-nowrap">
                        <input type="number" v-model.number="item.quantity"
                            @keyup.enter="updateQuantity(item.id, item.quantity)"
                            @blur="updateQuantity(item.id, item.quantity)" @keydown.shift="unfocusInput"
                            class="w-16 px-2 py-1 border rounded"  />
                    </td>
                    <td class="py-2 px-4 whitespace-nowrap">{{ formatRupiah(item.price * item.quantity) }}</td>
                    <td class="py-2 px-4 whitespace-nowrap">
                        <Button @click="() => openDeleteModal(item.id)" :class="{
                                'flex gap-1 bg-red-500 text-white hover:bg-red-600 hover:ring-4 ring-red-300 focus-visible:ring-4 focus-visible:ring-red-300 focus-visible:ring-offset-0 focus-visible:bg-red-800': true,
                                'ring-4 ring-red-300': isPseudoFocused(index)
                            }" @blur="blurRemoveButton(index)">
                            <img width="20" height="20"
                                src="https://img.icons8.com/?size=100&id=78581&format=png&color=ffffff" alt="gear" />
                            Hapus
                        </Button>
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Delete Modal -->
        <DialogWrapper v-model:open="isDeleteModalOpen" title="Hapus Item" desc="Hapus item">
            <DialogFooter>
                <Button ref="cancelButton" @click="isDeleteModalOpen = false"
                    @keydown.right.prevent="$refs.dialogDeleteButton.$el.focus()"
                    @keydown.left.prevent="$refs.dialogDeleteButton.$el.focus()" tabindex="0" variant="outline">
                    Batal
                </Button>
                <Button ref="dialogDeleteButton" @click="removeItem"
                    @keydown.right.prevent="$refs.cancelButton.$el.focus()"
                    @keydown.left.prevent="$refs.cancelButton.$el.focus()" tabindex="0" variant="destructive">
                    Hapus
                </Button>
            </DialogFooter>
        </DialogWrapper>

        <Toaster />
    </div>
</template>

<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue';
import Button from './ui/button/Button.vue';
import DialogWrapper from './ui/dialog/DialogWrapper.vue';
import DialogFooter from './ui/dialog/DialogFooter.vue';
import { useToast } from '@/Components/ui/toast/use-toast'
import Toaster from './ui/toast/Toaster.vue';
import Select from './ui/select/Select.vue';
import SelectTrigger from './ui/select/SelectTrigger.vue';
import SelectValue from './ui/select/SelectValue.vue';
import SelectContent from './ui/select/SelectContent.vue';
import SelectGroup from './ui/select/SelectGroup.vue';
import SelectItem from './ui/select/SelectItem.vue';

const props = defineProps({
    items: {
        type: Array,
        required: true
    },
    updatingItemId: [String, Number],
    newlyAddedItemId: [String, Number]
});

const emit = defineEmits(['updateQuantity', 'removeItem', 'updateVariant']);

const { toast } = useToast();

const quantityInputs = ref([]);
const removeButtons = ref([]);
const isDeleteModalOpen = ref(false);
const selectedItem = ref(null);

const cancelButton = ref(null);
const dialogDeleteButton = ref(null);

let isHKeyPressed = false;

const openDeleteModal = (item) => {
    selectedItem.value = item;
    isDeleteModalOpen.value = true;
};

const updateVariant = (itemId, variantId) => {
    emit('updateVariant', itemId, variantId);
};

const updateQuantity = (id, quantity) => {
    emit('updateQuantity', id, quantity);
};

const removeItem = () => {
    emit('removeItem', selectedItem.value);
    isHKeyPressed = false;
    isDeleteModalOpen.value = false;
    toast({
        title: 'Item berhasil dihapus',
        description: 'Harga total telah disesuaikan',
        variant: 'destructive',
        duration: 2000
    });
};

const isFocusedOnInput = () => {
    return document.activeElement.tagName === 'INPUT';
};

const focusQuantityInput = (index) => {
    if (quantityInputs.value[index]) {
        quantityInputs.value[index].focus();
    }
};

const focusNextQuantityInput = () => {
    const currentIndex = quantityInputs.value.findIndex(input => input === document.activeElement);
    const nextIndex = (currentIndex + 1) % quantityInputs.value.length;
    focusQuantityInput(nextIndex);
};

const focusPreviousQuantityInput = () => {
    const currentIndex = quantityInputs.value.findIndex(input => input === document.activeElement);
    const previousIndex = (currentIndex - 1 + quantityInputs.value.length) % quantityInputs.value.length;
    focusQuantityInput(previousIndex);
};

const focusNextRemoveButton = (currentIndex) => {
    const nextIndex = (currentIndex + 1) % removeButtons.value.length;
    focusRemoveButton(nextIndex);
};

const focusPreviousRemoveButton = (currentIndex) => {
    const previousIndex = (currentIndex - 1 + removeButtons.value.length) % removeButtons.value.length;
    focusRemoveButton(previousIndex);
};

const pseudoFocusedIndex = ref(null);

const isPseudoFocused = (index) => pseudoFocusedIndex.value === index;

const focusRemoveButton = (index) => {
    if (removeButtons.value[index]) {
        removeButtons.value[index].$el.focus();
        pseudoFocusedIndex.value = index;
    }
};

const blurRemoveButton = (index) => {
    if (pseudoFocusedIndex.value === index) {
        pseudoFocusedIndex.value = null;
    }
};

const handleGlobalKeydown = (event) => {
    if (event.key === 'h' && !isFocusedOnInput()) {
        isHKeyPressed = !isHKeyPressed;
        if (!isInputFocused() || isHKeyPressed) {
            event.preventDefault();
            if (document.activeElement instanceof HTMLElement) {
                document.activeElement.blur();
            }
            if (isHKeyPressed) {
                focusRemoveButton(0);
            }
        }
    } else if (event.key === 'Shift') {
        if (document.activeElement instanceof HTMLElement) {
            document.activeElement.blur();
        }
        isHKeyPressed = false;
    }
};

const isInputFocused = () => {
    return document.activeElement.tagName === 'INPUT' || document.activeElement.tagName === 'TEXTAREA';
};

watch(() => props.items, () => {
    quantityInputs.value = new Array(props.items.length);
    removeButtons.value = new Array(props.items.length);
}, { immediate: true });

onMounted(() => {
    quantityInputs.value = quantityInputs.value.filter(input => input !== null);
    removeButtons.value = removeButtons.value.filter(button => button !== null);
    window.addEventListener('keydown', handleGlobalKeydown);
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleGlobalKeydown);
});

const formatRupiah = (value) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value);
};

const unfocusInput = (event) => {
    event.target.blur();
};

defineExpose({ focusNextQuantityInput, focusPreviousQuantityInput });
</script>