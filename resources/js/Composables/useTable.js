import { computed } from 'vue'
import {
    useVueTable,
    getCoreRowModel,
    getPaginationRowModel,
} from '@tanstack/vue-table'

export function useTable() {

const table = useVueTable({
    get data() {
        return data.value
    },
    columns,
    getCoreRowModel: getCoreRowModel(),
    getPaginationRowModel: getPaginationRowModel(),
    state: {
        pagination: computed(() => ({
            pageIndex: pagination.value.pageIndex,
            pageSize: pagination.value.pageSize,
        })),
    },
    manualPagination: true,
    pageCount: computed(() => pagination.value.pageCount),
    onPaginationChange: (updater) => {
        if (typeof updater === 'function') {
            const newPagination = updater(pagination.value)
            pagination.value = { ...pagination.value, ...newPagination }
        } else {
            pagination.value = { ...pagination.value, ...updater }
        }
        fetchData()
    }
})

return table;
}