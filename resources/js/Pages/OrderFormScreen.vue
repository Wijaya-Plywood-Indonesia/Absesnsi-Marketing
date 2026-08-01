<template>
    <!-- STEP 1: pilih toko — persis pola CustomersScreen -->
    <template v-if="step === 1">
        <div class="appbar">
            <div class="back-btn" @click="nav('order')">
                <svg viewBox="0 0 24 24" fill="none" stroke-width="2.4">
                    <path d="M15 18l-6-6 6-6" />
                </svg>
            </div>
            <h1>Pilih Toko</h1>
        </div>

        <div class="scroll" style="position: relative">
            <div class="search-box">
                <svg viewBox="0 0 24 24" fill="none" stroke-width="2">
                    <circle cx="11" cy="11" r="7" />
                    <path d="m21 21-4.3-4.3" />
                </svg>
                <input v-model="storeSearch" placeholder="Cari nama toko..." />
            </div>

            <div v-if="filteredStores.length">
                <div
                    v-for="c in filteredStores"
                    :key="c.id"
                    class="cust-card"
                    @click="pickCustomer(c.id)"
                >
                    <div class="cname">{{ c.name }}</div>
                    <div class="caddr">{{ c.addr }}</div>
                    <div class="tags">
                        <span
                            class="chip"
                            :class="c.status === 'Baru' ? 'baru' : 'lama'"
                            >{{ c.status }}</span
                        >
                        <span
                            class="chip"
                            :class="c.pola === 'Partai' ? 'partai' : 'ecer'"
                            >{{ c.pola }}</span
                        >
                    </div>
                </div>
            </div>
            <div v-else class="empty-note">Toko tidak ditemukan.</div>
        </div>
    </template>

    <!-- STEP 2: pilih produk -->
    <template v-else>
        <div class="appbar">
            <div class="back-btn" @click="backFromStep2">
                <svg viewBox="0 0 24 24" fill="none" stroke-width="2.4">
                    <path d="M15 18l-6-6 6-6" />
                </svg>
            </div>
            <h1>Request Order</h1>
        </div>

        <div class="scroll" style="padding-bottom: 90px">
            <span class="eyebrow">Untuk Customer</span>
            <div class="card flex justify-between items-center mb-4">
                <div class="info-line" style="border: none; padding: 0">
                    <span class="v text-xl font-bold">{{
                        currentCustomer ? currentCustomer.name : "-"
                    }}</span>
                </div>
                <button
                    class="text-xs text-[var(--accent)] bg-transparent border-0 cursor-pointer"
                    @click="step = 1"
                >
                    Ganti
                </button>
            </div>
            <div class="section-title" style="margin-top: 6px">Cari Produk</div>
            <div class="search-box">
                <svg viewBox="0 0 24 24" fill="none" stroke-width="2">
                    <circle cx="11" cy="11" r="7" />
                    <path d="m21 21-4.3-4.3" />
                </svg>
                <input
                    v-model="productSearch"
                    placeholder="Cari nama produk..."
                />
            </div>
            <div v-if="productSearch.trim()" class="flex flex-col gap-2.5">
                <div
                    v-if="!searchResults.length"
                    class="empty-note"
                    style="padding: 12px 0"
                >
                    Produk tidak ditemukan.
                </div>
                <div
                    v-for="p in searchResults"
                    :key="p.id"
                    class="card flex justify-between items-center gap-3 cursor-pointer"
                    @click="addToCart(p)"
                >
                    <div class="min-w-0">
                        <div class="font-semibold text-sm">{{ p.name }}</div>
                        <div class="text-xs text-[var(--text-muted)] mt-0.5">
                            per {{ p.unit }}
                        </div>
                    </div>
                    <span
                        class="shrink-0 w-8 h-8 rounded-full bg-[var(--accent)] text-white flex items-center justify-center text-lg leading-none"
                        >+</span
                    >
                </div>
            </div>

            <div class="section-title" style="margin-top: 20px">
                Keranjang Order
            </div>
            <div v-if="cartItems.length" class="flex flex-col gap-2.5">
                <div
                    v-for="(item, i) in cartItems"
                    :key="item.product_id"
                    class="flex justify-between items-center card"
                >
                    <div>
                        <div class="font-semibold text-sm">{{ item.name }}</div>
                        <div class="text-xs text-[var(--text-muted)] mt-0.5">
                            per {{ item.unit }}
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <button
                            class="w-7 h-7 rounded-full border border-[var(--border)] bg-[var(--surface-2)] text-[var(--text)] text-base leading-none cursor-pointer"
                            @click="stepCartQty(i, -1)"
                        >
                            −
                        </button>
                        <span class="min-w-[20px] text-center font-semibold">{{
                            item.qty
                        }}</span>
                        <button
                            class="w-7 h-7 rounded-full border border-[var(--border)] bg-[var(--surface-2)] text-[var(--text)] text-base leading-none cursor-pointer"
                            @click="stepCartQty(i, 1)"
                        >
                            +
                        </button>
                        <button
                            class="w-6 h-6 rounded-full border border-[var(--border)] bg-[var(--surface-2)] text-[var(--text-muted)] text-sm leading-none cursor-pointer ml-1"
                            @click="removeFromCart(i)"
                        >
                            ×
                        </button>
                    </div>
                </div>
            </div>
            <div v-else class="empty-note">
                Belum ada produk dipilih. Cari produk di atas untuk menambahkan.
            </div>

            <div class="field" style="margin-top: 16px">
                <label>Catatan Tambahan</label>
                <textarea
                    v-model="orderNote"
                    placeholder="Contoh: minta dikirim akhir minggu"
                ></textarea>
            </div>
        </div>

        <div class="order-bar" style="bottom: 74px">
            <div class="sum">
                Item dipilih<br /><b>{{ cartItems.length }}</b>
            </div>
            <button
                class="btn btn-primary flex-1"
                :disabled="submitting"
                @click="handleSubmitOrder"
            >
                {{ submitting ? "Mengirim..." : "Kirim Request Order" }}
            </button>
        </div>
    </template>
</template>

<script setup>
import { ref, computed } from "vue";
import { useCustomers } from "../composables/useCustomers";
import { useOrders } from "../composables/useOrders";
import { useSuccessStamp } from "../composables/useSuccessStamp";
import { useAppNav } from "../composables/useAppNav";

const { currentCustomer, currentCustomerId, customerList, setCurrentCustomer } =
    useCustomers();
const { products, submitOrder } = useOrders();
const { setSuccessStamp } = useSuccessStamp();
const { nav } = useAppNav();

const productSearch = ref("");
const cartItems = ref([]);
const orderNote = ref("");
const submitting = ref(false);

const searchResults = computed(() => {
    const q = productSearch.value.toLowerCase().trim();
    if (!q) return [];
    return products.value
        .filter((p) => p.name.toLowerCase().includes(q))
        .filter((p) => !cartItems.value.some((c) => c.product_id === p.id));
});

function addToCart(product) {
    cartItems.value.push({
        product_id: product.id,
        name: product.name,
        unit: product.unit,
        qty: 1,
    });
    productSearch.value = "";
}

function stepCartQty(index, dir) {
    const item = cartItems.value[index];
    const next = item.qty + dir;
    if (next <= 0) {
        cartItems.value.splice(index, 1);
    } else {
        item.qty = next;
    }
}

function removeFromCart(index) {
    cartItems.value.splice(index, 1);
}

// Step awal: kalau sudah ada customer aktif (mis. masuk dari detail toko),
// langsung ke step 2. Kalau belum, mulai dari pilih toko.
// Tombol "Ganti" selalu ditampilkan di step 2, jadi toko bisa diganti
// kapan saja tanpa kehilangan keranjang/catatan yang sudah diisi.
const step = ref(currentCustomerId.value !== null ? 2 : 1);

const storeSearch = ref("");
const filteredStores = computed(() => {
    const q = storeSearch.value.toLowerCase().trim();
    if (!q) return customerList.value;
    return customerList.value.filter((c) => c.name.toLowerCase().includes(q));
});

function pickCustomer(id) {
    setCurrentCustomer(id);
    step.value = 2;
}

function backFromStep2() {
    step.value = 1;
}

async function handleSubmitOrder() {
    if (!currentCustomerId.value) {
        alert("Pilih customer terlebih dahulu.");
        return;
    }
    if (!cartItems.value.length) {
        alert("Pilih minimal satu produk.");
        return;
    }

    submitting.value = true;
    try {
        const data = await submitOrder(
            currentCustomerId.value,
            currentCustomer.value?.name,
            cartItems.value,
            orderNote.value,
        );
        submitting.value = false;

        if (data.success) {
            setSuccessStamp({
                title: data.order.order_no,
                isOrder: true,
                details: [
                    { k: "Toko", v: currentCustomer.value.name },
                    ...cartItems.value.map((p) => ({
                        k: p.name,
                        v: `${p.qty} ${p.unit}`,
                    })),
                ],
                action: () => nav("home"),
            });
            orderNote.value = "";
            nav("successStamp");
        } else {
            alert("Gagal membuat request order.");
        }
    } catch (err) {
        submitting.value = false;
        console.error(err);
        alert("Terjadi kesalahan koneksi.");
    }
}
</script>
