<template>
    <div class="appbar"><h1>Order</h1></div>

    <div class="scroll" style="position: relative">
        <div class="search-box">
            <svg viewBox="0 0 24 24" fill="none" stroke-width="2">
                <circle cx="11" cy="11" r="7" />
                <path d="m21 21-4.3-4.3" />
            </svg>
            <input
                v-model="search"
                placeholder="Cari nomor order / nama toko..."
            />
        </div>

        <div v-if="filteredOrders.length">
            <div
                v-for="o in filteredOrders"
                :key="o.id"
                class="cust-card order-card"
                @click="toggleExpand(o.id)"
            >
                <div class="order-top">
                    <span class="order-no">{{ o.order_no }}</span>
                    <span class="order-date">{{
                        formatDate(o.order_date || o.created_at)
                    }}</span>
                </div>
                <div class="order-row-2">
                    <div class="cname">
                        {{
                            o.customer
                                ? o.customer.name
                                : getCustomerName(o.customer_id)
                        }}
                    </div>
                    <span class="order-item-count"
                        >{{ o.items.length }} produk</span
                    >
                    <svg
                        class="chevron"
                        :class="{ open: expandedIds.has(o.id) }"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke-width="2.4"
                    >
                        <path d="M6 9l6 6 6-6" />
                    </svg>
                </div>

                <template v-if="expandedIds.has(o.id)">
                    <div class="order-items">
                        <span
                            v-for="(item, idx) in o.items"
                            :key="idx"
                            class="item-pill"
                        >
                            {{ item.name }} · {{ item.qty }} {{ item.unit }}
                        </span>
                    </div>

                    <div v-if="o.catatan" class="order-note">
                        {{ o.catatan }}
                    </div>
                </template>
            </div>
        </div>

        <div v-else class="empty-note">
            Belum ada order yang dibuat.<br />Tekan tombol + untuk buat order
            pertama.
        </div>
    </div>

    <button class="fab" @click="nav('orderForm')">
        <svg viewBox="0 0 24 24" fill="none" stroke-width="2.4">
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

<style scoped>
.order-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 6px;
}
.order-no {
    font-family: "IBM Plex Mono", monospace;
    font-weight: 700;
    color: var(--accent);
    font-size: 13px;
}
.order-date {
    font-size: 12px;
    color: var(--text-muted);
}
.order-card {
    cursor: pointer;
}
.order-row-2 {
    display: flex;
    align-items: center;
    gap: 8px;
}
.order-row-2 .cname {
    flex: 1;
}
.order-item-count {
    font-size: 11px;
    color: var(--text-muted);
    white-space: nowrap;
}
.chevron {
    width: 16px;
    height: 16px;
    color: var(--text-muted);
    stroke: currentColor;
    transition: transform 0.15s ease;
    flex-shrink: 0;
}
.chevron.open {
    transform: rotate(180deg);
}
.order-items {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-top: 8px;
}
.item-pill {
    font-size: 11px;
    background: var(--surface-2);
    border: 1px solid var(--border);
    border-radius: 999px;
    padding: 3px 10px;
    color: var(--text-muted);
}
.order-note {
    margin-top: 8px;
    font-size: 12px;
    background: var(--surface-2);
    padding: 8px 10px;
    border-radius: 8px;
    border-left: 3px solid var(--accent);
}
</style>
