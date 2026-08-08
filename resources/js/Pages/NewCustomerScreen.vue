<template>
    <div
        class="appbar flex-shrink-0 px-[18px] pt-[6px] pb-[16px] flex items-center gap-[10px]"
    >
        <div
            class="back-btn w-[32px] h-[32px] rounded-[10px] bg-[var(--surface-2)] border border-[var(--border)] flex items-center justify-center cursor-pointer flex-shrink-0"
            @click="nav('home')"
        >
            <svg
                class="w-4 h-4 stroke-[var(--text)]"
                viewBox="0 0 24 24"
                fill="none"
                stroke-width="2.4"
            >
                <path d="M15 18l-6-6 6-6" />
            </svg>
        </div>

        <h1>Daftar Customer Baru</h1>
    </div>

    <div class="scroll flex-1 overflow-y-auto px-[18px] pb-[24px]">
        <!-- FORM UTAMA -->
        <!-- Tidak lagi disabled berdasarkan GPS -->
        <fieldset class="form-fields border-none p-0 m-0">
            <!-- Nama Toko -->
            <div class="field mb-[16px]">
                <label
                    class="block text-[12.5px] text-[var(--text-muted)] mb-[7px] font-medium"
                >
                    Nama Toko / Usaha
                </label>

                <input
                    class="w-full bg-[var(--surface-2)] border border-[var(--border)] rounded-[10px] px-[13px] py-[12px] text-[var(--text)] text-[14.5px] outline-none"
                    type="text"
                    v-model="ncName"
                    placeholder="Contoh: Toko Bangunan Sumber Jaya"
                />
            </div>

            <!-- Nomor HP -->
            <div class="field mb-[16px]">
                <label
                    class="block text-[12.5px] text-[var(--text-muted)] mb-[7px] font-medium"
                >
                    Nomor HP
                </label>

                <input
                    class="w-full bg-[var(--surface-2)] border border-[var(--border)] rounded-[10px] px-[13px] py-[12px] text-[var(--text)] text-[14.5px] outline-none"
                    type="text"
                    v-model="ncPhone"
                    placeholder="08xx-xxxx-xxxx"
                />
            </div>

            <!-- Foto Depan Toko -->
            <div class="field mb-[16px]">
                <label
                    class="block text-[12.5px] text-[var(--text-muted)] mb-[7px] font-medium"
                >
                    Foto Depan Toko
                </label>

                <div
                    v-if="ncFotoPreview"
                    class="foto-preview-wrap flex flex-col gap-2 items-center"
                >
                    <img
                        :src="ncFotoPreview"
                        class="foto-preview w-full max-h-[220px] object-cover rounded-[12px]"
                        alt="Preview foto toko"
                    />

                    <button
                        type="button"
                        class="btn btn-ghost bg-[var(--surface-2)] text-[var(--text)] font-sans font-semibold text-[14.5px] rounded-[12px] px-[18px] py-[14px] cursor-pointer flex items-center justify-center gap-[8px] w-full"
                        @click="clearFoto"
                    >
                        🗑️ Hapus Foto
                    </button>
                </div>

                <label
                    v-else
                    class="foto-picker flex items-center justify-center p-[14px] border-[1.5px] border-dashed border-[var(--border)] rounded-[12px] text-center text-[var(--text-faint)] text-[12.5px] cursor-pointer"
                >
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

            <!-- Alamat Lengkap -->
            <div class="field mb-[16px]">
                <label
                    class="block text-[12.5px] text-[var(--text-muted)] mb-[7px] font-medium"
                >
                    Alamat Lengkap
                </label>

                <textarea
                    class="w-full bg-[var(--surface-2)] border border-[var(--border)] rounded-[10px] px-[13px] py-[12px] text-[var(--text)] text-[14.5px] outline-none resize-none h-[78px]"
                    v-model="ncAddress"
                    rows="3"
                    placeholder="Jalan, patokan, detail lokasi"
                ></textarea>
            </div>

            <!-- Kota / Kabupaten -->
            <div class="field mb-[16px]">
                <label
                    class="block text-[12.5px] text-[var(--text-muted)] mb-[7px] font-medium"
                >
                    Kota/Kabupaten
                </label>

                <select
                    class="w-full bg-[var(--surface-2)] border border-[var(--border)] rounded-[10px] px-[13px] py-[12px] text-[var(--text)] text-[14.5px] outline-none"
                    v-model="ncKota"
                >
                    <option value="" disabled>
                        {{ loadingKota ? "Memuat..." : "Pilih Kota/Kabupaten" }}
                    </option>

                    <option v-for="k in kotaOptions" :key="k" :value="k">
                        {{ k }}
                    </option>
                </select>
            </div>

            <!-- Kecamatan -->
            <div class="field mb-[16px]">
                <label
                    class="block text-[12.5px] text-[var(--text-muted)] mb-[7px] font-medium"
                >
                    Kecamatan
                </label>

                <select
                    class="w-full bg-[var(--surface-2)] border border-[var(--border)] rounded-[10px] px-[13px] py-[12px] text-[var(--text)] text-[14.5px] outline-none"
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

            <!-- Jenis Usaha -->
            <div class="field mb-[16px]">
                <label
                    class="block text-[12.5px] text-[var(--text-muted)] mb-[7px] font-medium"
                >
                    Jenis Usaha
                </label>

                <select
                    class="w-full bg-[var(--surface-2)] border border-[var(--border)] rounded-[10px] px-[13px] py-[12px] text-[var(--text)] text-[14.5px] outline-none"
                    v-model="ncJenis"
                >
                    <option>Mebel</option>
                    <option>Reseller</option>
                    <option>Toko Bangunan</option>
                    <option>Pabrik Lain</option>
                    <option value="Lainnya">Lainnya...</option>
                </select>
            </div>

            <!-- Jenis Usaha Lainnya -->
            <div v-if="ncJenis === 'Lainnya'" class="field mb-[16px]">
                <label
                    class="block text-[12.5px] text-[var(--text-muted)] mb-[7px] font-medium"
                >
                    Sebutkan Jenis Usaha
                </label>

                <input
                    class="w-full bg-[var(--surface-2)] border border-[var(--border)] rounded-[10px] px-[13px] py-[12px] text-[var(--text)] text-[14.5px] outline-none"
                    type="text"
                    v-model="ncJenisLain"
                    placeholder="Contoh: Konveksi"
                />
            </div>

            <!-- Status Customer -->
            <div
                class="card bg-[var(--surface)] border border-[var(--border)] rounded-[var(--radius)] p-[16px]"
                style="margin-bottom: 16px"
            >
                <div
                    class="info-line flex justify-between text-[13.5px]"
                    style="border: none; padding: 0"
                >
                    <span class="k text-[var(--text-muted)]">
                        Status Customer
                    </span>

                    <span
                        class="chip baru inline-flex items-center gap-[4px] text-[11px] font-semibold px-[9px] py-[4px] rounded-full border text-[var(--good)] border-[#3c4d33] bg-[var(--good-soft)] font-mono tracking-[0.01em]"
                    >
                        Baru — Otomatis
                    </span>
                </div>
            </div>
        </fieldset>

        <!-- =====================================================
             LOKASI TOKO
             ===================================================== -->

        <div class="field mb-[16px]">
            <label
                class="block text-[12.5px] text-[var(--text-muted)] mb-[7px] font-medium"
            >
                Lokasi Toko
            </label>

            <!-- Sedang mencari lokasi -->
            <div
                v-if="ncLocating"
                class="locating flex items-center gap-[10px] p-[16px] text-[13.5px] text-[var(--text-muted)]"
            >
                <span
                    class="spinner w-[16px] h-[16px] rounded-full border-2 border-[var(--border)] border-t-[var(--accent)] animate-spin flex-shrink-0"
                ></span>

                Mengunci sinyal GPS...
            </div>

            <!-- Lokasi belum didapat -->
            <div
                v-else-if="!ncCoord"
                class="card bg-[var(--surface)] border border-[var(--border)] rounded-[var(--radius)] p-[16px]"
            >
                <p style="font-size: 13px; opacity: 0.75; margin: 0 0 8px">
                    ⚠️ Lokasi belum didapat. Aktifkan GPS lalu coba lagi untuk
                    mengisi lokasi toko.
                </p>

                <button
                    class="btn btn-ghost bg-[var(--surface-2)] text-[var(--text)] font-sans font-semibold text-[14.5px] rounded-[12px] px-[18px] py-[14px] cursor-pointer flex items-center justify-center gap-[8px] w-full"
                    @click="locateNewCustomer"
                >
                    📍 Coba Ambil Lokasi Lagi
                </button>
            </div>

            <!-- Lokasi berhasil -->
            <template v-else>
                <div
                    id="ncMap"
                    class="map-box w-full h-[260px] border border-[var(--border)] rounded-xl overflow-hidden bg-[var(--surface)]"
                ></div>

                <p class="map-hint text-[12px] opacity-60 mt-[6px]">
                    Geser pin di peta kalau titik lokasi kurang tepat
                </p>

                <!-- Latitude & Longitude -->
                <div class="coord-grid grid grid-cols-2 gap-[8px] mt-[8px]">
                    <div class="field mb-[16px]" style="margin-bottom: 0">
                        <label
                            class="block text-[12.5px] text-[var(--text-muted)] mb-[7px] font-medium"
                        >
                            Latitude
                        </label>

                        <input
                            class="w-full bg-[var(--surface-2)] border border-[var(--border)] rounded-[10px] px-[13px] py-[12px] text-[var(--text)] text-[14.5px] outline-none"
                            type="text"
                            v-model="ncLat"
                            @change="onLatLngInput"
                            placeholder="-7.834384"
                        />
                    </div>

                    <div class="field mb-[16px]" style="margin-bottom: 0">
                        <label
                            class="block text-[12.5px] text-[var(--text-muted)] mb-[7px] font-medium"
                        >
                            Longitude
                        </label>

                        <input
                            class="w-full bg-[var(--surface-2)] border border-[var(--border)] rounded-[10px] px-[13px] py-[12px] text-[var(--text)] text-[14.5px] outline-none"
                            type="text"
                            v-model="ncLng"
                            @change="onLatLngInput"
                            placeholder="112.692398"
                        />
                    </div>
                </div>

                <!-- Ambil ulang GPS -->
                <button
                    class="btn btn-ghost bg-[var(--surface-2)] text-[var(--text)] font-sans font-semibold text-[14.5px] rounded-[12px] px-[18px] py-[14px] cursor-pointer flex items-center justify-center gap-[8px] w-full"
                    style="margin-top: 8px; width: 100%"
                    @click="locateNewCustomer"
                >
                    🔄 Ambil Ulang Lokasi GPS
                </button>
            </template>
        </div>

        <!-- =====================================================
             SUBMIT
             ===================================================== -->

        <button
            class="btn btn-primary font-sans font-semibold text-[14.5px] rounded-[12px] border-none px-[18px] py-[14px] cursor-pointer flex items-center justify-center gap-[8px] w-full bg-[var(--accent)] text-[var(--accent-ink)] active:scale-[0.98] disabled:bg-[var(--border)] disabled:text-[var(--text-faint)] disabled:cursor-not-allowed"
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
import { ref, watch, nextTick, onBeforeUnmount, onMounted } from "vue";

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

/* =========================================================
   ON MOUNTED
   ========================================================= */

onMounted(() => {
    /*
     * Kota langsung dimuat ketika halaman dibuka.
     */
    loadKota();

    /*
     * GPS hanya sebagai bantuan awal.
     */
    if (!ncCoord.value) {
        locateNewCustomer();
    }

    /*
     * Jika ncKota sudah memiliki nilai ketika
     * halaman pertama kali dibuka, langsung load
     * Kecamatan.
     *
     * Ini penting karena tidak ada event @change
     * yang otomatis dipanggil saat initial state.
     */
    if (ncKota.value) {
        loadKecamatan(ncKota.value);
    }
});

/* =========================================================
   HELPER
   ========================================================= */

function toNum(v) {
    const n = typeof v === "number" ? v : parseFloat(v);

    return Number.isFinite(n) ? n : 0;
}

/* =========================================================
   LATITUDE / LONGITUDE
   ========================================================= */

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

    ncCoord.value = {
        ...(ncCoord.value || {}),
        lat,
        lng,
    };
}

/* =========================================================
   FOTO TOKO
   ========================================================= */

const ncFoto = ref(null);
const ncFotoPreview = ref("");

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

/* =========================================================
   SUBMIT
   ========================================================= */

async function onSubmit() {
    await saveCustomer(ncFoto.value);

    clearFoto();
}

/* =========================================================
   WILAYAH
   ========================================================= */

const kotaOptions = ref([]);
const kecamatanOptions = ref([]);

const loadingKota = ref(false);
const loadingKecamatan = ref(false);

/*
 * Load Kota/Kabupaten.
 */
async function loadKota() {
    /*
     * Jangan request ulang jika sudah ada data.
     */
    if (kotaOptions.value.length > 0) {
        return;
    }

    loadingKota.value = true;

    try {
        const res = await fetch("/api/wilayah/kota");

        if (!res.ok) {
            throw new Error(`HTTP ${res.status}`);
        }

        const data = await res.json();

        kotaOptions.value = Object.keys(data);
    } catch (e) {
        console.error("Gagal memuat daftar kota", e);
    } finally {
        loadingKota.value = false;
    }
}

/*
 * Load Kecamatan berdasarkan Kota.
 */
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

        if (!res.ok) {
            throw new Error(`HTTP ${res.status}`);
        }

        const data = await res.json();

        kecamatanOptions.value = Object.keys(data);
    } catch (e) {
        console.error("Gagal memuat daftar kecamatan", e);

        kecamatanOptions.value = [];
    } finally {
        loadingKecamatan.value = false;
    }
}

/*
 * Tidak perlu lagi memanggil loadKecamatan()
 * dari event change secara manual.
 *
 * Perubahan ncKota akan ditangkap watcher.
 */
function onKotaChange() {
    ncKecamatan.value = "";
}

/*
 * WATCH KOTA
 *
 * Ini yang memperbaiki bug first load.
 *
 * Jika ncKota:
 * - sudah memiliki nilai ketika component dibuat
 * - berubah karena user memilih kota
 *
 * maka Kecamatan akan otomatis dimuat.
 */
watch(
    ncKota,
    (kota) => {
        if (!kota) {
            kecamatanOptions.value = [];
            ncKecamatan.value = "";
            return;
        }

        /*
         * Reset Kecamatan ketika Kota berubah.
         */
        ncKecamatan.value = "";

        /*
         * Load Kecamatan untuk Kota tersebut.
         */
        loadKecamatan(kota);
    },
    {
        immediate: true,
    },
);

/* =========================================================
   LEAFLET MAP
   ========================================================= */

let map = null;
let marker = null;

function initMap() {
    if (!ncCoord.value) return;

    nextTick(() => {
        const el = document.getElementById("ncMap");

        if (!el) return;

        const lat = toNum(ncCoord.value.lat);

        const lng = toNum(ncCoord.value.lng);

        /*
         * Jika map sudah ada,
         * update posisi saja.
         */
        if (map) {
            const latlng = [lat, lng];

            map.setView(latlng, map.getZoom());

            if (marker) {
                marker.setLatLng(latlng);
            }

            map.invalidateSize();

            return;
        }

        /*
         * Buat Leaflet map.
         */
        map = L.map("ncMap").setView([lat, lng], 16);

        L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
            attribution: "&copy; OpenStreetMap contributors",
        }).addTo(map);

        /*
         * Marker draggable.
         */
        marker = L.marker([lat, lng], {
            draggable: true,
        }).addTo(map);

        /*
         * Marker digeser.
         */
        marker.on("dragend", () => {
            const pos = marker.getLatLng();

            ncCoord.value = {
                ...ncCoord.value,
                lat: pos.lat,
                lng: pos.lng,
            };

            syncLatLngFromCoord();
        });

        /*
         * Map diklik.
         */
        map.on("click", (e) => {
            marker.setLatLng(e.latlng);

            ncCoord.value = {
                ...ncCoord.value,
                lat: e.latlng.lat,
                lng: e.latlng.lng,
            };

            syncLatLngFromCoord();
        });

        /*
         * Pastikan ukuran map benar
         * setelah DOM selesai dirender.
         */
        setTimeout(() => {
            if (map) {
                map.invalidateSize();
            }
        }, 200);
    });
}

/*
 * Hapus Leaflet map.
 */
function destroyMap() {
    if (map) {
        map.remove();

        map = null;
        marker = null;
    }
}

/* =========================================================
   WATCH GPS
   ========================================================= */

watch(ncCoord, (val) => {
    if (val) {
        syncLatLngFromCoord();
        initMap();
    } else {
        destroyMap();
    }
});

/* =========================================================
   CLEANUP
   ========================================================= */

onBeforeUnmount(() => {
    destroyMap();

    if (ncFotoPreview.value) {
        URL.revokeObjectURL(ncFotoPreview.value);
    }
});
</script>
