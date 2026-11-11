<template>
    <!-- ══ POP-UP / BOTTOM SHEET ABSENSI ══════════════════════════════════════ -->
    <Teleport to="body">
        <div
            v-if="showAbsenMasukPopup || showAbsenKeluarPopup"
            class="fixed inset-0 z-[200] flex items-end sm:items-center justify-center"
            style="background: rgba(0, 0, 0, 0.7); backdrop-filter: blur(3px); padding: 14px;"
        >
            <div
                class="w-full max-w-[440px] overflow-hidden flex flex-col shadow-2xl"
                style="background: var(--surface); border: 1px solid var(--border); border-radius: 18px; max-height: 90vh;"
            >
                <!-- Header -->
                <div
                    class="flex items-center justify-between flex-shrink-0"
                    style="padding: 16px 18px; border-bottom: 1px solid var(--border); background: var(--surface-2);"
                >
                    <div style="flex: 1; min-width: 0;">
                        <div
                            class="font-bold flex items-center gap-2"
                            style="font-family: 'Space Grotesk', sans-serif; font-size: 16px;"
                            :style="showAbsenMasukPopup ? 'color: var(--good)' : 'color: var(--danger)'"
                        >
                            <span
                                style="width: 8px; height: 8px; border-radius: 50%; display: inline-block; flex-shrink: 0;"
                                :style="showAbsenMasukPopup ? 'background: var(--good); box-shadow: 0 0 8px var(--good);' : 'background: var(--danger); box-shadow: 0 0 8px var(--danger);'"
                            ></span>
                            <span>{{ showAbsenMasukPopup ? 'Absen Masuk' : 'Absen Pulang' }}</span>
                        </div>
                        <div style="font-size: 12px; color: var(--text-muted); margin-top: 3px;">
                            {{ showAbsenMasukPopup
                                ? 'Silakan ambil foto selfie & pastikan GPS terkunci.'
                                : 'Silakan ambil foto selfie & pastikan GPS terkunci sebelum selesai bertugas.'
                            }}
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="onDismiss"
                        class="cursor-pointer"
                        style="width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; margin-left: 12px; background: var(--surface-3); border: 1px solid var(--border); color: var(--text-muted);"
                    >
                        ✕
                    </button>
                </div>

                <!-- Content Area -->
                <div class="overflow-y-auto" style="padding: 18px; display: flex; flex-direction: column; gap: 14px;">
                    <!-- Status GPS -->
                    <div
                        v-if="absensiLocating"
                        class="card"
                        style="padding: 12px 14px; display: flex; align-items: center; gap: 10px; font-size: 12.5px; color: var(--text-muted);"
                    >
                        <span class="spinner" style="width: 16px; height: 16px; flex-shrink: 0;"></span>
                        <span>Mengunci titik sinyal GPS...</span>
                    </div>

                    <div
                        v-else-if="absensiError && !absensiCoord"
                        class="card"
                        style="padding: 12px 14px; border: 1px solid rgba(193,80,46,0.4);"
                    >
                        <div style="font-size: 12.5px; color: var(--danger); margin-bottom: 8px;">{{ absensiError }}</div>
                        <button
                            class="btn btn-ghost"
                            style="padding: 8px 12px; font-size: 12px;"
                            @click="startAbsensiGeo"
                        >
                            🔄 Coba Lagi
                        </button>
                    </div>

                    <div
                        v-if="absensiCoord"
                        class="card"
                        style="padding: 12px 14px;"
                    >
                        <div style="display: flex; align-items: flex-start; gap: 8px; border-bottom: 1px solid var(--border); padding-bottom: 8px; margin-bottom: 8px; font-size: 12.5px;">
                            <span style="flex-shrink: 0;">📍</span>
                            <span v-if="absensiAddressLoading" style="color: var(--text-muted);">Mencari alamat...</span>
                            <span v-else-if="absensiAddress" style="color: var(--text); font-weight: 500; line-height: 1.4;">{{ absensiAddress }}</span>
                            <span v-else style="color: var(--text-muted);">{{ absensiCoord.lat }}, {{ absensiCoord.lng }}</span>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 6px; font-size: 11.5px; color: var(--text-muted);">
                            <div>Akurasi: <b style="color: var(--text); font-family: 'IBM Plex Mono', monospace;">{{ absensiCoord.accuracy }} m</b></div>
                            <div>Waktu: <b style="color: var(--text); font-family: 'IBM Plex Mono', monospace;">{{ absensiCoord.time }}</b></div>
                        </div>
                    </div>

                    <!-- Ambil Foto (Mirip Form Kunjungan) -->
                    <div style="margin-top: 2px;">
                        <label style="display: block; font-size: 12px; font-weight: 600; color: var(--text-muted); margin-bottom: 8px;">
                            Foto Selfie / Presensi <span style="color: var(--danger)">*</span>
                        </label>
                        <input
                            type="file"
                            ref="fileInputRef"
                            accept="image/*"
                            capture="environment"
                            style="display: none;"
                            @change="onFotoChange"
                        />
                        <div
                            class="photo-drop cursor-pointer"
                            @click="triggerFileInput"
                            style="padding: 18px 14px; display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 110px; border-radius: 12px; background: var(--surface-2);"
                        >
                            <template v-if="absensiPhotoPreview">
                                <div style="position: relative; display: inline-block;">
                                    <img
                                        :src="absensiPhotoPreview"
                                        style="max-height: 140px; border-radius: 8px; margin-bottom: 8px; object-fit: cover;"
                                    />
                                    <div
                                        v-if="absensiIsUploading"
                                        style="position: absolute; inset: 0; background: rgba(0,0,0,0.6); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 15px;"
                                    >
                                        {{ absensiUploadProgress }}%
                                    </div>
                                </div>
                                <span
                                    v-if="!absensiIsUploading"
                                    style="font-size: 11.5px; font-weight: 600; color: var(--accent);"
                                >
                                    Ganti Foto
                                </span>
                            </template>
                            <template v-else>
                                <span style="font-size: 26px; margin-bottom: 6px;">📷</span>
                                <span style="font-size: 12px; color: var(--text-muted);">Ketuk untuk Ambil Foto Selfie (Wajib)</span>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Footer Tombol -->
                <div
                    class="flex-shrink-0"
                    style="padding: 14px 18px 18px; border-top: 1px solid var(--border); background: var(--surface-2);"
                >
                    <button
                        class="btn"
                        style="width: 100%; border-radius: 12px; font-size: 14px; font-weight: 600; padding: 13px 18px;"
                        :style="showAbsenMasukPopup
                            ? 'background: var(--good); color: #fff;'
                            : 'background: var(--danger); color: #fff;'"
                        :disabled="absensiSubmitting || absensiIsUploading || !absensiPhotoTempPath || !absensiCoord"
                        @click="onSimpan"
                    >
                        <span v-if="absensiSubmitting" class="spinner" style="width: 14px; height: 14px; margin-right: 8px; border-color: rgba(255,255,255,0.3); border-top-color: #fff;"></span>
                        <span>{{ absensiSubmitting ? 'Menyimpan Presensi...' : (showAbsenMasukPopup ? 'Simpan Absen Masuk' : 'Simpan Absen Pulang') }}</span>
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { ref, watch } from "vue";
import { useAbsensi } from "../composables/useAbsensi";

const {
    showAbsenMasukPopup,
    showAbsenKeluarPopup,
    absensiCoord,
    absensiLocating,
    absensiError,
    absensiAddress,
    absensiAddressLoading,
    absensiPhoto,
    absensiPhotoTempPath,
    absensiIsUploading,
    absensiUploadProgress,
    absensiSubmitting,
    dismissMasukPopup,
    dismissKeluarPopup,
    bukaFormAbsensi,
    startAbsensiGeo,
    uploadAbsensiFoto,
    saveAbsensi,
} = useAbsensi();

const fileInputRef = ref(null);
const absensiPhotoPreview = ref(null);

watch([showAbsenMasukPopup, showAbsenKeluarPopup], ([masuk, keluar]) => {
    if ((masuk || keluar) && !absensiCoord.value && !absensiLocating.value) {
        const tipe = masuk ? "masuk" : "keluar";
        bukaFormAbsensi(tipe);
    }
}, { immediate: true });

function triggerFileInput() {
    if (absensiIsUploading.value) return;
    fileInputRef.value?.click();
}

function onDismiss() {
    if (showAbsenMasukPopup.value) {
        dismissMasukPopup();
    } else {
        dismissKeluarPopup();
    }
    absensiPhotoPreview.value = null;
}

function onFotoChange(e) {
    const file = e.target.files?.[0];
    if (!file) return;
    absensiPhoto.value = file;
    absensiPhotoPreview.value = URL.createObjectURL(file);
    uploadAbsensiFoto(file);
}

async function onSimpan() {
    const data = await saveAbsensi();
    if (data?.success) {
        absensiPhotoPreview.value = null;
    }
}
</script>
