<script setup>
import { ref, watch, onMounted, computed } from 'vue';

const props = defineProps({
    value: {
        type: [Number, String],
        required: true,
    },
    duration: {
        type: Number,
        default: 1100, // Durasi animasi gulung (ms)
    },
    prefix: {
        type: String,
        default: '',
    },
    suffix: {
        type: String,
        default: '',
    },
    delay: {
        type: Number,
        default: 150, // Jeda awal sebelum mulai berputar (ms)
    },
});

const isMounted = ref(false);

onMounted(() => {
    // Sedikit jeda agar browser memuat nilai awal 0 lalu memulai animasi gulungan
    setTimeout(() => {
        isMounted.value = true;
    }, props.delay);
});

// Watch perubahan nilai jika data disegarkan (refresh)
watch(
    () => props.value,
    () => {
        isMounted.value = false;
        setTimeout(() => {
            isMounted.value = true;
        }, 50);
    }
);

// Format karakter angka dan simbol
const characters = computed(() => {
    return String(props.value ?? 0).split('');
});

// Strip angka 0-9 diulang 2 kali untuk efek gulungan slot reel kontinu
const digitStrip = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 0, 1, 2, 3, 4, 5, 6, 7, 8, 9];

const getDigitStyle = (char, index) => {
    const num = parseInt(char, 10);
    if (isNaN(num)) return {};

    // Jika belum mounted, berhenti di 0. Saat mounted, berputar melewati 1 siklus penuh ke angka tujuan
    const targetIndex = isMounted.value ? (num + 10) : 0;
    const translateY = -(targetIndex * 5); // 1 digit = 5% dari 20 digit strip
    const staggerDelay = index * 75; // Efek berurutan dari digit kiri ke kanan

    return {
        transform: `translateY(${translateY}%)`,
        transition: `transform ${props.duration}ms cubic-bezier(0.16, 1, 0.3, 1) ${staggerDelay}ms`,
    };
};
</script>

<template>
    <span class="inline-flex items-baseline font-mono select-none overflow-hidden" :aria-label="String(value)">
        <span v-if="prefix" class="mr-0.5">{{ prefix }}</span>

        <span
            v-for="(char, idx) in characters"
            :key="idx"
            class="inline-block relative"
        >
            <!-- Jika digit angka 0-9: tampilkan reel animasi gulungan -->
            <span
                v-if="!isNaN(parseInt(char, 10))"
                class="inline-block overflow-hidden h-[1.12em] leading-[1.12em] align-baseline"
            >
                <span
                    class="inline-flex flex-col will-change-transform"
                    :style="getDigitStyle(char, idx)"
                >
                    <span
                        v-for="(digit, dIdx) in digitStrip"
                        :key="dIdx"
                        class="h-[1.12em] leading-[1.12em] flex items-center justify-center"
                    >
                        {{ digit }}
                    </span>
                </span>
            </span>

            <!-- Jika simbol non-angka (seperti . , % spasi): tampilkan statis -->
            <span v-else class="inline-block leading-[1.12em]">
                {{ char }}
            </span>
        </span>

        <span v-if="suffix" class="ml-0.5">{{ suffix }}</span>
    </span>
</template>
