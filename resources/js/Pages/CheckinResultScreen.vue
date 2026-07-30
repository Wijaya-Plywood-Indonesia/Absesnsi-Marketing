<template>
    <div class="appbar">
        <div class="back-btn" @click="goToCheckinStep(1)">
            <svg viewBox="0 0 24 24" fill="none" stroke-width="2.4">
                <path d="M15 18l-6-6 6-6" />
            </svg>
        </div>
        <h1>Kunjungan</h1>
    </div>
    <div class="scroll">
        <span class="eyebrow">Langkah 2 dari 2</span>
        <div class="section-title" style="margin-top: 2px">Hasil Kunjungan</div>

        <div class="field">
            <label>Hasil</label>
            <select v-model="ciHasil">
                <option>Order</option>
                <option>Follow-up</option>
                <option>Komplain</option>
                <option>Toko Tutup</option>
                <option>Tidak Ada Respon</option>
            </select>
        </div>
        <div class="field">
            <label>Catatan Kunjungan</label>
            <textarea
                v-model="ciNote"
                placeholder="Ceritakan singkat hasil kunjungan..."
            ></textarea>
        </div>
        <div v-if="checkinCoord" class="address-line">
            <span class="pin">📍</span>
            <span v-if="checkinAddressLoading" class="address-text muted"
                >Mencari alamat...</span
            >
            <span v-else-if="checkinAddress" class="address-text">{{
                checkinAddress
            }}</span>
            <span v-else class="address-text muted"
                >Alamat tidak ditemukan ({{ checkinCoord.lat }},
                {{ checkinCoord.lng }})</span
            >
        </div>

        <div class="field">
            <label>Foto Toko (opsional)</label>
            <input
                type="file"
                ref="fileInput"
                accept="image/*"
                capture="environment"
                style="display: none"
                @change="onPhotoSelected"
            />
            <div
                class="photo-drop"
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
                    <span style="font-size: 11px; color: var(--accent)"
                        >Ganti Foto</span
                    >
                </template>
                <template v-else> 📷 Ambil Foto Toko / Lokasi </template>
            </div>
        </div>
        <button
            class="btn btn-primary"
            :disabled="submitting || !checkinCoord"
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
    </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
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
</style>
