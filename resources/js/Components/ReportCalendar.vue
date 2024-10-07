<template>
    <div class="mx-auto font-sans p-4 2xl:p-6 bg-white rounded-lg border border-gray-200">
        <!-- Header Navigasi Bulan -->
        <div class="flex justify-between items-center mb-6">
            <button @click="changeMonth(-1)"
                class="p-2 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-600 transition-colors focus:outline-none focus:ring-2 focus:ring-gray-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </button>
            <h2 class="text-xl 2xl:text-3xl font-extrabold text-gray-800 tracking-tight">{{ currentMonthYear }}</h2>
            <button @click="changeMonth(1)"
                class="p-2 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-600 transition-colors focus:outline-none focus:ring-2 focus:ring-gray-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
        </div>

        <!-- Grid Kalender -->
        <div class="grid grid-cols-7 gap-1 bg-gray-200 rounded-lg overflow-hidden">
            <!-- Header Hari -->
            <div v-for="day in daysOfWeek" :key="day"
                class="bg-gray-100 py-3 text-center text-sm font-bold uppercase text-gray-600">
                {{ day }}
            </div>

            <!-- Tanggal dalam Kalender -->
            <div v-for="{ date, data } in calendarDays" :key="date"
                class="relative bg-white p-3 h-14 2xl:h-24 flex flex-col justify-between items-center transition-transform transform hover:scale-105 cursor-pointer border"
                :class="{
                    'bg-[#74d39c]': data,
                    'opacity-30 bg-gray-50 border-gray-200': !isCurrentMonth(date),
                    'border-blue-500 bg-blue-50': isToday(date)
                }" @click="handleDayClick(date, data)">
                <!-- Tanggal -->
                <span class="absolute 2xl:top-2 2xl:left-2 text-sm font-semibold"
                    :class="isCurrentMonth(date) ? 'text-gray-800' : 'text-gray-500'">
                    {{ date.getDate() }}
                </span>

                <!-- data jika ada -->
                <span v-if="data"
                    class="hidden 2xl:block mt-auto px-3 py-1 text-xs font-medium bg-green-200 text-green-700 rounded-full">
                    {{ formatRupiah(data) }}
                </span>
            </div>
        </div>

        <p class="text-xs mt-2 text-gray-400">Klik tanggal untuk melihat jumlah penjualan</p>

        <!-- Modal Pop-up -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4">
            <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-sm">
                <h3 class="text-lg font-bold mb-2">Detail Tanggal</h3>
                <p class="text-sm text-gray-500 mb-4">Tanggal: {{ selectedDate }}</p>
                <p class="text-2xl font-semibold text-gray-800 mb-6">{{ selectedData ? formatRupiah(selectedData) :
                    'Tidak ada data' }}</p>
                <button @click="showModal = false"
                    class="w-full py-2 px-4 rounded bg-blue-500 text-white hover:bg-blue-600 transition-colors">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';

const props = defineProps({
    apiEndpoint: {
        type: String,
        required: true
    },
    dataKey: {
        type: String,
        required: true
    },
});

const currentDate = ref(new Date());
const data = ref({});
const showModal = ref(false);
const selectedDate = ref('');
const selectedData = ref(null);

const daysOfWeek = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

const currentMonthYear = computed(() => {
    return currentDate.value.toLocaleString('default', { month: 'long', year: 'numeric' });
});

const calendarDays = computed(() => {
    const year = currentDate.value.getFullYear();
    const month = currentDate.value.getMonth();
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const daysInMonth = lastDay.getDate();
    const startingDayOfWeek = firstDay.getDay();

    const days = [];

    // Add days from previous month
    for (let i = startingDayOfWeek - 1; i >= 0; i--) {
        const date = new Date(year, month, -i);
        days.push({
            date,
            data: data.value[formatDate(date)],
        });
    }

    // Add days of current month
    for (let i = 1; i <= daysInMonth; i++) {
        const date = new Date(year, month, i);
        days.push({
            date,
            data: data.value[formatDate(date)],
        });
    }

    // Add days from next month
    const remainingDays = 42 - days.length; // 6 rows * 7 days
    for (let i = 1; i <= remainingDays; i++) {
        const date = new Date(year, month + 1, i);
        days.push({
            date,
            data: data.value[formatDate(date)],
        });
    }

    return days;
});

const changeMonth = (delta) => {
    const newDate = new Date(currentDate.value);
    newDate.setMonth(newDate.getMonth() + delta);
    currentDate.value = newDate;
    fetchData();
};

const isCurrentMonth = (date) => date.getMonth() === currentDate.value.getMonth();

const isToday = (date) => {
    const today = new Date();
    return date.getDate() === today.getDate() && date.getMonth() === today.getMonth() && date.getFullYear() === today.getFullYear();
};

const formatDate = (date) => {
    // Mengubah format ke string 'YYYY-MM-DD' lokal
    const year = date.getFullYear();
    const month = (`0${date.getMonth() + 1}`).slice(-2);
    const day = (`0${date.getDate()}`).slice(-2);
    return `${year}-${month}-${day}`;
};

function formatRupiah(value) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value);
}

const fetchData = async () => {
    const year = currentDate.value.getFullYear();
    const month = currentDate.value.getMonth() + 1;

    try {
        const response = await axios.get(`${props.apiEndpoint}/${year}/${month}`);
        data.value = response.data;
    } catch (error) {
        console.error(`Error fetching ${props.dataKey} data:`, error);
    }
};

const handleDayClick = (date, dayData) => {
    if (window.innerWidth < 768) { // Hanya tampilkan modal di tampilan mobile
        selectedDate.value = formatDate(date);  // Gunakan `formatDate` yang baru
        selectedData.value = dayData;
        showModal.value = true;
    }
};

onMounted(fetchData);
</script>


<style scoped>
/* Styling tambahan untuk modal */
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.3s ease;
}

.modal-enter,
.modal-leave-to

/* .modal-leave-active in <2.1.8 */
    {
    opacity: 0;
}
</style>
