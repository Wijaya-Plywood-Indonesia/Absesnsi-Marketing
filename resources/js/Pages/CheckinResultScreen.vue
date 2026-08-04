<template>
    <div class="appbar flex-shrink-0 px-[18px] pt-[6px] pb-[16px] flex items-center gap-[10px]">
        <div class="w-[32px] h-[32px] rounded-[10px] bg-[var(--surface-2)] border border-[var(--border)] flex items-center justify-center cursor-pointer flex-shrink-0" @click="goToCheckinStep(1)">
            <svg class="w-4 h-4 stroke-[var(--text)]" viewBox="0 0 24 24" fill="none" stroke-width="2.4">
                <path d="M15 18l-6-6 6-6" />
            </svg>
        </div>
        <h1 class="font-['Space_Grotesk'] text-[19px] font-semibold tracking-[-0.01em]">Kunjungan</h1>
    </div>
    <div class="scroll flex-1 overflow-y-auto px-[18px] pb-[24px]">
        <span class="font-mono text-[10.5px] tracking-[0.13em] uppercase text-[var(--accent)] mb-[4px] block">Langkah 2 dari 2</span>
        <div class="font-['Space_Grotesk'] text-[15px] font-semibold mt-[22px] mb-[10px] style='margin-top: 2px'">Hasil Kunjungan</div>

        <div class="field mb-[16px]">
            <label class="block text-[12.5px] text-[var(--text-muted)] mb-[7px] font-medium">Hasil</label>
            <select class="w-full bg-[var(--surface-2)] border border-[var(--border)] rounded-[10px] px-[13px] py-[12px] text-[var(--text)] text-[14.5px] outline-none" v-model="ciHasil">
                <option>Order</option>
                <option>Follow-up</option>
                <option>Komplain</option>
                <option>Toko Tutup</option>
                <option>Tidak Ada Respon</option>
            </select>
        </div>
        <div class="field mb-[16px]">
            <label class="block text-[12.5px] text-[var(--text-muted)] mb-[7px] font-medium">Catatan Kunjungan</label>
            <textarea
                class="w-full bg-[var(--surface-2)] border border-[var(--border)] rounded-[10px] px-[13px] py-[12px] text-[var(--text)] text-[14.5px] outline-none resize-none h-[78px]"
                v-model="ciNote"
                placeholder="Ceritakan singkat hasil kunjungan..."
            ></textarea>
        </div>
        <div v-if="checkinCoord" class="flex gap-2 items-start text-xs pb-[14px] mb-[18px] border-b border-[var(--border)]">
            <span class="shrink-0">📍</span>
            <span v-if="checkinAddressLoading" class="text-[var(--text-muted)]"
                >Mencari alamat...</span
            >
            <span v-else-if="checkinAddress" class="text-[var(--text)]">{{
                checkinAddress
            }}</span>
            <span v-else class="text-[var(--text-muted)]"
                >Alamat tidak ditemukan ({{ checkinCoord.lat }},
                {{ checkinCoord.lng }})</span
            >
        </div>

        <div class="field mb-[16px]">
            <label class="block text-[12.5px] text-[var(--text-muted)] mb-[7px] font-medium">Foto Toko <span style="color: var(--danger)">*</span></label>
            <input
                type="file"
                ref="fileInput"
                accept="image/*"
                capture="environment"
                style="display: none"
                @change="onPhotoSelected"
            />
            <div
                class="photo-drop border-[1.5px] border-dashed border-[var(--border)] rounded-[12px] p-[20px] text-center text-[var(--text-faint)] text-[12.5px] cursor-pointer"
                :class="isEditMode ? 'opacity-80' : ''"
                @click="triggerFileInput"
                style="
                    padding: 8px;
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    justify-content: center;
                    min-height: 80px;
                "
            >
                <template v-if="photoPreview">
                    <img
                        :src="photoPreview"
                        style="
                            max-height: 120px;
                            border-radius: 8px;
                            margin-bottom: 8px;
                        "
                    />
                    <span
                        v-if="!isEditMode"
                        style="font-size: 11px; color: var(--accent)"
                        >Ganti Foto</span
                    >
                    <span
                        v-else
                        style="font-size: 11px; color: var(--text-muted)"
                        >🔒 Foto tidak dapat diubah</span
                    >
                </template>
                <template v-else>
                    📷 Ambil Foto Toko / Lokasi (wajib)
                </template>
            </div>
        </div>
        <button
            class="btn btn-primary font-sans font-semibold text-[14.5px] rounded-[12px] border-none px-[18px] py-[14px] cursor-pointer flex items-center justify-center gap-[8px] w-full bg-[var(--accent)] text-[var(--accent-ink)] active:scale-[0.98] disabled:bg-[var(--border)] disabled:text-[var(--text-faint)] disabled:cursor-not-allowed"
            :disabled="submitting || !checkinCoord || !photoPreview"
            @click="handleSaveCheckin"
        >
            {{ submitting ? "Menyimpan..." : "Simpan Kunjungan" }}
        </button>
        <div
            v-if="!checkinCoord"
            style="
                font-size: 12px;
                color: var(--danger);
                margin-top: 8px;
                text-align: center;
            "
        >
            Lokasi belum diambil. Silakan kembali ke langkah 1.
        </div>
        <div
            v-else-if="!photoPreview"
            style="
                font-size: 12px;
                color: var(--danger);
                margin-top: 8px;
                text-align: center;
            "
        >
            Foto toko wajib diambil sebelum menyimpan kunjungan.
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, watch } from "vue";
import { useCheckinFlow } from "../composables/useCheckinFlow";
import { useSuccessStamp } from "../composables/useSuccessStamp";
import { useAppNav } from "../composables/useAppNav";

const {
    checkinCoord,
    checkinAddress,
    checkinAddressLoading,
    ciHasil,
    ciNote,
    checkinPhoto,
    existingPhotoUrl,
    isEditMode,
    submitting,
    enterCheckinResult,
    goToCheckinStep,
    saveCheckin,
} = useCheckinFlow();
const { setSuccessStamp } = useSuccessStamp();
const { nav } = useAppNav();

const fileInput = ref(null);
const photoPreview = ref(null);

function triggerFileInput() {
    // Kalau sedang edit kunjungan yang sudah ada, foto terkunci (tidak bisa diganti).
    if (isEditMode.value) return;
    fileInput.value.click();
}

function onPhotoSelected(e) {
    const file = e.target.files[0];
    if (file) {
        photoPreview.value = URL.createObjectURL(file);
        checkinPhoto.value = file;
    }
}

async function handleSaveCheckin() {
    const result = await saveCheckin();
    if (result.success) {
        setSuccessStamp({
            title: result.title,
            isOrder: result.isOrder,
            details: result.details,
            action: () => nav("home"),
        });
        nav("successStamp");
    }
}

// Entry point step 2: cek kunjungan hari ini & siapkan alamat.
onMounted(() => {
    enterCheckinResult();
});

// existingPhotoUrl datang dari fetchTodayVisit() (async, lewat API), jadi
// belum tentu terisi tepat saat onMounted jalan — pakai watch supaya preview
// tetap ke-set begitu data visit selesai di-fetch.
watch(existingPhotoUrl, (url) => {
    if (url && !checkinPhoto.value) {
        photoPreview.value = url;
    }
});
</script>

<style scoped>
.address-line {
    display: flex;
    align-items: flex-start;
    gap: 6px;
    font-size: 13px;
    font-weight: 600;
    margin-bottom: 14px;
}
.address-line .pin {
    flex-shrink: 0;
}
.address-line .address-text.muted {
    font-weight: 400;
    color: var(--text-muted);
}
.photo-drop-locked {
    cursor: not-allowed;
    opacity: 0.85;
}
</style>
