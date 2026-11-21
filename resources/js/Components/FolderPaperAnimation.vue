<script setup>
defineProps({
    title: {
        type: String,
        default: 'Sedang Memasukkan Data ke Dalam DPT...',
    },
    subtitle: {
        type: String,
        default: 'Sistem membaca berkas dan mengekstrak data NIM & Nama mahasiswa secara otomatis.',
    },
    fileName: {
        type: String,
        default: '',
    },
    fileSize: {
        type: [String, Number],
        default: '',
    },
    compact: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <div
        :class="[
            'flex flex-col items-center justify-center text-center transition-all select-none',
            compact ? 'p-2' : 'p-6 w-full max-w-md mx-auto'
        ]"
    >
        <!-- Kontainer Ilustrasi Animasi Map & Kertas -->
        <div class="relative flex items-center justify-center overflow-visible" :class="compact ? 'w-28 h-24' : 'w-48 h-40'">
            <svg
                viewBox="0 0 200 160"
                class="w-full h-full overflow-visible folder-scene"
                fill="none"
                xmlns="http://www.w3.org/2000/svg"
            >
                <defs>
                    <!-- Gradient Belakang Map (Dark Slate / Deep Navy) -->
                    <linearGradient id="folderBackGradient" x1="14" y1="50" x2="186" y2="148" gradientUnits="userSpaceOnUse">
                        <stop offset="0%" stop-color="#1E293B" />
                        <stop offset="100%" stop-color="#0F172A" />
                    </linearGradient>

                    <!-- Gradient Depan Map (Kantong Map yang Menutup Kertas) -->
                    <linearGradient id="folderFrontGradient" x1="14" y1="74" x2="186" y2="148" gradientUnits="userSpaceOnUse">
                        <stop offset="0%" stop-color="#334155" />
                        <stop offset="50%" stop-color="#1E293B" />
                        <stop offset="100%" stop-color="#0F172A" />
                    </linearGradient>

                    <!-- Gradient Badge Tab Map -->
                    <linearGradient id="tabAccentGradient" x1="24" y1="50" x2="70" y2="60" gradientUnits="userSpaceOnUse">
                        <stop offset="0%" stop-color="#3B82F6" />
                        <stop offset="100%" stop-color="#1D4ED8" />
                    </linearGradient>

                    <!-- Shadow Efek Kertas Melayang -->
                    <filter id="paperShadowFilter" x="-15%" y="-15%" width="130%" height="130%">
                        <feDropShadow dx="0" dy="5" stdDeviation="4" flood-color="#0F172A" flood-opacity="0.18" />
                    </filter>
                </defs>

                <!-- 1. Bayangan Bawah Map (Pulsing Shadow) -->
                <ellipse cx="100" cy="150" rx="72" ry="7" fill="rgba(15, 23, 42, 0.15)" class="anim-shadow" />

                <!-- 2. Folder Belakang (Map Back Flap) -->
                <g class="anim-folder">
                    <!-- Tab Map Kiri Atas Beraksen Biru -->
                    <path
                        d="M 22 48 L 70 48 C 76 48 80 54 84 58 L 94 58 L 22 58 Z"
                        fill="url(#tabAccentGradient)"
                    />
                    <!-- Badan Belakang Map -->
                    <path
                        d="M 24 54 L 70 54 C 76 54 80 58 84 62 L 176 62 C 182 62 186 66 186 72 L 186 138 C 186 144 182 148 176 148 L 24 148 C 18 148 14 144 14 138 L 14 64 C 14 58 18 54 24 54 Z"
                        fill="url(#folderBackGradient)"
                    />
                    <!-- Rongga Dalam Map (Bayangan Gelap Tempat Masuk Kertas) -->
                    <rect x="22" y="66" width="156" height="74" rx="4" fill="rgba(15, 23, 42, 0.45)" />
                </g>

                <!-- 3. Kertas Kedua (Secondary Paper - Meluncur Berselang) -->
                <g class="paper-group paper-delayed">
                    <rect
                        x="62"
                        y="12"
                        width="76"
                        height="86"
                        rx="6"
                        fill="#F8FAFC"
                        stroke="#CBD5E1"
                        stroke-width="1.5"
                        filter="url(#paperShadowFilter)"
                    />
                    <!-- Garis-garis data tabel pemilih -->
                    <rect x="72" y="24" width="26" height="4" rx="2" fill="#94A3B8" />
                    <rect x="72" y="34" width="56" height="3" rx="1.5" fill="#E2E8F0" />
                    <rect x="72" y="42" width="46" height="3" rx="1.5" fill="#CBD5E1" />
                    <rect x="72" y="50" width="52" height="3" rx="1.5" fill="#E2E8F0" />
                    <rect x="72" y="58" width="38" height="3" rx="1.5" fill="#CBD5E1" />
                    <rect x="72" y="66" width="48" height="3" rx="1.5" fill="#E2E8F0" />
                </g>

                <!-- 4. Kertas Utama (Primary Paper - Meluncur Masuk ke Map) -->
                <g class="paper-group paper-main">
                    <rect
                        x="54"
                        y="6"
                        width="92"
                        height="94"
                        rx="7"
                        fill="#FFFFFF"
                        stroke="#CBD5E1"
                        stroke-width="1.5"
                        filter="url(#paperShadowFilter)"
                    />
                    <!-- Header Biru Dokumen (Kop DPT) -->
                    <rect x="64" y="18" width="34" height="5" rx="2.5" fill="#2563EB" />
                    <!-- Baris Simulasi NIM dan Nama -->
                    <rect x="64" y="28" width="72" height="3.5" rx="1.75" fill="#E2E8F0" />
                    <rect x="64" y="36" width="58" height="3.5" rx="1.75" fill="#94A3B8" />
                    <rect x="64" y="44" width="66" height="3.5" rx="1.75" fill="#E2E8F0" />
                    <rect x="64" y="52" width="48" height="3.5" rx="1.75" fill="#CBD5E1" />
                    <rect x="64" y="60" width="62" height="3.5" rx="1.75" fill="#E2E8F0" />

                    <!-- Stempel / Badge Centang Verifikasi Hijau -->
                    <circle cx="128" cy="74" r="8" fill="#ECFDF5" stroke="#10B981" stroke-width="1.5" />
                    <path
                        d="M 125 74 L 127 76 L 131 72"
                        stroke="#059669"
                        stroke-width="1.5"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />
                </g>

                <!-- 5. Kantong Depan Map (Folder Front - Menutupi Bagian Bawah Kertas) -->
                <g class="anim-folder">
                    <!-- Bentuk Kantong dengan Lekukan Khas Map Dokumen -->
                    <path
                        d="M 14 84 C 14 78 18 74 24 74 L 66 74 C 72 74 78 78 84 83 C 90 88 98 90 106 90 L 176 90 C 182 90 186 94 186 100 L 186 138 C 186 144 182 148 176 148 L 24 148 C 18 148 14 144 14 138 Z"
                        fill="url(#folderFrontGradient)"
                    />
                    <!-- Garis Sorot Putih Halus pada Bibir Kantong Map -->
                    <path
                        d="M 14 84 C 14 78 18 74 24 74 L 66 74 C 72 74 78 78 84 83 C 90 88 98 90 106 90 L 176 90 C 182 90 186 94 186 100"
                        stroke="rgba(255, 255, 255, 0.35)"
                        stroke-width="1.5"
                        fill="none"
                    />
                    <!-- Label Arsip pada Map -->
                    <rect x="30" y="112" width="56" height="18" rx="4" fill="rgba(255, 255, 255, 0.12)" />
                    <rect x="36" y="117" width="28" height="3" rx="1.5" fill="rgba(255, 255, 255, 0.6)" />
                    <rect x="36" y="123" width="40" height="2.5" rx="1.25" fill="rgba(255, 255, 255, 0.35)" />
                </g>

                <!-- 6. Partikel Efek Mengambang (Sparkles) -->
                <g class="sparkle-1">
                    <circle cx="158" cy="45" r="2.5" fill="#F59E0B" />
                </g>
                <g class="sparkle-2">
                    <circle cx="44" cy="36" r="2" fill="#3B82F6" />
                </g>
                <g class="sparkle-3">
                    <circle cx="166" cy="80" r="1.5" fill="#10B981" />
                </g>
            </svg>
        </div>

        <!-- Teks Informasi & Status Ekstraksi -->
        <div v-if="!compact" class="mt-4 space-y-1.5 animate-in fade-in">
            <h4 class="text-sm sm:text-base font-bold text-slate-900 tracking-tight">
                {{ title }}
            </h4>
            <p class="text-xs text-slate-500 max-w-sm leading-relaxed mx-auto">
                {{ subtitle }}
            </p>

            <!-- Badge Berkas yang Sedang Diproses -->
            <div
                v-if="fileName"
                class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-slate-100 border border-slate-200 text-slate-800 text-xs font-mono font-medium mt-2 max-w-xs truncate"
            >
                <span class="truncate">{{ fileName }}</span>
                <span v-if="fileSize" class="text-slate-400 text-[10px] shrink-0">({{ fileSize }})</span>
            </div>

            <!-- Bilah Loading Progress yang Bergerak -->
            <div class="w-60 max-w-full h-1.5 bg-slate-100 rounded-full overflow-hidden mx-auto mt-4 border border-slate-200">
                <div class="h-full bg-slate-900 rounded-full progress-stripes"></div>
            </div>

            <div class="text-[11px] text-slate-400 pt-1">
                Mohon tunggu sejenak, data sedang diproses...
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Animasi Meluncur Kertas Utama Masuk ke dalam Map */
.paper-main {
    animation: paperSlideMain 2.4s cubic-bezier(0.25, 1, 0.5, 1) infinite;
    transform-origin: center bottom;
}

/* Animasi Meluncur Kertas Kedua Masuk Berselang */
.paper-delayed {
    animation: paperSlideDelayed 2.4s cubic-bezier(0.25, 1, 0.5, 1) infinite 1.2s;
    transform-origin: center bottom;
}

@keyframes paperSlideMain {
    0% {
        transform: translateY(-56px) scale(0.92) rotate(-1.5deg);
        opacity: 0;
    }
    18% {
        opacity: 1;
        transform: translateY(-28px) scale(0.96) rotate(-0.5deg);
    }
    60% {
        transform: translateY(22px) scale(1) rotate(0deg);
        opacity: 1;
    }
    85% {
        transform: translateY(32px) scale(0.98) rotate(0.5deg);
        opacity: 0.9;
    }
    100% {
        transform: translateY(38px) scale(0.95);
        opacity: 0;
    }
}

@keyframes paperSlideDelayed {
    0% {
        transform: translateY(-56px) scale(0.9) rotate(1.5deg);
        opacity: 0;
    }
    20% {
        opacity: 0.85;
        transform: translateY(-24px) scale(0.94) rotate(0.5deg);
    }
    65% {
        transform: translateY(18px) scale(0.98) rotate(-0.5deg);
        opacity: 0.85;
    }
    88% {
        transform: translateY(28px) scale(0.96);
        opacity: 0.7;
    }
    100% {
        transform: translateY(34px) scale(0.93);
        opacity: 0;
    }
}

/* Sedikit Goyangan / Napas Halus pada Map Saat Kertas Masuk */
.anim-folder {
    animation: folderBreathe 2.4s ease-in-out infinite;
    transform-origin: center 130px;
}

@keyframes folderBreathe {
    0%, 100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-2.5px);
    }
}

/* Bayangan Bawah Berdenyut Sesuai Goyangan Map */
.anim-shadow {
    animation: shadowPulse 2.4s ease-in-out infinite;
    transform-origin: center;
}

@keyframes shadowPulse {
    0%, 100% {
        transform: scale(1);
        opacity: 0.5;
    }
    50% {
        transform: scale(1.08);
        opacity: 0.35;
    }
}

/* Partikel Berkelap-kelip */
.sparkle-1 {
    animation: sparkleFloat 1.8s ease-in-out infinite alternate;
}
.sparkle-2 {
    animation: sparkleFloat 2.2s ease-in-out infinite 0.6s alternate;
}
.sparkle-3 {
    animation: sparkleFloat 2.0s ease-in-out infinite 1.1s alternate;
}

@keyframes sparkleFloat {
    0% {
        transform: translateY(0) scale(0.8);
        opacity: 0.3;
    }
    100% {
        transform: translateY(-4px) scale(1.2);
        opacity: 1;
    }
}

/* Bilah Progress Bergerak Halus */
.progress-stripes {
    width: 100%;
    animation: progressIndeterminate 1.5s cubic-bezier(0.65, 0, 0.35, 1) infinite;
}

@keyframes progressIndeterminate {
    0% {
        transform: translateX(-100%);
    }
    50% {
        transform: translateX(0%);
    }
    100% {
        transform: translateX(100%);
    }
}
</style>
