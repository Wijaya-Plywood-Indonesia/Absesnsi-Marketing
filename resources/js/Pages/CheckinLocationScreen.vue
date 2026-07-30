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
        <div class="section-title" style="margin-top: 2px">
            Ambil Lokasi Kunjungan
        </div>

        <div
            ref="mapEl"
            class="map-box"
            id="checkinMap"
            style="height: 220px; border-radius: 12px; overflow: hidden"
        >
            <div v-if="checkinLocating" class="locating">
                <span class="spinner"></span> Mengunci sinyal GPS...
            </div>
        </div>

        <div
            v-if="checkinError && !checkinCoord"
            class="card"
            style="margin-top: 12px"
        >
            <div
                style="
                    font-size: 13px;
                    color: var(--danger);
                    margin-bottom: 10px;
                "
            >
                {{ checkinError }}
            </div>
            <button class="btn btn-ghost" @click="startCheckinGeo">
                🔄 Coba Lagi
            </button>
        </div>

        <div v-if="checkinCoord">
            <div class="card coord-card">
                <div class="address-line">
                    <span class="pin">📍</span>
                    <span v-if="checkinAddressLoading" class="address-text muted"
                        >Mencari alamat...</span
                    >
                    <span v-else-if="checkinAddress" class="address-text">{{
                        checkinAddress
                    }}</span>
                    <span v-else class="address-text muted"
                        >Alamat tidak ditemukan, pakai koordinat di
                        bawah.</span
                    >
                </div>
                <div class="coord-grid">
                    <div class="item">
                        <div class="k">Latitude</div>
                        <div class="v">{{ checkinCoord.lat }}</div>
                    </div>
                    <div class="item">
                        <div class="k">Longitude</div>
                        <div class="v">{{ checkinCoord.lng }}</div>
                    </div>
                    <div class="item">
                        <div class="k">Akurasi</div>
                        <div class="v">{{ checkinCoord.accuracy }} m</div>
                    </div>
                    <div class="item">
                        <div class="k">Jam Check-in</div>
                        <div class="v">{{ checkinCoord.time }}</div>
                    </div>
                </div>
            </div>

            <div
                v-if="distanceToStore !== null"
                class="card"
                :class="isWithinRadius ? 'distance-ok' : 'distance-danger'"
                style="margin-top: 12px"
            >
                <div style="font-size: 13px; font-weight: 600">
                    {{
                        isWithinRadius
                            ? "✅ Dalam radius toko"
                            : "⚠️ Terlalu jauh dari toko"
                    }}
                </div>
                <div
                    style="
                        font-size: 12px;
                        color: var(--text-muted);
                        margin-top: 2px;
                    "
                >
                    Jarak ke toko: {{ Math.round(distanceToStore) }} m (maks.
                    {{ CHECKIN_RADIUS_METERS }} m)
                </div>
            </div>

            <button
                class="btn btn-primary"
                style="margin-top: 16px"
                :disabled="!isWithinRadius"
                @click="goToCheckinStep(2)"
            >
                Lanjutkan
            </button>
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

const mapEl = ref(null);

const storeIcon = L.divIcon({
    className: "store-marker-icon",
    html: '<div class="store-pin">🏬</div>',
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

<style scoped>
.address-line {
    display: flex;
    align-items: flex-start;
    gap: 6px;
    font-size: 13px;
    font-weight: 600;
    margin-bottom: 10px;
    padding-bottom: 10px;
    border-bottom: 1px solid var(--border);
}
.address-line .pin {
    flex-shrink: 0;
}
.address-line .address-text.muted {
    font-weight: 400;
    color: var(--text-muted);
}
.distance-ok {
    border-left: 3px solid #22c55e;
}
.distance-danger {
    border-left: 3px solid var(--danger, #ef4444);
}
</style>

<style>
.store-pin {
    font-size: 20px;
    line-height: 32px;
    text-align: center;
}
</style>
