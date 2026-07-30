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
            <input type="tel" v-model="ncPhone" placeholder="08xx-xxxx-xxxx" />
        </div>
        <div class="field">
            <label>Nama Jalan</label>
            <input
                type="text"
                v-model="ncJalan"
                placeholder="Contoh: Jl. Soekarno Hatta No. 112"
            />
        </div>
        <div class="field">
            <label>Lokasi Toko</label>
            <div id="ncLocateBox">
                <button
                    v-if="!ncCoord && !ncLocating"
                    class="btn btn-ghost"
                    @click="locateNewCustomer"
                >
                    📍 Ambil Lokasi Sekarang
                </button>
                <div v-else-if="ncLocating" class="locating">
                    <span class="spinner"></span> Mengunci sinyal GPS...
                </div>
                <div v-else class="card">
                    <div class="info-line">
                        <span class="k">Lokasi Tersimpan</span>
                        <span class="v">{{
                            ncCoord.address || `${ncCoord.lat}, ${ncCoord.lng}`
                        }}</span>
                    </div>
                    <div
                        class="info-line"
                        style="border: none; padding-top: 4px"
                    >
                        <span class="k" style="font-size: 11px; opacity: 0.6"
                            >Koordinat</span
                        >
                        <span class="v" style="font-size: 11px; opacity: 0.6"
                            >{{ ncCoord.lat }}, {{ ncCoord.lng }}</span
                        >
                    </div>
                    <button
                        class="btn btn-ghost"
                        style="margin-top: 8px; width: 100%"
                        @click="locateNewCustomer"
                    >
                        🔄 Ambil Ulang Lokasi
                    </button>
                </div>
            </div>
        </div>

        <!-- Desa/Kecamatan/Kota terisi otomatis begitu lokasi GPS didapat.
             Ditampilkan sebagai input yang bisa dikoreksi manual, karena
             hasil deteksi otomatis (OpenStreetMap) kadang kurang presisi
             tergantung wilayah. -->
        <template v-if="ncCoord">
            <div class="field">
                <label
                    >Desa/Kelurahan
                    <span style="font-size: 11px; opacity: 0.6"
                        >(otomatis dari lokasi, bisa diedit)</span
                    ></label
                >
                <input
                    type="text"
                    v-model="ncDesa"
                    placeholder="Belum terdeteksi"
                />
            </div>
            <div class="field">
                <label
                    >Kecamatan
                    <span style="font-size: 11px; opacity: 0.6"
                        >(otomatis dari lokasi, bisa diedit)</span
                    ></label
                >
                <input
                    type="text"
                    v-model="ncKecamatan"
                    placeholder="Belum terdeteksi"
                />
            </div>
            <div class="field">
                <label
                    >Kota/Kabupaten
                    <span style="font-size: 11px; opacity: 0.6"
                        >(otomatis dari lokasi, bisa diedit)</span
                    ></label
                >
                <input
                    type="text"
                    v-model="ncKota"
                    placeholder="Belum terdeteksi"
                />
            </div>
        </template>

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
        <button
            class="btn btn-primary"
            :disabled="submitting || !ncCoord"
            @click="saveCustomer"
        >
            {{
                submitting
                    ? "Menyimpan..."
                    : !ncCoord
                      ? "Ambil Lokasi Dahulu"
                      : "Simpan Customer"
            }}
        </button>
    </div>
</template>

<script setup>
import { useNewCustomerForm } from "../composables/useNewCustomerForm";
import { useAppNav } from "../composables/useAppNav";

const {
    ncName,
    ncPhone,
    ncJalan,
    ncDesa,
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
</script>
