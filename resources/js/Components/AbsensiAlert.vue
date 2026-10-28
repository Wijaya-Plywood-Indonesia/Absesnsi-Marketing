<template>
    <!-- Alert absen masuk -->
    <div
        v-if="showAbsenMasukAlert"
        class="flex items-center gap-[10px] px-[18px] py-[10px] flex-shrink-0"
        style="background: var(--good-soft); border-bottom: 1px solid #3c4d33; cursor: pointer;"
        @click="bukaFormAbsensi('masuk')"
    >
        <span class="text-[15px] flex-shrink-0">⚠️</span>
        <div class="flex-1 min-w-0">
            <span class="text-[12.5px] font-semibold" style="color: var(--good);">Anda belum absen masuk hari ini</span>
            <span class="text-[11.5px] ml-1" style="color: var(--text-muted);">— Ketuk untuk absen</span>
        </div>
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="color: var(--good); flex-shrink: 0;">
            <path d="M9 18l6-6-6-6"/>
        </svg>
    </div>

    <!-- Alert absen keluar -->
    <div
        v-else-if="showAbsenKeluarAlert"
        class="flex items-center gap-[10px] px-[18px] py-[10px] flex-shrink-0"
        style="background: var(--danger-soft); border-bottom: 1px solid #4d2a1e; cursor: pointer;"
        @click="bukaFormAbsensi('keluar')"
    >
        <span class="text-[15px] flex-shrink-0">🕓</span>
        <div class="flex-1 min-w-0">
            <span class="text-[12.5px] font-semibold" style="color: var(--danger);">Waktunya absen pulang!</span>
            <span class="text-[11.5px] ml-1" style="color: var(--text-muted);">— Ketuk untuk absen</span>
        </div>
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="color: var(--danger); flex-shrink: 0;">
            <path d="M9 18l6-6-6-6"/>
        </svg>
    </div>
</template>

<script setup>
import { useAbsensi } from "../composables/useAbsensi";

const {
    showAbsenMasukAlert,
    showAbsenKeluarAlert,
    bukaFormAbsensi,
} = useAbsensi();
</script>
