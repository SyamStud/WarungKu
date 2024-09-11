<!-- ReusableTableHeader.vue -->
<script setup>
import { TableHead, TableHeader, TableRow } from '@/Components/ui/table'

const props = defineProps({
    columns: {
        type: Array,
        required: true
    },
    sorting: {
        type: Object,
        required: true
    },
    onSort: {
        type: Function,
        required: true
    },
    showActions: {
        type: Boolean,
        default: true
    }
})

const emit = defineEmits(['sort'])

const handleSort = (accessorKey) => {
    emit('sort', accessorKey)
}
</script>

<template>
    <TableHeader>
        <TableRow>
            <TableHead>No</TableHead>
            <TableHead v-for="column in columns" :key="column.accessorKey"
                @click="() => handleSort(column.accessorKey)">
                {{ column.header }}
                <span v-if="sorting.field === column.accessorKey">
                    {{ sorting.direction === 'asc' ? '↑' : '↓' }}
                </span>
            </TableHead>
            <slot />
            <TableHead v-if="showActions">Aksi</TableHead>
        </TableRow>
    </TableHeader>
</template>