<template>
    <div class="appbar flex-shrink-0 px-[18px] pt-[6px] pb-[16px] flex items-center gap-[10px]">
        <h1 class="font-['Space_Grotesk'] text-[19px] font-semibold tracking-[-0.01em]">Order</h1>
    </div>

    <div class="scroll flex-1 overflow-y-auto px-[18px] pb-[24px] relative">
        <div class="search-box flex items-center gap-[8px] bg-[var(--surface-2)] border border-[var(--border)] rounded-[12px] px-[13px] py-[10px] mt-[14px] mb-[12px]">
            <svg class="w-[16px] h-[16px] stroke-[var(--text-faint)] flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke-width="2">
                <circle cx="11" cy="11" r="7" />
                <path d="m21 21-4.3-4.3" />
            </svg>
            <input
                class="bg-transparent border-none outline-none text-[var(--text)] text-[14px] flex-1 font-sans"
                v-model="search"
                placeholder="Cari nomor order / nama toko..."
            />
        </div>

        <div v-if="filteredOrders.length">
            <div
                v-for="o in filteredOrders"
                :key="o.id"
                class="cust-card bg-[var(--surface)] border border-[var(--border)] rounded-[var(--radius)] p-[14px] mb-[10px] cursor-pointer"
                @click="toggleExpand(o.id)"
            >
                <div class="flex justify-between items-center mb-[6px]">
                    <span class="font-mono font-bold text-[var(--accent)] text-[13px]">{{ o.order_no }}</span>
                    <span class="text-[12px] text-[var(--text-muted)]">{{
                        formatDate(o.order_date || o.created_at)
                    }}</span>
                </div>
                <div class="flex items-center gap-[8px]">
                    <div class="flex-1 font-['Space_Grotesk'] font-semibold text-[15px]">
                        {{
                            o.customer
                                ? o.customer.name
                                : getCustomerName(o.customer_id)
                        }}
                    </div>
                    <span class="text-[11px] text-[var(--text-muted)] whitespace-nowrap"
                        >{{ o.items.length }} produk</span
                     >
                    <svg
                        class="w-[16px] h-[16px] text-[var(--text-muted)] stroke-current transition-transform duration-150 ease-in-out shrink-0"
                        :class="expandedIds.has(o.id) ? 'rotate-180' : ''"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke-width="2.4"
                    >
                        <path d="M6 9l6 6 6-6" />
                    </svg>
                </div>

                <template v-if="expandedIds.has(o.id)">
                    <div class="flex flex-wrap gap-[6px] mt-[8px]">
                        <span
                            v-for="(item, idx) in o.items"
                            :key="idx"
                            class="text-[11px] bg-[var(--surface-2)] border border-[var(--border)] rounded-full px-[10px] py-[3px] text-[var(--text-muted)]"
                        >
                            {{ item.name }} · {{ item.qty }} {{ item.unit }}
                        </span>
                    </div>

                    <div v-if="o.catatan" class="mt-[8px] text-[12px] bg-[var(--surface-2)] px-[10px] py-[8px] rounded-[8px] border-l-[3px] border-[var(--accent)]">
                        {{ o.catatan }}
                    </div>
                </template>
            </div>
        </div>

        <div v-else class="text-center text-[var(--text-faint)] text-[12.5px] py-[30px] px-[10px]">
            Belum ada order yang dibuat.<br />Tekan tombol + untuk buat order
            pertama.
        </div>
    </div>

    <button class="fab absolute right-[18px] bottom-[96px] w-[52px] h-[52px] rounded-[16px] bg-[var(--accent)] text-[var(--accent-ink)] border-none flex items-center justify-center shadow-[0_10px_24px_-8px_rgba(242,169,59,0.5)] cursor-pointer z-40" @click="nav('orderForm')">
        <svg class="w-[22px] h-[22px] stroke-[var(--accent-ink)]" viewBox="0 0 24 24" fill="none" stroke-width="2.4">
            <path d="M12 5v14M5 12h14" />
        </svg>
    </button>
</template>

<script setup>
import { ref, computed } from "vue";
import { useOrders } from "../composables/useOrders";
import { useCustomers } from "../composables/useCustomers";
import { useAppNav } from "../composables/useAppNav";

const { orderList } = useOrders();
const { getCustomerName } = useCustomers();
const { nav } = useAppNav();

const search = ref("");
const expandedIds = ref(new Set());

function toggleExpand(id) {
    const next = new Set(expandedIds.value);
    if (next.has(id)) {
        next.delete(id);
    } else {
        next.add(id);
    }
    expandedIds.value = next;
}

// orderList sudah difilter di backend supaya cuma order milik sales yang
// sedang login; di sini tinggal urutkan terbaru paling atas.
const sortedOrders = computed(() => {
    return [...orderList.value].sort((a, b) => {
        const da = a.order_date || a.created_at || "";
        const db = b.order_date || b.created_at || "";
        if (da !== db) return db.localeCompare(da);
        return (b.created_at || "").localeCompare(a.created_at || "");
    });
});

const filteredOrders = computed(() => {
    const q = search.value.toLowerCase().trim();
    if (!q) return sortedOrders.value;
    return sortedOrders.value.filter((o) => {
        const custName =
            (o.customer
                ? o.customer.name
                : getCustomerName(o.customer_id)) || "";
        return (
            o.order_no.toLowerCase().includes(q) ||
            custName.toLowerCase().includes(q)
        );
    });
});

function formatDate(dateStr) {
    if (!dateStr) return "";
    const date = new Date(dateStr);
    const day = String(date.getDate()).padStart(2, "0");
    const months = [
        "Jan",
        "Feb",
        "Mar",
        "Apr",
        "Mei",
        "Jun",
        "Jul",
        "Agu",
        "Sep",
        "Okt",
        "Nov",
        "Des",
    ];
    return `${day} ${months[date.getMonth()]} ${date.getFullYear()}`;
}
</script>
