<template>
    <AdminLayout>
        <div id="app" ref="appContainer">
            <h1 class="text-3xl font-bold mb-10">Laporan Penjualan</h1>
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