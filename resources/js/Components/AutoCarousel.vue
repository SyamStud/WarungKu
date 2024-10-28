<template>
    <div class="relative w-full mx-auto overflow-hidden rounded-lg shadow-lg">
        <!-- Slide Container -->
        <div class="flex transition-transform duration-700 ease-in-out"
            :style="{ transform: `translateX(-${currentSlide * 100}%)` }">
            <div v-for="(image, index) in images" :key="index" class="relative min-w-full">
                <!-- Clickable Container -->
                <component :is="slides[index]?.link ? 'a' : 'div'" :href="slides[index]?.link"
                    :target="slides[index]?.link ? '_blank' : undefined" class="block relative">
                    <!-- Image Container with Aspect Ratio -->
                    <div class="relative w-full h-48 md:h-80 lg:h-96 xl:h-[32rem]">
                        <img :src="image" :alt="`Slide ${index + 1}`"
                            class="absolute inset-0 w-full h-full object-cover" />
                        <!-- Dark Overlay -->
                        <div class="absolute inset-0 bg-black/30"></div>
                    </div>

                    <!-- Content Container -->
                    <div class="absolute inset-0 flex items-center justify-center">
                        <div class="w-full max-w-6xl mx-auto px-4 md:px-8">
                            <!-- Content Wrapper -->
                            <div class="relative max-w-xl mx-auto text-center text-white">
                                <!-- Logo -->
                                <div class="mb-4 flex justify-center">
                                    <img v-if="slides[index]?.logo" :src="slides[index].logo"
                                        class="h-16 md:h-20 lg:h-24 object-contain" alt="Logo" />
                                </div>

                                <!-- Title -->
                                <h2 v-if="slides[index]?.title"
                                    class="text-2xl md:text-3xl lg:text-4xl xl:text-5xl font-bold mb-4 tracking-tight"
                                    style="text-shadow: 2px 2px 4px rgba(0,0,0,0.5);">
                                    {{ slides[index].title }}
                                </h2>

                                <!-- Description -->
                                <p v-if="slides[index]?.description"
                                    class="text-sm md:text-base lg:text-lg xl:text-xl max-w-lg mx-auto"
                                    style="text-shadow: 1px 1px 2px rgba(0,0,0,0.5);">
                                    {{ slides[index].description }}
                                </p>
                            </div>
                        </div>
                    </div>
                </component>

                <!-- Loading Fallback -->
                <div v-if="!imageLoaded[index]" class="absolute inset-0 flex items-center justify-center bg-gray-200">
                    <div class="animate-pulse">Loading...</div>
                </div>
            </div>
        </div>

        <!-- Previous Button -->
        <button
            class=" absolute top-1/2 left-2 transform -translate-y-1/2 text-white p-2 rounded-full focus:outline-none z-10 hover:bg-black/20 transition-colors duration-200"
            @click.prevent="prevSlide">
            <svg class="w-6 h-6 sm:w-8 sm:h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
        </button>

        <!-- Next Button -->
        <button
            class=" absolute top-1/2 right-2 transform -translate-y-1/2 text-white p-2 rounded-full focus:outline-none z-10 hover:bg-black/20 transition-colors duration-200"
            @click.prevent="nextSlide">
            <svg class="w-6 h-6 sm:w-8 sm:h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
        </button>

        <!-- Dots Indicator -->
        <div class="hidden md:flex absolute bottom-4 left-1/2 transform -translate-x-1/2 space-x-2">
            <button v-for="(_, index) in images" :key="index" @click.prevent="setSlide(index)"
                class="w-2 h-2 sm:w-3 sm:h-3 rounded-full transition-all duration-200 focus:outline-none" :class="[
                    currentSlide === index
                        ? 'bg-white scale-110'
                        : 'bg-white/50 hover:bg-white/70',
                ]" :aria-label="`Go to slide ${index + 1}`"></button>
        </div>

        <!-- Touch Swipe Area -->
        <div class="absolute  z-0" @touchstart.capture="handleTouchStart" @touchmove.capture="handleTouchMove"
            @touchend.capture="handleTouchEnd"></div>
    </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';

const props = defineProps({
    images: {
        type: Array,
        default: () => [],
    },
    interval: {
        type: Number,
        default: 5000,
    },
    autoplay: {
        type: Boolean,
        default: true,
    },
    slides: {
        type: Array,
        default: () => [],
        validator: (value) => {
            return value.every(slide => {
                return typeof slide === 'object' &&
                    (!slide.link || typeof slide.link === 'string') &&
                    (!slide.logo || typeof slide.logo === 'string') &&
                    (!slide.title || typeof slide.title === 'string') &&
                    (!slide.description || typeof slide.description === 'string');
            });
        }
    },
});

// State
const currentSlide = ref(0);
const imageLoaded = ref([]);
const touchStart = ref(null);
const intervalId = ref(null);

// Navigation functions
const nextSlide = () => {
    currentSlide.value = (currentSlide.value + 1) % props.images.length;
};

const prevSlide = () => {
    currentSlide.value = (currentSlide.value - 1 + props.images.length) % props.images.length;
};

const setSlide = (index) => {
    currentSlide.value = index;
    resetAutoplay();
};

// Touch handling
const handleTouchStart = (e) => {
    touchStart.value = e.touches[0].clientX;
};

const handleTouchMove = (e) => {
    if (!touchStart.value) return;

    const currentX = e.touches[0].clientX;
    const diff = touchStart.value - currentX;

    if (Math.abs(diff) > 50) {
        if (diff > 0) {
            nextSlide();
        } else {
            prevSlide();
        }
        touchStart.value = null;
    }
};

const handleTouchEnd = () => {
    touchStart.value = null;
};

// Autoplay functions
const startAutoplay = () => {
    if (props.autoplay && !intervalId.value) {
        intervalId.value = setInterval(nextSlide, props.interval);
    }
};

const stopAutoplay = () => {
    if (intervalId.value) {
        clearInterval(intervalId.value);
        intervalId.value = null;
    }
};

const resetAutoplay = () => {
    stopAutoplay();
    startAutoplay();
};

// Lifecycle hooks
onMounted(() => {
    startAutoplay();

    // Initialize imageLoaded array
    imageLoaded.value = new Array(props.images.length).fill(false);

    // Preload images
    props.images.forEach((src, index) => {
        const img = new Image();
        img.onload = () => {
            imageLoaded.value[index] = true;
        };
        img.src = src;
    });
});

onBeforeUnmount(() => {
    stopAutoplay();
});
</script>

<style scoped>
.carousel-slide-enter-active,
.carousel-slide-leave-active {
    transition: all 0.75s ease-in-out;
}

.carousel-slide-enter-from {
    transform: translateX(100%);
    opacity: 0;
}

.carousel-slide-leave-to {
    transform: translateX(-100%);
    opacity: 0;
}
</style>