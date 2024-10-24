<template>
    <div
        class="min-h-screen bg-gradient-to-b"
        :class="[
            user.store.status === 'rejected'
                ? 'from-red-50 to-white'
                : 'from-blue-50 to-white',
        ]"
    >
        <div class="max-w-3xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
            <!-- Status Card -->
            <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
                <!-- Header with Animation -->
                <div
                    class="relative h-56 flex items-center justify-center"
                    :class="[
                        user.store.status === 'rejected'
                            ? 'bg-gradient-to-r from-red-500 to-red-600'
                            : 'bg-gradient-to-r from-blue-500 to-blue-600',
                    ]"
                >
                    <!-- Processing Icon / Rejected Icon -->
                    <div
                        class="absolute"
                        v-if="user.store.status !== 'rejected'"
                    >
                        <svg
                            class="w-32 h-32 text-white animate-spin-slow"
                            viewBox="0 0 24 24"
                        >
                            <circle
                                class="opacity-25"
                                cx="12"
                                cy="12"
                                r="10"
                                stroke="currentColor"
                                stroke-width="4"
                                fill="none"
                            ></circle>
                            <path
                                class="opacity-75"
                                fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                            ></path>
                        </svg>
                    </div>
                    <div class="absolute" v-else>
                        <svg
                            class="w-32 h-32 text-white"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                            />
                        </svg>
                    </div>

                    <!-- Status Text -->
                    <div class="absolute mt-44 text-white font-medium">
                        <template v-if="user.store.status !== 'rejected'">
                            <span class="inline-block animate-bounce">P</span>
                            <span
                                class="inline-block animate-bounce"
                                style="animation-delay: 0.1s"
                                >r</span
                            >
                            <span
                                class="inline-block animate-bounce"
                                style="animation-delay: 0.2s"
                                >o</span
                            >
                            <span
                                class="inline-block animate-bounce"
                                style="animation-delay: 0.3s"
                                >s</span
                            >
                            <span
                                class="inline-block animate-bounce"
                                style="animation-delay: 0.4s"
                                >e</span
                            >
                            <span
                                class="inline-block animate-bounce"
                                style="animation-delay: 0.5s"
                                >s</span
                            >
                        </template>
                        <template v-else>
                            <span class="text-xl">Ditolak</span>
                        </template>
                    </div>
                </div>

                <!-- Content -->
                <div class="px-6 py-8">
                    <div class="text-center">
                        <h2 class="text-2xl font-bold text-gray-900 mb-4">
                            {{
                                user.store.status === "rejected"
                                    ? "Pendaftaran Toko Ditolak"
                                    : `Pendaftaran Toko Sedang
                            Diproses`
                            }}
                        </h2>
                        <p class="text-gray-600 mb-8">
                            {{
                                user.store.status === "rejected"
                                    ? `Mohon maaf, pendaftaran toko Anda tidak dapat disetujui saat ini. Silakan tinjau alasan
                            penolakan di bawah ini.`
                                    : `Tim kami sedang meninjau pendaftaran toko Anda. Proses ini biasanya memakan waktu 1-2 hari
                            kerja.`
                            }}
                        </p>

                        <!-- Status Timeline -->
                        <div class="space-y-6 mb-8">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div
                                        class="w-8 h-8 rounded-full bg-green-500 flex items-center justify-center"
                                    >
                                        <svg
                                            class="w-5 h-5 text-white"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M5 13l4 4L19 7"
                                            ></path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-4 text-left">
                                    <h3
                                        class="text-lg font-medium text-gray-900"
                                    >
                                        Formulir Terkirim
                                    </h3>
                                    <p class="text-sm text-gray-500">
                                        {{ formatDate(submissionDate) }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div
                                        v-if="user.store.status === 'rejected'"
                                        class="w-8 h-8 rounded-full bg-red-500 flex items-center justify-center"
                                    >
                                        <svg
                                            class="w-5 h-5 text-white"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M6 18L18 6M6 6l12 12"
                                            ></path>
                                        </svg>
                                    </div>
                                    <div
                                        v-else
                                        class="w-8 h-8 rounded-full bg-blue-500 flex items-center justify-center animate-pulse"
                                    >
                                        <svg
                                            class="w-5 h-5 text-white"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                            ></path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-4 text-left">
                                    <h3
                                        class="text-lg font-medium text-gray-900"
                                    >
                                        {{
                                            user.store.status === "rejected"
                                                ? "Ditolak"
                                                : "Persetujuan Final"
                                        }}
                                    </h3>
                                    <p class="text-sm text-gray-500">
                                        {{
                                            user.store.status === "rejected"
                                                ? formatDate(rejectionDate)
                                                : `Sedang dalam
                                        proses`
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Rejection Reason (Only shown when rejected) -->
                        <div
                            v-if="user.store.status === 'rejected'"
                            class="bg-red-50 rounded-lg p-6 mb-8"
                        >
                            <div class="flex items-start">
                                <svg
                                    class="w-6 h-6 text-red-500 mt-1"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                    />
                                </svg>
                                <div class="ml-3 text-left">
                                    <h4
                                        class="text-lg font-medium text-gray-900"
                                    >
                                        Alasan Penolakan
                                    </h4>
                                    <p class="text-gray-600 mt-2 text-sm">
                                        {{ user.store.reason_of_rejection }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Info Cards (Only shown when processing) -->
                        <div
                            v-if="user.store.status !== 'rejected'"
                            class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8"
                        >
                            <div class="p-4 bg-blue-50 rounded-lg">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <svg
                                            class="w-6 h-6 text-blue-500"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                            ></path>
                                        </svg>
                                    </div>
                                    <div class="ml-3 text-left">
                                        <h4
                                            class="text-sm font-medium text-gray-900"
                                        >
                                            Estimasi Waktu
                                        </h4>
                                        <p class="text-sm text-gray-500">
                                            1-3 hari kerja
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="p-4 bg-blue-50 rounded-lg">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <svg
                                            class="w-6 h-6 text-blue-500"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                                            ></path>
                                        </svg>
                                    </div>
                                    <div class="ml-3 text-left">
                                        <h4
                                            class="text-sm font-medium text-gray-900"
                                        >
                                            Notifikasi
                                        </h4>
                                        <p class="text-sm text-gray-500">
                                            Akan dikirim via email
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div
                            class="flex flex-col sm:flex-row gap-4 justify-center"
                        >
                            <template v-if="user.store.status === 'rejected'">
                                <Button
                                    @click="cancelRegistration"
                                    class="bg-blue-500 hover:bg-blue-600 flex items-center gap-2"
                                >
                                    <svg
                                        class="w-4 h-4"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                                        ></path>
                                    </svg>
                                    Ajukan Ulang
                                </Button>
                            </template>
                            <template v-else>
                                <div class="w-full flex justify-end">
                                    <Button
                                        class="bg-blue-500 hover:bg-blue-600"
                                        @click="refreshStatus"
                                    >
                                        <svg
                                            class="w-4 h-4 mr-2"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                                            ></path>
                                        </svg>
                                        Refresh Status
                                    </Button>
                                </div>
                                <div class="w-full flex justify-start">
                                    <Button
                                        @click="openCancelModal"
                                        class="bg-red-500 hover:bg-red-600"
                                    >
                                        Batalkan Pendaftaran
                                    </Button>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cancel Modal -->
    <DialogWrapper
        v-model:open="isCancelModalOpen"
        title="Batalkan Pengajuan"
        desc=""
    >
        <p>Anda yakin akan membatalkan pengajuan?</p>
        <DialogFooter>
            <Button @click="isCancelModalOpen = false" variant="outline"
                >Batal</Button
            >
            <Button @click="cancelRegistration" variant="destructive"
                >Ya, Batalkan</Button
            >
        </DialogFooter>
    </DialogWrapper>
</template>

<script setup>
import Button from "@/components/ui/button/Button.vue";
import DialogFooter from "@/Components/ui/dialog/DialogFooter.vue";
import DialogWrapper from "@/Components/ui/dialog/DialogWrapper.vue";
import { Inertia } from "@inertiajs/inertia";
import { router, usePage } from "@inertiajs/vue3";
import { ref, reactive, onMounted, watch } from "vue";

const { props } = usePage();
const user = reactive(props.auth.user);
const isCancelModalOpen = ref(false);

const submissionDate = ref(new Date(user.store.created_at));
const rejectionDate = ref(
    user.store.status === "rejected" ? new Date(user.store.updated_at) : null
);

onMounted(() => {
    console.log(user.store);
});

const openCancelModal = () => {
    isCancelModalOpen.value = true;
};

const formatDate = (date) => {
    return new Intl.DateTimeFormat("id-ID", {
        dateStyle: "full",
        timeStyle: "short",
    }).format(date);
};

const refreshStatus = () => {
    if (user.store.status == "active") {
        router.visit("/admin/dashboard");
    }
};

// Misalnya, jika user.store.status harus merespons perubahan
watch(
    () => user.store.status,
    (newStatus) => {
        if (newStatus == "active") {
            router.visit("/admin/dashboard");
        }
    }
);

const contactSupport = () => {
    // Implement contact support logic
};

const cancelRegistration = async () => {
    const response = await axios.post(
        `super-admin/store-applications/${user.store_id}?_method=delete`
    );

    router.visit("/no-store");

    isCancelModalOpen.value = false;
};
</script>

<style scoped>
.animate-spin-slow {
    animation: spin 3s linear infinite;
}

@keyframes spin {
    from {
        transform: rotate(0deg);
    }

    to {
        transform: rotate(360deg);
    }
}

.animate-bounce {
    animation: bounce 1s infinite;
}

@keyframes bounce {
    0%,
    100% {
        transform: translateY(-25%);
        animation-timing-function: cubic-bezier(0.8, 0, 1, 1);
    }

    50% {
        transform: translateY(0);
        animation-timing-function: cubic-bezier(0, 0, 0.2, 1);
    }
}
</style>
