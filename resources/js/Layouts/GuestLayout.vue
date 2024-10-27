<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100">
        <div class="mt-8">
            <Link href="/">
                <ApplicationLogo class="w-60 fill-current" />
            </Link>
        </div>

        <div
            class="w-[90%] md:w-full sm:max-w-sm mt-10 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg mb-8"
        >
            <slot />
        </div>
    </div>
</template>
