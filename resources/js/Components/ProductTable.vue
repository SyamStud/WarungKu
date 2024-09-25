<template>
    <table class="min-w-full bg-white border rounded-lg overflow-hidden">
        <thead class="bg-gray-100">
            <tr>
                <th class="py-2 px-4 text-left">SKU</th>
                <th class="py-2 px-4 text-left">Nama Produk</th>
                <th class="py-2 px-4 text-left">Variasi</th>
                <th class="py-2 px-4 text-left">Harga</th>
                <th class="py-2 px-4 text-left">Kuantitas</th>
                <th class="py-2 px-4 text-left">Subtotal</th>
                <th class="py-2 px-4 text-left">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <tr v-for="(item, index) in items" :key="item.id" class="border-t"
                :class="{ 'bg-green-100': item.id === updatingItemId }">
                <td class="py-2 px-4">{{ item.sku }}</td>
                <td class="py-2 px-4">{{ item.name }}</td>
                <td class="py-2 px-4">
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
                <td class="py-2 px-4">{{ formatRupiah(item.price) }}</td>
                <td class="py-2 px-4">
                    <input type="number" v-model.number="item.quantity"
                        @keyup.enter="updateQuantity(item.id, item.quantity)"
                        @blur="updateQuantity(item.id, item.quantity)" @keydown.shift="unfocusInput"
                        @keydown.down.prevent="focusNextQuantityInput()"
                        @keydown.up.prevent="focusPreviousQuantityInput()" min="1" class="w-16 px-2 py-1 border rounded"
                        :ref="el => { if (el) quantityInputs[index] = el }" />
                </td>
                <td class="py-2 px-4">{{ formatRupiah(item.price * item.quantity) }}</td>
                <td class="py-2 px-4">
                    <Button @click="() => openDeleteModal(item.id)" @keydown.down.prevent="focusNextRemoveButton(index)"
                        @keydown.up.prevent="focusPreviousRemoveButton(index)"
                        :ref="el => { if (el) removeButtons[index] = el }" :class="{
                            'bg-red-500 text-white hover:bg-red-600 hover:ring-4 ring-red-300 focus-visible:ring-4 focus-visible:ring-red-300 focus-visible:ring-offset-0 focus-visible:bg-red-800': true,
                            'ring-4 ring-red-300': isPseudoFocused(index)
                        }" @blur="blurRemoveButton(index)">
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
            <Button ref="dialogDeleteButton" @click="removeItem" @keydown.right.prevent="$refs.cancelButton.$el.focus()"
                @keydown.left.prevent="$refs.cancelButton.$el.focus()" tabindex="0" variant="destructive">
                Hapus
            </Button>
        </DialogFooter>
    </DialogWrapper>

    <Toaster />
</template>

<script setup>
import { defineProps, defineEmits, ref, watch, onMounted, onUnmounted } from 'vue';
import Button from './ui/button/Button.vue';
import DialogWrapper from './ui/dialog/DialogWrapper.vue';
import DialogFooter from './ui/dialog/DialogFooter.vue';

import { useToast } from '@/Components/ui/toast/use-toast'
import Toaster from './ui/toast/Toaster.vue';
import Select from './ui/select/Select.vue';
import FormControl from './ui/form/FormControl.vue';
import SelectTrigger from './ui/select/SelectTrigger.vue';
import SelectValue from './ui/select/SelectValue.vue';
import SelectContent from './ui/select/SelectContent.vue';
import SelectGroup from './ui/select/SelectGroup.vue';
import SelectItem from './ui/select/SelectItem.vue';

const { toast } = useToast()


const props = defineProps({
    items: {
        type: Array,
        required: true
    },
    updatingItemId: [String, Number]
});

const emit = defineEmits(['updateQuantity', 'removeItem', 'updateVariant']);

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
    if (event.key === 'h') {
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
        // Menghilangkan fokus dari elemen yang sedang aktif
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

defineExpose({ focusNextQuantityInput, focusPreviousQuantityInput });

function formatRupiah(value) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value);
}

const unfocusInput = (event) => {
    event.target.blur();
};
</script>