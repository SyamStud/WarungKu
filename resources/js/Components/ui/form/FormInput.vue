<template>
    <FormField v-if="type === 'file'" :name="name">
        <FormItem class="w-full">
            <FormLabel>{{ label }}</FormLabel>
            <FormControl>
                <slot />
            </FormControl>
            <FormMessage />
        </FormItem>
    </FormField>

    <FormField v-else v-slot="{ componentField }" :name="name">
        <FormItem class="w-full">
            <FormLabel>{{ label }}</FormLabel>
            <FormControl>
                <div class="flex gap-2">
                    <Input :required="required" :name="name" class="w-full" :type="type" :placeholder="placeholder"
                        v-bind="componentField" :readonly="disable == true || disable == true" />
                    <slot />
                </div>
            </FormControl>
            <FormMessage />
        </FormItem>
    </FormField>
</template>

<script setup>
import { FormField, FormItem, FormLabel, FormControl, FormMessage } from '@/Components/ui/form'
import { Input } from '@/Components/ui/input'

const props = defineProps({
    name: {
        type: String,
        required: true
    },
    label: {
        type: String,
        required: true
    },
    type: {
        type: String,
        default: 'text'
    },
    placeholder: {
        type: String,
        default: ''
    },
    disable: {
        type: Boolean,
        default: false
    },
    required: {
        type: Boolean,
        default: false
    }
})
</script>