<template>
    <div class="home-head">
        <span class="eyebrow">Rute Hari Ini</span>
        <div class="greet">{{ greeting }}, {{ user.name }}</div>
        <div class="date">{{ currentDate }}</div>
        <div class="stat-row">
            <div class="stat-box">
                <div class="num">{{ stats.doneToday }}/{{ dailyTarget }}</div>
                <div class="cap">Kunjungan Selesai</div>
            </div>
            <div class="stat-box">
                <div class="num">{{ stats.orderToday }}</div>
                <div class="cap">Order Masuk</div>
            </div>
            <div class="stat-box">
                <div class="num">{{ customerList.length }}</div>
                <div class="cap">Customer Dipegang</div>
            </div>
        </div>
        <div class="quick-row">
            <button
                class="btn btn-primary"
                style="flex: 1"
                @click="nav('newCustomer')"
            >
                + Customer Baru
            </button>
            <button
                class="btn btn-outline"
                style="flex: 1"
                @click="nav('customers')"
            >
                Lihat Semua
            </button>
        </div>
    </div>
    <div class="scroll">
        <div class="section-title">Rencana Kunjungan</div>
        <div v-if="plannedCustomers.length" class="visit-list">
            <div
                v-for="(c, i) in plannedCustomers"
                :key="c.id"
                class="visit-row"
                @click="openDetail(c.id)"
            >
                <span class="time-badge">{{ 9 + i * 2 }}:00</span>
                <span
                    class="dot"
                    :class="{
                        done: hasVisitedToday(c.id),
                        pending: !hasVisitedToday(c.id),
                    }"
                ></span>
                <div style="flex: 1">
                    <div class="vname">{{ c.name }}</div>
                    <div class="vaddr">{{ c.addr }}</div>
                </div>
            </div>
        </div>
        <div v-else class="empty-note">
            Belum ada customer yang ditugaskan ke Anda.
        </div>

        <div class="section-title">Kunjungan Terakhir</div>
        <div v-if="recentVisits.length" class="recent-list">
            <div
                v-for="v in recentVisits"
                :key="v.id"
                class="hist-row"
                @click="openDetail(v.custId)"
            >
                <div class="hist-top">
                    <span class="hname">{{ getCustomerName(v.custId) }}</span>
                    <span class="hdate">{{ v.date }}, {{ v.time }}</span>
                </div>
                <div class="hist-note">{{ v.hasil }} — {{ v.note }}</div>
            </div>
        </div>
        <div v-else class="empty-note">Belum ada kunjungan tercatat.</div>
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
