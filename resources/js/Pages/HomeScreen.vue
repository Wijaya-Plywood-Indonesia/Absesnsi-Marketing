<template>
    <div class="home-head pt-[4px] px-[18px] pb-[18px]">
        <span class="eyebrow font-mono text-[10.5px] tracking-[0.13em] uppercase text-[var(--accent)] mb-[4px] block">Rute Hari Ini</span>
        <div class="greet font-['Space_Grotesk'] text-[22px] font-semibold">{{ greeting }}, {{ user.name }}</div>
        <div class="date text-[12.5px] text-[var(--text-muted)] mt-[2px]">{{ currentDate }}</div>
        <div class="stat-row flex gap-[10px] mt-[16px]">
            <div class="stat-box flex-1 bg-[var(--surface)] border border-[var(--border)] rounded-[var(--radius)] p-[13px]">
                <div class="num font-['Space_Grotesk'] text-[22px] font-bold">{{ stats.doneToday }}/{{ dailyTarget }}</div>
                <div class="cap text-[11px] text-[var(--text-muted)] mt-[2px]">Kunjungan Selesai</div>
            </div>
            <div class="stat-box flex-1 bg-[var(--surface)] border border-[var(--border)] rounded-[var(--radius)] p-[13px]">
                <div class="num font-['Space_Grotesk'] text-[22px] font-bold">{{ stats.orderToday }}</div>
                <div class="cap text-[11px] text-[var(--text-muted)] mt-[2px]">Order Masuk</div>
            </div>
            <div class="stat-box flex-1 bg-[var(--surface)] border border-[var(--border)] rounded-[var(--radius)] p-[13px]">
                <div class="num font-['Space_Grotesk'] text-[22px] font-bold">{{ customerList.length }}</div>
                <div class="cap text-[11px] text-[var(--text-muted)] mt-[2px]">Customer Dipegang</div>
            </div>
        </div>
        <div class="quick-row flex gap-[10px] mt-[16px] mb-[4px]">
            <button
                class="btn btn-primary font-sans font-semibold text-[14.5px] rounded-[12px] border-none px-[18px] py-[14px] cursor-pointer flex items-center justify-center gap-[8px] bg-[var(--accent)] text-[var(--accent-ink)] active:scale-[0.98] disabled:bg-[var(--border)] disabled:text-[var(--text-faint)] disabled:cursor-not-allowed"
                style="flex: 1"
                @click="nav('newCustomer')"
            >
                + Customer Baru
            </button>
            <button
                class="btn btn-outline font-sans font-semibold text-[14.5px] rounded-[12px] bg-transparent text-[var(--text)] border border-[var(--border)] px-[18px] py-[14px] cursor-pointer flex items-center justify-center gap-[8px]"
                style="flex: 1"
                @click="nav('customers')"
            >
                Lihat Semua
            </button>
        </div>
    </div>
    <div class="scroll flex-1 overflow-y-auto px-[18px] pb-[24px]">
        <div class="section-title font-['Space_Grotesk'] text-[15px] font-semibold mt-[22px] mb-[10px]">Rencana Kunjungan</div>
        <div v-if="plannedCustomers.length" class="visit-list flex flex-col">
            <div
                v-for="(c, i) in plannedCustomers"
                :key="c.id"
                class="visit-row flex items-center gap-[12px] py-[13px] border-b border-[var(--border)] cursor-pointer last:border-b-0"
                @click="openDetail(c.id)"
            >
                <span class="time-badge font-mono text-[12px] text-[var(--text-muted)] w-[44px] flex-shrink-0">{{ 9 + i * 2 }}:00</span>
                <span
                    class="dot w-[7px] h-[7px] rounded-full flex-shrink-0"
                    :class="hasVisitedToday(c.id) ? 'done bg-[var(--good)]' : 'pending bg-[var(--text-faint)]'"
                ></span>
                <div style="flex: 1">
                    <div class="vname font-semibold text-[14px]">{{ c.name }}</div>
                    <div class="vaddr text-[12px] text-[var(--text-muted)] mt-[1px]">{{ c.addr }}</div>
                </div>
            </div>
        </div>
        <div v-else class="empty-note text-center text-[var(--text-faint)] text-[12.5px] py-[30px] px-[10px]">
            Belum ada customer yang ditugaskan ke Anda.
        </div>

        <div class="section-title font-['Space_Grotesk'] text-[15px] font-semibold mt-[22px] mb-[10px]">Kunjungan Terakhir</div>
        <div v-if="recentVisits.length" class="recent-list flex flex-col">
            <div
                v-for="v in recentVisits"
                :key="v.id"
                class="hist-row py-[12px] border-b border-[var(--border)] cursor-pointer last:border-b-0"
                @click="openDetail(v.custId)"
            >
                <div class="hist-top flex justify-between items-baseline">
                    <span class="hname font-semibold text-[14px]">{{ getCustomerName(v.custId) }}</span>
                    <span class="hdate text-[11px] text-[var(--text-faint)] font-mono">{{ v.date }}, {{ v.time }}</span>
                </div>
                <div class="hist-note text-[12.5px] text-[var(--text-muted)] mt-[3px]">{{ v.hasil }} — {{ v.note }}</div>
            </div>
        </div>
        <div v-else class="empty-note text-center text-[var(--text-faint)] text-[12.5px] py-[30px] px-[10px]">Belum ada kunjungan tercatat.</div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import { useAuth } from "../composables/useAuth";
import { useCustomers } from "../composables/useCustomers";
import { useVisits } from "../composables/useVisits";
import { useAppNav } from "../composables/useAppNav";

const { user } = useAuth();
const { customerList, plannedCustomers, getCustomerName } = useCustomers();
const { stats, recentVisits, hasVisitedToday } = useVisits();
const { nav, openDetail } = useAppNav();

const currentDate = ref("");
const greeting = ref("");

// fallback ke 8 kalau daily_target user belum diset (null)
const dailyTarget = computed(() => user.value?.daily_target ?? 8);

onMounted(() => {
    currentDate.value = new Date().toLocaleDateString("id-ID", {
        weekday: "long",
        day: "numeric",
        month: "long",
    });
    const h = new Date().getHours();
    if (h < 11) greeting.value = "Selamat Pagi";
    else if (h < 15) greeting.value = "Selamat Siang";
    else if (h < 18) greeting.value = "Selamat Sore";
    else greeting.value = "Selamat Malam";
});
</script>
