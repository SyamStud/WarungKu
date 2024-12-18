<template>
    <AdminLayout>
        <div id="app" ref="appContainer">
            <h1 class="text-2xl font-bold mb-10">Laporan Penjualan</h1>
            <div class="flex gap-2 items-center mb-5">
                <div class="flex gap-2 items-center">
                    <Label>Pilih Rentang Export : </Label>
                    <Popover>
                        <PopoverTrigger as-child>
                            <Button variant="outline" :class="cn(
                                'w-[280px] justify-start text-left font-normal',
                                !value && 'text-muted-foreground',
                            )">
                                <img class="me-2" width="20" height="20"
                                    src="https://img.icons8.com/color/48/calendar--v1.png" alt="calendar--v1" />
                                <template v-if="value.start">
                                    <template v-if="value.end">
                                        {{ df.format(value.start.toDate(getLocalTimeZone())) }} - {{
                                            df.format(value.end.toDate(getLocalTimeZone())) }}
                                    </template>

                                    <template v-else>
                                        {{ df.format(value.start.toDate(getLocalTimeZone())) }}
                                    </template>
                                </template>
                                <template v-else>
                                    Pick a date
                                </template>
                            </Button>
                        </PopoverTrigger>
                        <PopoverContent class="w-auto p-0">
                            <RangeCalendar v-model="value" initial-focus :number-of-months="2"
                                @update:start-value="(startDate) => value.start = startDate" />
                        </PopoverContent>
                    </Popover>
                </div>

                <Button @click="exportExcel"
                    class="w-full md:w-max bg-green-700 hover:bg-green-700 flex items-center gap-3">
                    <img class="w-5" src="https://img.icons8.com/?size=100&id=11594&format=png&color=FFFFFF" alt="">
                    Export Excel
                </Button>
            </div>

            <!-- <Button @click="download">Download</Button> -->
            <ReportCalendar apiEndpoint="/api/transactions" dataKey="transactions" />
        </div>
        
        <ReportLineChart apiEndpoint="/api/transactions/chartData" chartTitle="Total Revenue Over Time"
            yAxisLabel="transactions" xAxisLabel="Time Period" labelRange="Select Time Range"
            placeholderRange="Choose Range" :rangeOptions="[
                { value: 'weekly', label: 'Weekly' },
                { value: 'monthly', label: 'Monthly' },
                { value: 'yearly', label: 'Yearly' }
            ]" defaultRange="weekly" dataKey="transactionlist" />

    </AdminLayout>
</template>

<script setup>
import { ref } from 'vue';
import axios from 'axios';
import { html2pdf } from 'html2pdf.js';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ReportCalendar from '@/Components/ReportCalendar.vue';
import ReportLineChart from '@/Components/ReportLineChart.vue';
import { RangeCalendar } from '@/Components/ui/range-calendar'
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover'
import { cn } from '@/lib/utils';
import Button from '@/components/ui/button/Button.vue';

const appContainer = ref(null);

import {
    CalendarDate,
    DateFormatter,
    getLocalTimeZone,
} from '@internationalized/date'
import Label from '@/components/ui/label/Label.vue';

// Formatter untuk tanggal
const df = new DateFormatter('en-US', {
    dateStyle: 'medium',
})

// Menggunakan ref tanpa tipe DateValue (JavaScript tidak membutuhkan tipe)
const value = ref({
    start: new CalendarDate(new Date().getFullYear(), new Date().getMonth() + 1, new Date().getDate()),
    end: new CalendarDate(new Date().getFullYear(), new Date().getMonth() + 1, new Date().getDate()),
})

const formatDate = (date) => {
    // Format the date as YYYY-MM-DD
    return date.getFullYear() + '-' +
        ('0' + (date.getMonth() + 1)).slice(-2) + '-' +
        ('0' + date.getDate()).slice(-2);
}

const exportExcel = async () => {
    const startDateObj = value.value.start.toDate(getLocalTimeZone());
    const endDateObj = value.value.end.toDate(getLocalTimeZone());

    const startDate = formatDate(startDateObj);
    const endDate = formatDate(endDateObj);

    window.location.href = `/admin/transaction-items/export-excel?start_date=${startDate}&end_date=${endDate}`;
}

// const download = async () => {
//     const element = appContainer.value;
//     const opt = {
//         margin: 10,
//         filename: 'laporan_pendapatan.pdf',
//         image: { type: 'jpeg', quality: 0.98 },
//         html2canvas: { scale: 2 },
//         jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
//     };

//     try {
//         await html2pdf().from(element).set(opt).outputPdf('dataurlnewwindow');
//         console.log('PDF generated successfully');
//     } catch (error) {
//         console.error('Error generating PDF:', error);
//     }
// };
</script>