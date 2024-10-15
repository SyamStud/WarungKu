<template>
    <AdminLayout>
        <div id="app" ref="appContainer">
            <h1 class="text-3xl font-bold mb-10">Laporan Pembelian</h1>
            <!-- <Button @click="download">Download</Button> -->
            <ReportCalendar api-endpoint="/api/purchases" />

            <ReportLineChart apiEndpoint="/api/restocks/chartData" chartTitle="Total Revenue Over Time"
                yAxisLabel="restocks" xAxisLabel="Time Period" labelRange="Select Time Range"
                placeholderRange="Choose Range" :rangeOptions="[
                    { value: 'weekly', label: 'Weekly' },
                    { value: 'monthly', label: 'Monthly' },
                    { value: 'yearly', label: 'Yearly' }
                ]" defaultRange="monthly" dataKey="restocklist" />
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref } from 'vue';
import axios from 'axios';
import html2pdf from 'html2pdf.js';
import ReportCalendar from '@/Components/ReportCalendar.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ReportLineChart from '@/Components/ReportLineChart.vue';

const appContainer = ref(null);

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