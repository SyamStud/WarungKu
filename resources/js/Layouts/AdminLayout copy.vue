<script setup>
import { ref } from "vue";
import Sidebar from "@/Components/Sidebar.vue";

</script>

<style scoped>
.modal-enter {
    transition: opacity 0.3s ease-out, transform 0.3s ease-out;
    opacity: 0;
    transform: translate(0, 1rem) scale(0.95);
}

.modal-enter-active {
    opacity: 1;
    transform: translate(0, 0) scale(1);
}

.modal-leave {
    transition: opacity 0.2s ease-in, transform 0.2s ease-in;
    opacity: 1;
    transform: translate(0, 0) scale(1);
}

.modal-leave-active {
    opacity: 0;
    transform: translate(0, 1rem) scale(0.95);
}
</style>

<template>


    <div>
        <div class="min-h-screen bg-gray-100">
            <!-- Page Heading -->
            <header class="bg-white shadow" v-if="$slots.header">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <Sidebar>
                <div class="bg-gray-100">
                    <slot />
                </div>
            </Sidebar>
        </div>
    </div>
</template>
