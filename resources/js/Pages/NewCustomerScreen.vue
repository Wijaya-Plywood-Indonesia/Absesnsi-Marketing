<template>
    <div class="appbar">
        <div class="back-btn" @click="nav('home')">
            <svg viewBox="0 0 24 24" fill="none" stroke-width="2.4">
                <path d="M15 18l-6-6 6-6" />
            </svg>
        </div>
        <h1>Daftar Customer Baru</h1>
    </div>
    <div class="scroll">
        <fieldset :disabled="!ncCoord" class="form-fields">
            <div class="field">
                <label>Nama Toko / Usaha</label>
                <input
                    type="text"
                    v-model="ncName"
                    placeholder="Contoh: Toko Bangunan Sumber Jaya"
                />
            </div>
            <div class="field">
                <label>Nomor HP</label>
                <input
                    type="tel"
                    v-model="ncPhone"
                    placeholder="08xx-xxxx-xxxx"
                />
            </div>

            <div class="field">
                <label>Foto Depan Toko</label>

                <div v-if="ncFotoPreview" class="foto-preview-wrap">
                    <img
                        :src="ncFotoPreview"
                        class="foto-preview"
                        alt="Preview foto toko"
                    />
                    <button type="button" class="btn btn-ghost" @click="clearFoto">
                        🗑️ Hapus Foto
                    </button>
                </div>

                <label v-else class="foto-picker">
                    <input
                        type="file"
                        accept="image/*"
                        capture="environment"
                        @change="onFotoChange"
                        style="display: none"
                    />
                    📷 Ambil / Pilih Foto Toko
                </label>
            </div>

            <div class="field">
                <label>Alamat Lengkap</label>
                <textarea
                    v-model="ncAddress"
                    rows="3"
                    placeholder="Jalan, patokan, detail lokasi"
                ></textarea>
            </div>

            <div class="field">
                <label>Kota/Kabupaten</label>
                <select v-model="ncKota" @change="onKotaChange">
                    <option value="" disabled>
                        {{ loadingKota ? "Memuat..." : "Pilih Kota/Kabupaten" }}
                    </option>
                    <option v-for="k in kotaOptions" :key="k" :value="k">
                        {{ k }}
                    </option>
                </select>
            </div>
            <div class="field">
                <label>Kecamatan</label>
                <select
                    v-model="ncKecamatan"
                    :disabled="!ncKota || loadingKecamatan"
                >
                    <option value="" disabled>
                        {{
                            loadingKecamatan
                                ? "Memuat..."
                                : !ncKota
                                  ? "Pilih Kota dahulu"
                                  : "Pilih Kecamatan"
                        }}
                    </option>
                    <option
                        v-for="kec in kecamatanOptions"
                        :key="kec"
                        :value="kec"
                    >
                        {{ kec }}
                    </option>
                </select>
            </div>

            <div class="field">
                <label>Jenis Usaha</label>
                <select v-model="ncJenis">
                    <option>Mebel</option>
                    <option>Reseller</option>
                    <option>Toko Bangunan</option>
                    <option>Pabrik Lain</option>
                    <option value="Lainnya">Lainnya...</option>
                </select>
            </div>
            <div v-if="ncJenis === 'Lainnya'" class="field">
                <label>Sebutkan Jenis Usaha</label>
                <input
                    type="text"
                    v-model="ncJenisLain"
                    placeholder="Contoh: Konveksi"
                />
            </div>

            <div class="card" style="margin-bottom: 16px">
                <div class="info-line" style="border: none; padding: 0">
                    <span class="k">Status Customer</span>
                    <span class="chip baru">Baru — Otomatis</span>
                </div>
            </div>
        </fieldset>

        <div class="field">
            <label>Lokasi Toko</label>

            <div v-if="ncLocating" class="locating">
                <span class="spinner"></span> Mengunci sinyal GPS...
            </div>

            <div v-else-if="!ncCoord" class="card">
                <p style="font-size: 13px; opacity: 0.75; margin: 0 0 8px">
                    ⚠️ Lokasi belum didapat. Aktifkan GPS lalu coba lagi untuk
                    mengisi form.
                </p>
                <button class="btn btn-ghost" @click="locateNewCustomer">
                    📍 Coba Ambil Lokasi Lagi
                </button>
            </div>

            <template v-else>
                <div id="ncMap" class="map-box"></div>
                <p class="map-hint">
                    Geser pin di peta kalau titik lokasi kurang tepat
                </p>

                <div class="coord-grid">
                    <div class="field" style="margin-bottom: 0">
                        <label>Latitude</label>
                        <input
                            type="text"
                            v-model="ncLat"
                            @change="onLatLngInput"
                            placeholder="-7.834384"
                        />
                    </div>
                    <div class="field" style="margin-bottom: 0">
                        <label>Longitude</label>
                        <input
                            type="text"
                            v-model="ncLng"
                            @change="onLatLngInput"
                            placeholder="112.692398"
                        />
                    </div>
                </div>

                <button
                    class="btn btn-ghost"
                    style="margin-top: 8px; width: 100%"
                    @click="locateNewCustomer"
                >
                    🔄 Ambil Ulang Lokasi GPS
                </button>
            </template>
        </div>



        <button
            class="btn btn-primary"
            :disabled="submitting || !ncCoord || !ncKota || !ncKecamatan"
            @click="onSubmit"
        >
            {{
                submitting
                    ? "Menyimpan..."
                    : !ncCoord
                      ? "Ambil Lokasi Dahulu"
                      : !ncKota || !ncKecamatan
                        ? "Lengkapi Kota & Kecamatan"
                        : "Simpan Customer"
            }}
        </button>
    </div>
</template>

<script setup>
import {
    ref,
    computed,
    watch,
    nextTick,
    onBeforeUnmount,
    onMounted,
} from "vue";
import L from "leaflet";
import "leaflet/dist/leaflet.css";
import { useNewCustomerForm } from "../composables/useNewCustomerForm";
import { useAppNav } from "../composables/useAppNav";

const {
    ncName,
    ncPhone,
    ncAddress,
    ncKecamatan,
    ncKota,
    ncCoord,
    ncLocating,
    ncJenis,
    ncJenisLain,
    submitting,
    locateNewCustomer,
    saveCustomer,
} = useNewCustomerForm();
const { nav } = useAppNav();

onMounted(() => {
    if (!ncCoord.value) locateNewCustomer();
});

/* ---------------- Helper: normalisasi angka ---------------- */

function toNum(v) {
    const n = typeof v === "number" ? v : parseFloat(v);
    return Number.isFinite(n) ? n : 0;
}

/* Latitude/Longitude sebagai input yang bisa diedit manual,
   selalu sinkron dua arah dengan ncCoord (mirip Filament) */

const ncLat = ref("");
const ncLng = ref("");

function syncLatLngFromCoord() {
    if (ncCoord.value) {
        ncLat.value = toNum(ncCoord.value.lat).toFixed(6);
        ncLng.value = toNum(ncCoord.value.lng).toFixed(6);
    }
}

function onLatLngInput() {
    const lat = toNum(ncLat.value);
    const lng = toNum(ncLng.value);
    ncCoord.value = { ...(ncCoord.value || {}), lat, lng };
}

/* ---------------- Foto toko ---------------- */

const ncFoto = ref(null); // File object yang akan dikirim
const ncFotoPreview = ref(""); // Object URL untuk preview

function onFotoChange(e) {
    const file = e.target.files[0];
    if (!file) return;

    ncFoto.value = file;

    if (ncFotoPreview.value) {
        URL.revokeObjectURL(ncFotoPreview.value);
    }
    ncFotoPreview.value = URL.createObjectURL(file);
}

function clearFoto() {
    if (ncFotoPreview.value) {
        URL.revokeObjectURL(ncFotoPreview.value);
    }
    ncFoto.value = null;
    ncFotoPreview.value = "";
}

/* Bungkus saveCustomer supaya ikut kirim file foto.
   Sesuaikan dengan signature asli saveCustomer() di composable. */
async function onSubmit() {
    await saveCustomer(ncFoto.value);
    clearFoto();
}

/* ---------------- Wilayah dropdown ---------------- */

const kotaOptions = ref([]);
const kecamatanOptions = ref([]);
const loadingKota = ref(false);
const loadingKecamatan = ref(false);

async function loadKota() {
    loadingKota.value = true;
    try {
        const res = await fetch("/api/wilayah/kota");
        const data = await res.json();
        kotaOptions.value = Object.keys(data);
    } catch (e) {
        console.error("Gagal memuat daftar kota", e);
    } finally {
        loadingKota.value = false;
    }
}

async function loadKecamatan(kota) {
    if (!kota) {
        kecamatanOptions.value = [];
        return;
    }
    loadingKecamatan.value = true;
    try {
        const res = await fetch(
            `/api/wilayah/kecamatan?kota=${encodeURIComponent(kota)}`,
        );
        const data = await res.json();
        kecamatanOptions.value = Object.keys(data);
    } catch (e) {
        console.error("Gagal memuat daftar kecamatan", e);
    } finally {
        loadingKecamatan.value = false;
    }
}

function onKotaChange() {
    ncKecamatan.value = "";
    loadKecamatan(ncKota.value);
}

/* ---------------- Leaflet map ---------------- */

let map = null;
let marker = null;

function initMap() {
    if (!ncCoord.value) return;

    nextTick(() => {
        const el = document.getElementById("ncMap");
        if (!el) return;

        const lat = toNum(ncCoord.value.lat);
        const lng = toNum(ncCoord.value.lng);

        if (map) {
            const latlng = [lat, lng];
            map.setView(latlng, map.getZoom());
            marker.setLatLng(latlng);
            return;
        }

        map = L.map("ncMap").setView([lat, lng], 16);

        L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
            attribution: "&copy; OpenStreetMap contributors",
        }).addTo(map);

        marker = L.marker([lat, lng], { draggable: true }).addTo(map);

        marker.on("dragend", () => {
            const pos = marker.getLatLng();
            ncCoord.value = {
                ...ncCoord.value,
                lat: pos.lat,
                lng: pos.lng,
            };
            syncLatLngFromCoord();
        });

        map.on("click", (e) => {
            marker.setLatLng(e.latlng);
            ncCoord.value = {
                ...ncCoord.value,
                lat: e.latlng.lat,
                lng: e.latlng.lng,
            };
            syncLatLngFromCoord();
        });

        setTimeout(() => map.invalidateSize(), 200);
    });
}

function destroyMap() {
    if (map) {
        map.remove();
        map = null;
        marker = null;
    }
}

watch(ncCoord, (val) => {
    if (val) {
        if (kotaOptions.value.length === 0) loadKota();
        if (ncKota.value) loadKecamatan(ncKota.value);
        syncLatLngFromCoord();
        initMap();
    } else {
        destroyMap();
    }
});

onBeforeUnmount(() => {
    destroyMap();
    if (ncFotoPreview.value) {
        URL.revokeObjectURL(ncFotoPreview.value);
    }
});
</script>

<style scoped>
.map-box {
    width: 100%;
    height: 260px;
    border-radius: 12px;
    overflow: hidden;
}
.map-hint {
    font-size: 12px;
    opacity: 0.6;
    margin-top: 6px;
}
.coord-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
    margin-top: 8px;
}
.form-fields {
    border: none;
    padding: 0;
    margin: 0;
}
.form-fields:disabled,
.form-fields[disabled] {
    opacity: 0.45;
}
.foto-picker {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 14px;
    border: 1.5px dashed rgba(0, 0, 0, 0.25);
    border-radius: 12px;
    font-size: 14px;
    cursor: pointer;
    text-align: center;
}
.foto-preview-wrap {
    display: flex;
    flex-direction: column;
    gap: 8px;
    align-items: center;
}
.foto-preview {
    width: 100%;
    max-height: 220px;
    object-fit: cover;
    border-radius: 12px;
}
</style>
