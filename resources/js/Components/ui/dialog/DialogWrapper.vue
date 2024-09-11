<template>
    <Dialog>
        <DialogScrollContent class="sm:max-w-[425px] md:max-w-full md:w-[40rem]">
            <DialogHeader>
                <DialogTitle>{{ title }}</DialogTitle>
                <DialogDescription>
                    {{ desc }}
                </DialogDescription>
            </DialogHeader>

            <slot />
        </DialogScrollContent>
    </Dialog>
</template>

<script setup>
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogScrollContent,
    DialogTitle,
} from '@/Components/ui/dialog'


const props = defineProps({
    title: {
        type: String,
        required: true
    },
    desc: {
        type: String,
        default: ''
    },
})
</script>