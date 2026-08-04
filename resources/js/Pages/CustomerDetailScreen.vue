<template>
    <div class="appbar flex-shrink-0 px-[18px] pt-[6px] pb-[16px] flex items-center gap-[10px]">
        <div class="back-btn w-[32px] h-[32px] rounded-[10px] bg-[var(--surface-2)] border border-[var(--border)] flex items-center justify-center cursor-pointer flex-shrink-0" @click="nav('customers')">
            <svg class="w-4 h-4 stroke-[var(--text)]" viewBox="0 0 24 24" fill="none" stroke-width="2.4">
                <path d="M15 18l-6-6 6-6" />
            </svg>
        </div>
        <h1 class="font-['Space_Grotesk'] text-[19px] font-semibold tracking-[-0.01em]">Detail Customer</h1>
    </div>
    <div class="scroll flex-1 overflow-y-auto px-[18px] pb-[24px]" v-if="currentCustomer">
        <div class="detail-hero pt-[2px] pb-[4px]">
            <div class="font-['Space_Grotesk'] text-[20px] font-bold">{{ currentCustomer.name }}</div>
            <div class="text-[13px] text-[var(--text-muted)] mt-[4px] mb-[12px]">{{ currentCustomer.addr }}</div>
            <div class="flex gap-[6px] flex-wrap" style="margin-bottom: 16px">
                <span
                    class="chip inline-flex items-center gap-[4px] text-[11px] font-semibold px-[9px] py-[4px] rounded-full border font-mono tracking-[0.01em]"
                    :class="currentCustomer.status === 'Baru' ? 'baru text-[var(--good)] border-[#3c4d33] bg-[var(--good-soft)]' : 'lama border-[var(--border)] text-[var(--text-muted)]'"
                    >{{ currentCustomer.status }}</span
                >
                <span
                    class="chip inline-flex items-center gap-[4px] text-[11px] font-semibold px-[9px] py-[4px] rounded-full border font-mono tracking-[0.01em]"
                    :class="
                        currentCustomer.pola === 'Partai' ? 'partai text-[var(--accent)] border-[var(--accent-soft)] bg-[var(--accent-soft)]' : 'ecer border-[var(--border)] text-[var(--text-muted)]'"
                    >{{ currentCustomer.pola }}</span
                >
                <span class="chip inline-flex items-center gap-[4px] text-[11px] font-semibold px-[9px] py-[4px] rounded-full border border-[var(--border)] text-[var(--text-muted)] font-mono tracking-[0.01em]">{{ currentCustomer.jenis }}</span>
            </div>
        </div>
        <div class="card bg-[var(--surface)] border border-[var(--border)] rounded-[var(--radius)] p-[16px]">
            <div class="info-line flex justify-between py-[10px] border-b border-[var(--border)] text-[13.5px]">
                <span class="text-[var(--text-muted)]">No. HP</span
                ><span class="font-mono text-[12.5px]">{{ currentCustomer.phone }}</span>
            </div>
            <div class="info-line flex justify-between py-[10px] text-[13.5px]">
                <span class="text-[var(--text-muted)]">Koordinat Toko</span
                ><span class="font-mono text-[12.5px]"
                    >{{ currentCustomer.lat }}, {{ currentCustomer.lng }}</span
                >
            </div>
        </div>
        <div class="action-row flex gap-[10px] mt-[18px]">
            <button class="btn btn-primary font-sans font-semibold text-[14.5px] rounded-[12px] border-none px-[18px] py-[14px] cursor-pointer flex items-center justify-center gap-[8px] w-full bg-[var(--accent)] text-[var(--accent-ink)] active:scale-[0.98] disabled:bg-[var(--border)] disabled:text-[var(--text-faint)] disabled:cursor-not-allowed" @click="handleMulaiKunjungan">
                {{ alreadyVisitedToday ? "Edit Kunjungan" : "Mulai Kunjungan" }}
            </button>
            <button
                class="btn btn-outline font-sans font-semibold text-[14.5px] rounded-[12px] bg-transparent text-[var(--text)] border border-[var(--border)] px-[18px] py-[14px] cursor-pointer flex items-center justify-center gap-[8px] w-full"
                @click="nav('orderForm', currentCustomer.id)"
            >
                Request Order
            </button>
        </div>
        <div class="font-['Space_Grotesk'] text-[15px] font-semibold mt-[22px] mb-[10px]">Riwayat Kunjungan Toko Ini</div>
        <div v-if="customerVisits.length" class="flex flex-col">
            <div v-for="v in customerVisits" :key="v.id" class="mini-visit text-[12.5px] text-[var(--text-muted)] py-[8px] border-b border-[var(--border)] flex justify-between last:border-b-0">
                <span>{{ v.date }}, {{ v.time }} — {{ v.hasil }}</span>
            </div>
        </div>
        <div v-else class="text-center text-[var(--text-faint)] text-[12.5px] py-[30px] px-[10px]">Belum pernah dikunjungi oleh Anda.</div>
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
