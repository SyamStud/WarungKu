// SplashScreen.vue
<script setup>
import { onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import ApplicationLogo from './ApplicationLogo.vue';
import { Progress } from '@/Components/ui/progress';
import { watchEffect } from 'vue';

const progress = ref(13);
const isVisible = ref(true);
const router = useRouter();

watchEffect((cleanupFn) => {
    const timer1 = setTimeout(() => (progress.value = 66), 500);
    const timer2 = setTimeout(() => (progress.value = 85), 1000);
    const timer3 = setTimeout(() => (progress.value = 100), 1800);

    cleanupFn(() => {
        clearTimeout(timer1);
        clearTimeout(timer2);
        clearTimeout(timer3);
    });
});

onMounted(() => {
    setTimeout(() => {
        isVisible.value = false;
    }, 2000);
});
</script>

<template>
    <Transition name="splash-slide">
        <div v-if="isVisible" class="fixed inset-0 flex items-center justify-center bg-white z-50">
            <div class="text-center">
                <div class="flex items-center justify-center">
                    <ApplicationLogo class="w-64" />
                </div>
                <div class="mt-10 flex justify-center">
                    <Progress v-model="progress" class="h-2 w-4/5" />
                </div>
            </div>
        </div>
    </Transition>
</template>

<style scoped>
.splash-slide-leave-active {
    transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}

.splash-slide-leave-from {
    transform: translateY(0);
}

.splash-slide-leave-to {
    transform: translateY(-100%);
}

.fixed {
    backface-visibility: hidden;
    transform: translateZ(0);
    -webkit-backface-visibility: hidden;
    -webkit-transform: translateZ(0);
}
</style>