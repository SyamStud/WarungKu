<template>
    <div class="relative w-full mx-auto overflow-hidden rounded-lg shadow-lg">
        <!-- Slide Container -->
        <div class="flex transition-transform duration-700 ease-in-out"
            :style="{ transform: `translateX(-${currentSlide * 100}%)` }">
            <div v-for="(image, index) in images" :key="index" class="min-w-full h-64 bg-cover bg-center"
                :style="{ backgroundImage: `url(${image})` }"></div>
        </div>

        <!-- Previous Button -->
        <button
            class="absolute top-1/2 left-2 transform -translate-y-1/2 text-white p-2 rounded-fullfocus:outline-none z-10"
            @click="prevSlide">
            <img class="-rotate-180" width="30" height="30" src="https://img.icons8.com/glyph-neue/64/FFFFFF/circled-chevron-right.png"
                alt="circled-chevron-right" />
        </button>

        <!-- Next Button -->
        <button
            class="absolute top-1/2 right-2 transform -translate-y-1/2 text-white p-2 rounded-full focus:outline-none z-10"
            @click="nextSlide">
            <img width="30" height="30" src="https://img.icons8.com/glyph-neue/64/FFFFFF/circled-chevron-right.png"
                alt="circled-chevron-right" />
        </button>

        <!-- Dots Indicator -->
        <div class="absolute bottom-2 left-1/2 transform -translate-x-1/2 flex space-x-2">
            <span v-for="(image, index) in images" :key="index" @click="setSlide(index)"
                class="w-2 h-2 rounded-full cursor-pointer transition-colors" :class="{
                    'bg-gray-600': currentSlide === index,
                    'bg-gray-300': currentSlide !== index,
                }"></span>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, defineProps } from 'vue';

// Props
const props = defineProps({
    images: {
        type: Array,
        required: true,
    },
    interval: {
        type: Number,
        default: 3000, // Setel interval untuk auto-slide (dalam milidetik)
    },
});

// State management
const currentSlide = ref(0);
let intervalId = null;

// Function for next slide
const nextSlide = () => {
    currentSlide.value = (currentSlide.value + 1) % props.images.length;
};

// Function for previous slide
const prevSlide = () => {
    currentSlide.value =
        (currentSlide.value - 1 + props.images.length) % props.images.length;
};

// Set slide manually
const setSlide = (index) => {
    currentSlide.value = index;
};

// Start the auto-slide
const startAutoSlide = () => {
    intervalId = setInterval(nextSlide, props.interval);
};

// Stop the auto-slide
const stopAutoSlide = () => {
    if (intervalId) clearInterval(intervalId);
};

// Lifecycle Hooks
onMounted(startAutoSlide);
onBeforeUnmount(stopAutoSlide);
</script>

<style scoped>
/* Optional: add custom styles here if needed */
</style>