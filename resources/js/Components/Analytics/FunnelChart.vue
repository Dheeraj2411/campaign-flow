<script setup>
import { computed } from 'vue';
import VueApexCharts from 'vue3-apexcharts';

const props = defineProps({
    data: {
        type: Object,
        required: true,
    }
});

const chartOptions = computed(() => ({
    chart: {
        type: 'bar',
        height: 350,
        toolbar: { show: false }
    },
    plotOptions: {
        bar: {
            horizontal: true,
            distributed: true,
            borderRadius: 4,
            dataLabels: {
                position: 'center'
            }
        }
    },
    dataLabels: {
        enabled: true,
        textAnchor: 'middle',
        style: { colors: ['#fff'], fontSize: '14px', fontWeight: 'bold' },
        formatter: function (val) {
            const sent = props.data?.sent || 1;
            const percent = ((val / sent) * 100).toFixed(1);
            return `${val} (${percent}%)`;
        },
        offsetX: 0,
        dropShadow: { enabled: true }
    },
    xaxis: {
        categories: ['Sent', 'Delivered', 'Read', 'Failed'],
    },
    colors: ['#3b82f6', '#10b981', '#14b8a6', '#ef4444'],
    legend: { show: false },
    tooltip: {
        y: {
            formatter: (val) => val
        }
    }
}));

const series = computed(() => [{
    name: 'Messages',
    data: [
        props.data?.sent || 0,
        props.data?.delivered || 0,
        props.data?.read || 0,
        props.data?.failed || 0
    ]
}]);
</script>

<template>
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Message Delivery Funnel</h3>
        <VueApexCharts
            type="bar"
            height="350"
            :options="chartOptions"
            :series="series"
        />
    </div>
</template>
