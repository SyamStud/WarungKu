<template>
    <Link :href="href"
        class="font-semibold text-sm text-black flex items-center gap-2 hover:text-gray-600 transition-colors">
    <img :width="20" :height="20" :src="icon" :alt="alt" />
    <span>{{ text }}</span>
    </Link>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    href: {
        type: String,
        required: true
    },
    icon: {
        type: String,
        required: true
    },
    alt: {
        type: String,
        required: true
    },
    text: {
        type: String,
        required: true
    }
});
</script>