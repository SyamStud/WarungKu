<template>
    <div class="relative w-full mx-auto overflow-hidden rounded-lg shadow-lg">
        <!-- Slide Container -->
        <div
            class="flex transition-transform duration-700 ease-in-out"
            :style="{ transform: `translateX(-${currentSlide * 100}%)` }"
        >
            <div
                v-for="(image, index) in currentImages"
                :key="index"
                class="min-w-full bg-cover bg-center"
                :class="[
                    'h-48 md:h-80 lg:h-96 xl:h-[32rem]', // Responsive heights
                    'flex items-center justify-center', // Center content
                ]"
                :style="{ backgroundImage: `url(${image})` }"
            >
                <!-- Fallback for loading/error -->
                <div
                    v-if="!imageLoaded[index]"
                    class="w-full h-full flex items-center justify-center bg-gray-200"
                >
                    <div class="animate-pulse">Loading...</div>
                </div>
            </div>
        </div>

        <!-- Previous Button -->
        <button
            class="hidden md:block absolute top-1/2 left-2 transform -translate-y-1/2 text-white p-2 rounded-full focus:outline-none z-10 hover:bg-black/20 transition-colors duration-200"
            @click="prevSlide"
        >
            <svg
                class="w-6 h-6 sm:w-8 sm:h-8"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M15 19l-7-7 7-7"
                />
            </svg>
        </button>

        <!-- Next Button -->
        <button
            class="hidden md:block absolute top-1/2 right-2 transform -translate-y-1/2 text-white p-2 rounded-full focus:outline-none z-10 hover:bg-black/20 transition-colors duration-200"
            @click="nextSlide"
        >
            <svg
                class="w-6 h-6 sm:w-8 sm:h-8"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M9 5l7 7-7 7"
                />
            </svg>
        </button>

        <!-- Dots Indicator -->
        <div
            class="absolute bottom-4 left-1/2 transform -translate-x-1/2 flex space-x-2"
        >
            <button
                v-for="(_, index) in currentImages"
                :key="index"
                @click="setSlide(index)"
                class="w-2 h-2 sm:w-3 sm:h-3 rounded-full transition-all duration-200 focus:outline-none"
                :class="[
                    currentSlide === index
                        ? 'bg-white scale-110'
                        : 'bg-white/50 hover:bg-white/70',
                ]"
                :aria-label="`Go to slide ${index + 1}`"
            ></button>
        </div>

        <!-- Touch Swipe Area -->
        <div
            class="absolute inset-0 z-0"
            @touchstart="handleTouchStart"
            @touchmove="handleTouchMove"
            @touchend="handleTouchEnd"
        ></div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from "vue";

const props = defineProps({
    // For each breakpoint, provide an array of image URLs
    images: {
        type: Object,
        required: true,
        default: () => ({
            mobile: [], // < 640px
            tablet: [], // >= 640px
            laptop: [], // >= 1024px
            desktop: [], // >= 1280px
        }),
    },
    interval: {
        type: Number,
        default: 5000,
    },
    autoplay: {
        type: Boolean,
        default: true,
    },
});

// State
const currentSlide = ref(0);
const imageLoaded = ref([]);
const touchStart = ref(null);
const intervalId = ref(null);
const windowWidth = ref(window.innerWidth);

// Computed property to determine which image set to use based on screen width
const currentImages = computed(() => {
    if (windowWidth.value >= 1280)
        return (
            props.images.desktop ||
            props.images.laptop ||
            props.images.tablet ||
            props.images.mobile
        );
    if (windowWidth.value >= 1024)
        return (
            props.images.laptop || props.images.tablet || props.images.mobile
        );
    if (windowWidth.value >= 640)
        return props.images.tablet || props.images.mobile;
    return props.images.mobile;
});

// Navigation functions
const nextSlide = () => {
    currentSlide.value = (currentSlide.value + 1) % currentImages.value.length;
};

const prevSlide = () => {
    currentSlide.value =
        (currentSlide.value - 1 + currentImages.value.length) %
        currentImages.value.length;
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

// Window resize handler
const handleResize = () => {
    windowWidth.value = window.innerWidth;
};

// Lifecycle hooks
onMounted(() => {
    startAutoplay();
    window.addEventListener("resize", handleResize);

    // Initialize imageLoaded array
    imageLoaded.value = new Array(currentImages.value.length).fill(false);

    // Preload images
    currentImages.value.forEach((src, index) => {
        const img = new Image();
        img.onload = () => {
            imageLoaded.value[index] = true;
        };
        img.src = src;
    });
});

onBeforeUnmount(() => {
    stopAutoplay();
    window.removeEventListener("resize", handleResize);
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
