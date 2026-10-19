<script setup>
import { computed } from 'vue';
import { Chart as ChartJS, Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale } from 'chart.js';
import { Bar } from 'vue-chartjs';

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale);

const props = defineProps({
  logs: Array,
});

// Olah data logs berdasarkan tanggal/jam untuk grafik
const chartData = computed(() => {
  // Ambil 10 data log terbaru dan urutkan dari yang tertua ke terbaru untuk tren
  const recentLogs = [...props.logs].slice(0, 10).reverse();
  
  const labels = recentLogs.map(log => new Date(log.created_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }));
  const fitCounts = recentLogs.map(log => log.is_fatigue ? 0 : 1);
  const fatigueCounts = recentLogs.map(log => log.is_fatigue ? 1 : 0);

  return {
    labels: labels.length > 0 ? labels : ['Belum Ada Data'],
    datasets: [
      {
        label: 'Fit',
        backgroundColor: '#10B981', // Emerald 500
        data: fitCounts.length > 0 ? fitCounts : [0],
      },
      {
        label: 'Lelah / Kantuk',
        backgroundColor: '#F43F5E', // Rose 500
        data: fatigueCounts.length > 0 ? fatigueCounts : [0],
      },
    ],
  };
});

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'top',
    },
  },
  scales: {
    y: {
      beginAtZero: true,
      ticks: {
        stepSize: 1,
      },
    },
  },
};
</script>

<template>
  <div class="h-64">
    <Bar :data="chartData" :options="chartOptions" />
  </div>
</template>