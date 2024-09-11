<script setup>
import { computed } from 'vue';
import Button from '../button/Button.vue';

const props = defineProps({
    pagination: {
        type: Object,
        required: true,
    },
    onPageChange: {
        type: Function,
        required: true,
    },
});

const canGoToNextPage = computed(() => {
    return props.pagination.pageIndex < props.pagination.pageCount - 1;
});

const canGoToPreviousPage = computed(() => {
    return props.pagination.pageIndex > 0;
});

const goToNextPage = () => {
    if (canGoToNextPage.value) {
        props.onPageChange(props.pagination.pageIndex + 1);
    }
};

const goToPreviousPage = () => {
    if (canGoToPreviousPage.value) {
        props.onPageChange(props.pagination.pageIndex - 1);
    }
};
</script>

<template>
    <div class="flex items-center justify-between space-x-2 py-4">
        <div>
            Showing {{ pagination.pageIndex * pagination.pageSize + 1 }} to
            {{ Math.min((pagination.pageIndex + 1) * pagination.pageSize, pagination.total) }} of
            {{ pagination.total }} results
        </div>
        <div class="space-x-2">
            <Button variant="outline" size="sm" :disabled="!canGoToPreviousPage" @click="goToPreviousPage">
                Previous
            </Button>
            <Button variant="outline" size="sm" :disabled="!canGoToNextPage" @click="goToNextPage">
                Next
            </Button>
        </div>
    </div>
</template>