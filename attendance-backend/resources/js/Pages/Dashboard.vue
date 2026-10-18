<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';

defineProps({
  logs: Array,
  stats: Object,
});

const videoRef = ref(null);
const canvasRef = ref(null);
const isCapturing = ref(false);
const captureStatus = ref('');
let captureInterval = null;

// Jalankan Kamera Webcam saat Komponen Dimuat
onMounted(() => {
  startCamera();
});

// Matikan Kamera saat Halaman Ditinggalkan
onUnmounted(() => {
  stopCamera();
});

const startCamera = async () => {
  try {
    const stream = await navigator.mediaDevices.getUserMedia({ video: true });
    if (videoRef.value) {
      videoRef.value.srcObject = stream;
    }
  } catch (err) {
    captureStatus.value = 'Gagal mengakses webcam: ' + err.message;
  }
};

const stopCamera = () => {
  if (videoRef.value && videoRef.value.srcObject) {
    const stream = videoRef.value.srcObject;
    const tracks = stream.getTracks();
    tracks.forEach(track => track.stop());
  }
  stopAutoCapture();
};

const toggleAutoCapture = () => {
  if (isCapturing.value) {
    stopAutoCapture();
  } else {
    startAutoCapture();
  }
};

const startAutoCapture = () => {
  isCapturing.value = true;
  captureStatus.value = 'Auto capture aktif (setiap 5 detik)...';
  // Ambil foto pertama langsung
  captureAndSend();
  // Set interval tiap 5 detik
  captureInterval = setInterval(() => {
    captureAndSend();
  }, 5000);
};

const stopAutoCapture = () => {
  isCapturing.value = false;
  captureStatus.value = 'Auto capture dihentikan.';
  if (captureInterval) {
    clearInterval(captureInterval);
    captureInterval = null;
  }
};

const captureAndSend = () => {
  if (!videoRef.value || !canvasRef.value) return;

  const video = videoRef.value;
  const canvas = canvasRef.value;
  const context = canvas.getContext('2d');

  canvas.width = video.videoWidth || 640;
  canvas.height = video.videoHeight || 480;

  // Gambar frame video saat ini ke canvas
  context.drawImage(video, 0, 0, canvas.width, canvas.height);

  // Ubah ke Blob image JPEG
  canvas.toBlob(async (blob) => {
    if (!blob) return;

    const formData = new FormData();
    formData.append('file', blob, 'webcam_frame.jpg');

    try {
      captureStatus.value = 'Mengirim frame ke AI Engine...';
      // Kirim HTTP POST ke FastAPI (Port 8001)
      const response = await fetch('http://127.0.0.1:8001/api/v1/detect-drowsiness', {
        method: 'POST',
        body: formData,
      });

      if (response.ok) {
        captureStatus.value = 'Analisis sukses!';
        // Refresh data props Inertia secara halus tanpa reload halaman
        router.reload({ only: ['logs', 'stats'] });
      } else {
        captureStatus.value = 'FastAPI Error: ' + response.statusText;
      }
    } catch (err) {
      captureStatus.value = 'Gagal terhubung ke FastAPI (Port 8001)';
    }
  }, 'image/jpeg', 0.8);
};
</script>

<template>
  <div class="min-h-screen bg-slate-100 p-6">
    <div class="max-w-7xl mx-auto space-y-6">
      
      <!-- Header -->
      <div class="flex justify-between items-center bg-white p-6 rounded-xl shadow-sm">
        <div>
          <h1 class="text-2xl font-bold text-slate-800">AI Attendance & Drowsiness Dashboard</h1>
          <p class="text-slate-500 text-sm">Monitoring presensi dan deteksi tingkat kelelahan real-time</p>
        </div>
        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800">
          ● System Live
        </span>
      </div>

      <!-- Live Webcam & Stats Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Webcam Frame -->
        <div class="lg:col-span-1 bg-white p-6 rounded-xl shadow-sm space-y-4">
          <h2 class="text-lg font-bold text-slate-800">Live Camera Scan</h2>
          
          <div class="relative bg-black rounded-lg overflow-hidden aspect-video flex items-center justify-center">
            <video ref="videoRef" autoplay playsinline class="w-full h-full object-cover"></video>
            <canvas ref="canvasRef" class="hidden"></canvas>
            
            <!-- Indicator Overlay -->
            <div v-if="isCapturing" class="absolute top-3 right-3 bg-rose-600 text-white text-[10px] font-bold px-2 py-1 rounded-md animate-pulse">
              LIVE SCANNING
            </div>
          </div>

          <p class="text-xs text-slate-500 font-mono min-h-[20px]">{{ captureStatus }}</p>

          <div class="flex gap-2">
            <button 
              @click="toggleAutoCapture"
              :class="isCapturing ? 'bg-rose-600 hover:bg-rose-700' : 'bg-indigo-600 hover:bg-indigo-700'"
              class="w-full py-2.5 px-4 text-white font-semibold rounded-lg text-sm transition"
            >
              {{ isCapturing ? 'Hentikan Auto Scan' : 'Mulai Auto Scan' }}
            </button>
            
            <button 
              @click="captureAndSend"
              :disabled="isCapturing"
              class="py-2.5 px-4 bg-slate-200 hover:bg-slate-300 disabled:opacity-50 text-slate-700 font-semibold rounded-lg text-sm transition"
            >
              Jepret
            </button>
          </div>
        </div>

        <!-- Stats Cards -->
        <div class="lg:col-span-2 grid grid-cols-1 md:grid-cols-3 gap-6 items-start">
          <div class="bg-white p-6 rounded-xl shadow-sm border-l-4 border-indigo-500">
            <p class="text-xs font-semibold text-slate-400 uppercase">Total Presensi</p>
            <p class="text-3xl font-bold text-slate-800 mt-1">{{ stats.total }}</p>
          </div>
          <div class="bg-white p-6 rounded-xl shadow-sm border-l-4 border-emerald-500">
            <p class="text-xs font-semibold text-slate-400 uppercase">Karyawan Fit</p>
            <p class="text-3xl font-bold text-emerald-600 mt-1">{{ stats.fit }}</p>
          </div>
          <div class="bg-white p-6 rounded-xl shadow-sm border-l-4 border-rose-500">
            <p class="text-xs font-semibold text-slate-400 uppercase">Terdeteksi Lelah</p>
            <p class="text-3xl font-bold text-rose-600 mt-1">{{ stats.fatigue }}</p>
          </div>
        </div>

      </div>

      <!-- Log Table -->
      <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="p-6 border-b border-slate-100">
          <h2 class="text-lg font-bold text-slate-800">Riwayat Presensi Real-Time</h2>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-slate-50 text-slate-500 text-xs uppercase border-b border-slate-100">
                <th class="p-4">Waktu</th>
                <th class="p-4">Nama / Kode</th>
                <th class="p-4">Mata Terdeteksi</th>
                <th class="p-4">Status Kebugaran</th>
                <th class="p-4">Rekomendasi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-sm text-slate-700">
              <tr v-for="log in logs" :key="log.id" class="hover:bg-slate-50">
                <td class="p-4 whitespace-nowrap">{{ new Date(log.created_at).toLocaleString('id-ID') }}</td>
                <td class="p-4 font-medium text-slate-900">{{ log.user_name }} ({{ log.user_code }})</td>
                <td class="p-4">{{ log.eyes_detected }} Mata</td>
                <td class="p-4">
                  <span 
                    :class="log.is_fatigue ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700'"
                    class="px-2.5 py-1 rounded-full text-xs font-semibold"
                  >
                    {{ log.is_fatigue ? 'LELAH / KANTUK' : 'FIT' }}
                  </span>
                </td>
                <td class="p-4 font-mono text-xs">{{ log.recommendation }}</td>
              </tr>
              <tr v-if="logs.length === 0">
                <td colspan="5" class="p-6 text-center text-slate-400">Belum ada data presensi.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>
</template>