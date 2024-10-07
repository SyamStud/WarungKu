<template>
    <div class="mt-10 bg-white rounded-lg border border-gray-200 p-6">
        <div class="mb-4 w-full flex gap-2 items-center">
            <p class="text-sm font-semibold">{{ labelRange }}:</p>

            <div class="w-36">
                <Select v-model="selectedRange" @update:modelValue="fetchData">
                    <SelectTrigger>
                        <SelectValue :placeholder="placeholderRange" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectGroup>
                            <SelectItem v-for="option in rangeOptions" :key="option.value" :value="option.value">
                                {{ option.label }}
                            </SelectItem>
                        </SelectGroup>
                    </SelectContent>
                </Select>
            </div>
        </div>

        <div class="chart-container" style="position: relative; height: 60vh; width: 100%;">
            <Bar v-if="loaded && chartData" :data="chartData" :options="chartOptions" />
        </div>
        <p v-if="error" class="text-red-500">{{ error }}</p>
        <p v-if="!loaded && !error">Loading...</p>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { Bar } from 'vue-chartjs';
import { Chart as ChartJS, Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale } from 'chart.js';
import Select from './ui/select/Select.vue';
import SelectTrigger from './ui/select/SelectTrigger.vue';
import SelectValue from './ui/select/SelectValue.vue';
import SelectContent from './ui/select/SelectContent.vue';
import SelectGroup from './ui/select/SelectGroup.vue';
import SelectItem from './ui/select/SelectItem.vue';

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale);

const props = defineProps({
    apiEndpoint: {
        type: String,
        required: true
    },
    chartTitle: {
        type: String,
        default: 'Chart'
    },
    yAxisLabel: {
        type: String,
        default: 'Value'
    },
    xAxisLabel: {
        type: String,
        default: 'Time Period'
    },
    labelRange: {
        type: String,
        default: 'Select Range'
    },
    placeholderRange: {
        type: String,
        default: 'Select Range'
    },
    rangeOptions: {
        type: Array,
        default: () => [
            { value: 'weekly', label: 'Weekly' },
            { value: 'monthly', label: 'Monthly' },
            { value: 'yearly', label: 'Yearly' }
        ]
    },
    defaultRange: {
        type: String,
        default: 'weekly'
    },
    dataKey: {
        type: String,
        default: 'chartData'
    }
});

const loaded = ref(false);
const rawChartData = ref(null);
const selectedRange = ref(props.defaultRange);
const error = ref(null);

const chartData = computed(() => {
    if (!rawChartData.value) return null;

    // Attempt to parse the data regardless of its structure
    let labels = [];
    let datasets = [];

    try {
        if (Array.isArray(rawChartData.value)) {
            // If it's an array, assume it's an array of objects with a label and value
            labels = rawChartData.value.map(item => item.label);
            datasets = [{
                label: props.yAxisLabel,
                data: rawChartData.value.map(item => item.value),
                backgroundColor: 'rgba(75, 192, 192, 0.6)'
            }];
        } else if (typeof rawChartData.value === 'object') {
            // If it's an object, try to extract labels and datasets
            if (rawChartData.value.labels && rawChartData.value.datasets) {
                labels = rawChartData.value.labels;
                datasets = rawChartData.value.datasets;
            } else {
                // If not, create labels from keys and dataset from values
                labels = Object.keys(rawChartData.value);
                datasets = [{
                    label: props.yAxisLabel,
                    data: Object.values(rawChartData.value),
                    backgroundColor: 'rgba(75, 192, 192, 0.6)'
                }];
            }
        } else {
            throw new Error('Unexpected data structure');
        }
    } catch (err) {
        console.error('Error parsing chart data:', err);
        error.value = `Error parsing chart data: ${err.message}`;
        return null;
    }

    return { labels, datasets };
});

const chartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    scales: {
        y: {
            beginAtZero: true,
            title: {
                display: false,
                text: props.yAxisLabel
            }
        },
        x: {
            title: {
                display: false,
                text: props.xAxisLabel
            }
        }
    },
    plugins: {
        legend: {
            display: true,
            position: 'top',
        },
        title: {
            display: false,
            text: props.chartTitle
        }
    }
}));

const fetchData = async () => {
    loaded.value = false;
    error.value = null;
    try {
        const response = await fetch(`${props.apiEndpoint}?range=${selectedRange.value}`);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const data = await response.json();
        console.log('Received data:', data); // Log the received data for debugging
        rawChartData.value = data[props.dataKey] || data;
        if (!rawChartData.value) {
            throw new Error(`Data not found using key: ${props.dataKey}`);
        }
        loaded.value = true;
    } catch (err) {
        console.error('Error fetching data:', err);
        error.value = `Failed to load data: ${err.message}`;
    }
};

onMounted(fetchData);
</script>

<style scoped>
.chart-container {
    max-height: 600px;
}
</style>