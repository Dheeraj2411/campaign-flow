<script setup>
import { computed } from 'vue';
import VueApexCharts from 'vue3-apexcharts';

const props = defineProps({
    data: {
        type: Array,
        required: true,
        default: () => []
    }
});

const series = computed(() => {
    return [
        {
            name: 'Sent',
            data: props.data.map(item => item.sent || 0)
        },
        {
            name: 'Delivered',
            data: props.data.map(item => item.delivered || 0)
        },
        {
            name: 'Read',
            data: props.data.map(item => item.read || 0)
        },
        {
            name: 'Failed',
            data: props.data.map(item => item.failed || 0)
        }
    ];
});

const chartOptions = computed(() => {
    return {
        chart: {
            type: 'line',
            height: 350,
            toolbar: {
                show: false
            },
            zoom: {
                enabled: false
            },
            fontFamily: 'inherit'
        },
        colors: ['#3b82f6', '#10b981', '#14b8a6', '#ef4444'], // Blue, Green, Teal, Red
        dataLabels: {
            enabled: false
        },
        stroke: {
            curve: 'smooth',
            width: 3
        },
        xaxis: {
            categories: props.data.map(item => item.date),
            labels: {
                style: {
                    colors: '#64748b',
                    fontSize: '12px',
                    fontWeight: 500,
                }
            },
            axisBorder: {
                show: false
            },
            axisTicks: {
                show: false
            }
        },
        yaxis: {
            labels: {
                style: {
                    colors: '#64748b',
                    fontSize: '12px',
                    fontWeight: 500,
                }
            }
        },
        grid: {
            borderColor: '#f1f5f9',
            strokeDashArray: 4,
            yaxis: {
                lines: {
                    show: true
                }
            }
        },
        legend: {
            position: 'top',
            horizontalAlign: 'right',
            offsetY: -10,
            markers: {
                radius: 12,
            },
            itemMargin: {
                horizontal: 10,
                vertical: 0
            }
        },
        tooltip: {
            theme: 'light',
            y: {
                formatter: function (val) {
                    return val + ' messages'
                }
            }
        }
    };
});
</script>

<template>
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 h-full w-full">
        <div class="mb-2">
            <h3 class="text-base font-bold text-slate-800">Messaging Trend (Over Time)</h3>
            <p class="text-xs text-slate-500">Volume distribution by message status over the selected dates.</p>
        </div>
        
        <div v-if="data.length === 0" class="flex flex-col items-center justify-center h-[300px] text-slate-400">
            <span class="material-symbols-outlined text-4xl mb-2 opacity-50">show_chart</span>
            <p class="text-sm font-medium">No trend data available for this range</p>
        </div>
        <div v-else class="h-[300px]">
            <VueApexCharts 
                type="line" 
                height="300" 
                :options="chartOptions" 
                :series="series" 
            />
        </div>
    </div>
</template>
