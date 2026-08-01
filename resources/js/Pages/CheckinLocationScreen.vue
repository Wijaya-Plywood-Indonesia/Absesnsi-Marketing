<template>
    <div class="appbar">
        <div class="back-btn" @click="nav('customerDetail')">
            <svg viewBox="0 0 24 24" fill="none" stroke-width="2.4">
                <path d="M15 18l-6-6 6-6" />
            </svg>
        </div>
        <h1>Kunjungan</h1>
    </div>
    <div class="scroll">
        <span class="eyebrow">Langkah 1 dari 2</span>
        <div class="section-title mt-[2px]">Ambil Lokasi Kunjungan</div>

        <div
            ref="mapEl"
            class="map-box h-[220px] rounded-xl overflow-hidden"
            id="checkinMap"
        >
            <div v-if="checkinLocating" class="locating">
                <span class="spinner"></span> Mengunci sinyal GPS...
            </div>
        </div>

        <div v-if="checkinError && !checkinCoord" class="card mt-3">
            <div class="text-[13px] text-[var(--danger)] mb-2.5">
                {{ checkinError }}
            </div>
            <button class="btn btn-ghost" @click="startCheckinGeo">
                🔄 Coba Lagi
            </button>
        </div>

        <div v-if="checkinCoord" class="flex flex-col gap-3 mt-3">
            <div class="card coord-card">
                <div
                    class="flex flex-row items-start gap-2 text-[13px] font-semibold mb-2.5 pb-2.5 border-b border-[var(--border)]"
                >
                    <span class="shrink-0">📍</span>
                    <span
                        v-if="checkinAddressLoading"
                        class="text-[var(--text-muted)] font-normal"
                        >Mencari alamat...</span
                    >
                    <span v-else-if="checkinAddress">{{ checkinAddress }}</span>
                    <span v-else class="text-[var(--text-muted)] font-normal"
                        >Alamat tidak ditemukan, pakai koordinat di bawah.</span
                    >
                </div>
                <div class="grid grid-cols-2 gap-x-3 gap-y-3">
                    <div class="flex flex-col gap-1">
                        <div class="text-[11px] text-[var(--text-muted)]">
                            Latitude
                        </div>
                        <div class="text-sm font-medium">
                            {{ checkinCoord.lat }}
                        </div>
                    </div>
                    <div class="flex flex-col gap-1">
                        <div class="text-[11px] text-[var(--text-muted)]">
                            Longitude
                        </div>
                        <div class="text-sm font-medium">
                            {{ checkinCoord.lng }}
                        </div>
                    </div>
                    <div class="flex flex-col gap-1">
                        <div class="text-[11px] text-[var(--text-muted)]">
                            Akurasi
                        </div>
                        <div class="text-sm font-medium">
                            {{ checkinCoord.accuracy }} m
                        </div>
                    </div>
                    <div class="flex flex-col gap-1">
                        <div class="text-[11px] text-[var(--text-muted)]">
                            Jam Check-in
                        </div>
                        <div class="text-sm font-medium">
                            {{ checkinCoord.time }}
                        </div>
                    </div>
                </div>
            </div>

            <div
                v-if="distanceToStore !== null"
                class="card border-l-[3px]"
                :class="
                    isWithinRadius
                        ? 'border-l-[#22c55e]'
                        : 'border-l-[var(--danger,#ef4444)]'
                "
            >
                <div class="text-[13px] font-semibold">
                    {{
                        isWithinRadius
                            ? "✅ Dalam radius toko"
                            : "⚠️ Terlalu jauh dari toko"
                    }}
                </div>
                <div class="text-xs text-[var(--text-muted)] mt-0.5">
                    Jarak ke toko: {{ Math.round(distanceToStore) }} m (maks.
                    {{ CHECKIN_RADIUS_METERS }} m)
                </div>
            </div>

            <button class="btn btn-primary" @click="handleLanjutkan">
                Lanjutkan
            </button>
        </div>

        <div
            v-if="showOutsideAreaModal"
            class="fixed inset-0 bg-black/45 flex items-center justify-center p-5 z-[100]"
            @click.self="showOutsideAreaModal = false"
        >
            <div
                class="card !rounded-2xl !p-5 max-w-[340px] w-full text-center"
            >
                <div class="text-[32px] mb-1.5">⚠️</div>
                <div class="text-base font-bold mb-2">
                    Kamu di Luar Area Toko
                </div>
                <p
                    class="text-[13px] text-[var(--text-muted)] leading-[1.5] mb-[18px]"
                >
                    Jarak kamu ke toko adalah
                    <strong>{{ Math.round(distanceToStore) }} m</strong>
                    (maksimal {{ CHECKIN_RADIUS_METERS }} m). Kunjungan tetap
                    bisa dilanjutkan, tapi status
                    <strong>"Di Luar Area"</strong>
                    akan tercatat dan dilaporkan ke management.
                </p>
                <div class="flex flex-row gap-3">
                    <button
                        class="btn btn-ghost flex-1 !py-2 !text-[13px]"
                        @click="showOutsideAreaModal = false"
                    >
                        Batal
                    </button>
                    <button
                        class="btn btn-primary flex-1 !py-2 !text-[13px]"
                        @click="confirmOutsideArea"
                    >
                        Tetap Lanjutkan
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import {
    ref,
    computed,
    watch,
    nextTick,
    onMounted,
    onBeforeUnmount,
} from "vue";
import L from "leaflet";
import "leaflet/dist/leaflet.css";

import markerIcon2x from "leaflet/dist/images/marker-icon-2x.png";
import markerIcon from "leaflet/dist/images/marker-icon.png";
import markerShadow from "leaflet/dist/images/marker-shadow.png";
delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: markerIcon2x,
    iconUrl: markerIcon,
    shadowUrl: markerShadow,
});

import { useCustomers } from "../composables/useCustomers";
import { useCheckinFlow } from "../composables/useCheckinFlow";
import { useAppNav } from "../composables/useAppNav";

const { currentCustomer } = useCustomers();
const {
    checkinCoord,
    checkinLocating,
    checkinError,
    checkinAddress,
    checkinAddressLoading,
    checkinOutsideArea,
    resetCheckinForm,
    startCheckinGeo,
    goToCheckinStep,
} = useCheckinFlow();
const { nav } = useAppNav();

const CHECKIN_RADIUS_METERS = 100;

function distanceMeters(lat1, lng1, lat2, lng2) {
    const R = 6371000;
    const toRad = (deg) => (deg * Math.PI) / 180;
    const dLat = toRad(lat2 - lat1);
    const dLng = toRad(lng2 - lng1);
    const a =
        Math.sin(dLat / 2) ** 2 +
        Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLng / 2) ** 2;
    return 2 * R * Math.asin(Math.sqrt(a));
}

const storeLatLng = computed(() => {
    const lat = parseFloat(currentCustomer.value?.lat);
    const lng = parseFloat(currentCustomer.value?.lng);
    if (Number.isNaN(lat) || Number.isNaN(lng)) return null;
    return { lat, lng };
});

const distanceToStore = computed(() => {
    if (!storeLatLng.value || !checkinCoord.value) return null;
    return distanceMeters(
        parseFloat(checkinCoord.value.lat),
        parseFloat(checkinCoord.value.lng),
        storeLatLng.value.lat,
        storeLatLng.value.lng,
    );
});

const isWithinRadius = computed(() => {
    if (distanceToStore.value === null) return true;
    return distanceToStore.value <= CHECKIN_RADIUS_METERS;
});

/* ---------------- Modal "Di Luar Area" ---------------- */

const showOutsideAreaModal = ref(false);

function handleLanjutkan() {
    if (!isWithinRadius.value) {
        showOutsideAreaModal.value = true;
        return;
    }
    if (checkinOutsideArea) checkinOutsideArea.value = false;
    goToCheckinStep(2);
}

function confirmOutsideArea() {
    showOutsideAreaModal.value = false;
    if (checkinOutsideArea) checkinOutsideArea.value = true;
    goToCheckinStep(2);
}

const mapEl = ref(null);

const storeIcon = L.divIcon({
    className: "store-marker-icon",
    html: '<div class="text-[20px] leading-8 text-center">🏬</div>',
    iconSize: [32, 32],
    iconAnchor: [16, 32],
});

let mapInstance = null;
let marker = null;
let accuracyCircle = null;
let storeMarker = null;
let storeRadiusCircle = null;

function initOrUpdateMap(coord) {
    if (!coord || !mapEl.value) return;

    const lat = parseFloat(coord.lat);
    const lng = parseFloat(coord.lng);
    const accuracy = parseFloat(coord.accuracy) || 30;
    const store = storeLatLng.value;

    if (!mapInstance) {
        mapInstance = L.map(mapEl.value, {
            zoomControl: false,
            attributionControl: false,
        }).setView([lat, lng], 17);

        L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
            maxZoom: 19,
        }).addTo(mapInstance);

        marker = L.marker([lat, lng]).addTo(mapInstance);

        accuracyCircle = L.circle([lat, lng], {
            radius: accuracy,
            color: "#3b82f6",
            fillColor: "#3b82f6",
            fillOpacity: 0.15,
            weight: 1.5,
        }).addTo(mapInstance);
    } else {
        marker.setLatLng([lat, lng]);
        accuracyCircle.setLatLng([lat, lng]);
        accuracyCircle.setRadius(accuracy);
    }

    if (store) {
        if (!storeMarker) {
            storeMarker = L.marker([store.lat, store.lng], {
                icon: storeIcon,
            }).addTo(mapInstance);

            storeRadiusCircle = L.circle([store.lat, store.lng], {
                radius: CHECKIN_RADIUS_METERS,
                color: "#f97316",
                fillColor: "#f97316",
                fillOpacity: 0.08,
                weight: 1.5,
                dashArray: "4 4",
            }).addTo(mapInstance);
        } else {
            storeMarker.setLatLng([store.lat, store.lng]);
            storeRadiusCircle.setLatLng([store.lat, store.lng]);
        }

        mapInstance.fitBounds(
            L.latLngBounds([
                [lat, lng],
                [store.lat, store.lng],
            ]),
            { padding: [30, 30], maxZoom: 17 },
        );
    } else {
        mapInstance.setView([lat, lng], mapInstance.getZoom());
    }

    setTimeout(() => mapInstance.invalidateSize(), 200);
}

watch(
    checkinCoord,
    (coord) => {
        if (coord) nextTick(() => initOrUpdateMap(coord));
    },
    { immediate: true },
);

// Entry point step 1: reset form check-in sebelumnya & mulai ambil GPS.
onMounted(() => {
    resetCheckinForm();
    startCheckinGeo();
});

onBeforeUnmount(() => {
    if (mapInstance) {
        mapInstance.remove();
        mapInstance = null;
    }
});
</script>
