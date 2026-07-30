<template>
    <div class="appbar">
        <div class="back-btn" @click="nav('customers')">
            <svg viewBox="0 0 24 24" fill="none" stroke-width="2.4">
                <path d="M15 18l-6-6 6-6" />
            </svg>
        </div>
        <h1>Detail Customer</h1>
    </div>
    <div class="scroll" v-if="currentCustomer">
        <div class="detail-hero">
            <div class="cname">{{ currentCustomer.name }}</div>
            <div class="caddr">{{ currentCustomer.addr }}</div>
            <div class="tags" style="margin-bottom: 16px">
                <span
                    class="chip"
                    :class="currentCustomer.status === 'Baru' ? 'baru' : 'lama'"
                    >{{ currentCustomer.status }}</span
                >
                <span
                    class="chip"
                    :class="
                        currentCustomer.pola === 'Partai' ? 'partai' : 'ecer'
                    "
                    >{{ currentCustomer.pola }}</span
                >
                <span class="chip">{{ currentCustomer.jenis }}</span>
            </div>
        </div>
        <div class="card">
            <div class="info-line">
                <span class="k">No. HP</span
                ><span class="v">{{ currentCustomer.phone }}</span>
            </div>
            <div class="info-line">
                <span class="k">Koordinat Toko</span
                ><span class="v"
                    >{{ currentCustomer.lat }}, {{ currentCustomer.lng }}</span
                >
            </div>
        </div>
        <div class="action-row">
            <button class="btn btn-primary" @click="handleMulaiKunjungan">
                {{ alreadyVisitedToday ? "Edit Kunjungan" : "Mulai Kunjungan" }}
            </button>
            <button
                class="btn btn-outline"
                @click="nav('orderForm', currentCustomer.id)"
            >
                Request Order
            </button>
        </div>
        <div class="section-title">Riwayat Kunjungan Toko Ini</div>
        <div v-if="customerVisits.length">
            <div v-for="v in customerVisits" :key="v.id" class="mini-visit">
                <span>{{ v.date }}, {{ v.time }} — {{ v.hasil }}</span>
            </div>
        </div>
        <div v-else class="empty-note">Belum pernah dikunjungi oleh Anda.</div>
    </div>
</template>

<script setup>
import { computed } from "vue";
import { useCustomers } from "../composables/useCustomers";
import { useVisits } from "../composables/useVisits";
import { useAppNav } from "../composables/useAppNav";

const { currentCustomer, currentCustomerId } = useCustomers();
const { hasVisitedToday, customerVisitsFor } = useVisits();
const { nav } = useAppNav();

const customerVisits = customerVisitsFor(currentCustomerId);

const alreadyVisitedToday = computed(() => {
    if (!currentCustomer.value) return false;
    return hasVisitedToday(currentCustomer.value.id);
});

function handleMulaiKunjungan() {
    if (alreadyVisitedToday.value) {
        nav("checkinResult", currentCustomer.value.id);
    } else {
        nav("checkin", currentCustomer.value.id);
    }
}
</script>
