<template>
    <!-- STEP 1: pilih toko — persis pola CustomersScreen -->
    <template v-if="step === 1">
        <div
            class="appbar flex-shrink-0 px-[18px] pt-[6px] pb-[16px] flex items-center gap-[10px]"
        >
            <div
                class="w-[32px] h-[32px] rounded-[10px] bg-[var(--surface-2)] border border-[var(--border)] flex items-center justify-center cursor-pointer flex-shrink-0"
                @click="nav('order')"
            >
                <svg
                    class="w-4 h-4 stroke-[var(--text)]"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke-width="2.4"
                >
                    <path d="M15 18l-6-6 6-6" />
                </svg>
            </div>
            <h1
                class="font-['Space_Grotesk'] text-[19px] font-semibold tracking-[-0.01em]"
            >
                Pilih Toko
            </h1>
        </div>

        <div class="scroll flex-1 overflow-y-auto px-[18px] pb-[24px] relative">
            <div
                class="search-box flex items-center gap-[8px] bg-[var(--surface-2)] border border-[var(--border)] rounded-[12px] px-[13px] py-[10px] mt-[14px] mb-[12px]"
            >
                <svg
                    class="w-[16px] h-[16px] stroke-[var(--text-faint)] flex-shrink-0"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke-width="2"
                >
                    <circle cx="11" cy="11" r="7" />
                    <path d="m21 21-4.3-4.3" />
                </svg>
                <input
                    class="bg-transparent border-none outline-none text-[var(--text)] text-[14px] flex-1 font-sans"
                    v-model="storeSearch"
                    placeholder="Cari nama toko..."
                />
            </div>

            <div v-if="filteredStores.length">
                <div
                    v-for="c in filteredStores"
                    :key="c.id"
                    class="cust-card bg-[var(--surface)] border border-[var(--border)] rounded-[var(--radius)] p-[14px] mb-[10px] cursor-pointer"
                    @click="pickCustomer(c.id)"
                >
                    <div
                        class="font-['Space_Grotesk'] font-semibold text-[15px]"
                    >
                        {{ c.name }}
                    </div>
                    <div
                        class="text-[12.5px] text-[var(--text-muted)] mt-[3px]"
                    >
                        {{ c.addr }}
                    </div>
                    <div class="flex gap-[6px] flex-wrap mt-[10px]">
                        <span
                            class="inline-flex items-center gap-[4px] text-[11px] font-semibold px-[9px] py-[4px] rounded-full border font-mono tracking-[0.01em]"
                            :class="
                                c.status === 'Baru'
                                    ? 'text-[var(--good)] border-[#3c4d33] bg-[var(--good-soft)]'
                                    : 'border-[var(--border)] text-[var(--text-muted)]'
                            "
                            >{{ c.status }}</span
                        >
                        <span
                            class="inline-flex items-center gap-[4px] text-[11px] font-semibold px-[9px] py-[4px] rounded-full border font-mono tracking-[0.01em]"
                            :class="
                                c.pola === 'Partai'
                                    ? 'text-[var(--accent)] border-[var(--accent-soft)] bg-[var(--accent-soft)]'
                                    : 'border-[var(--border)] text-[var(--text-muted)]'
                            "
                            >{{ c.pola }}</span
                        >
                    </div>
                </div>
            </div>
            <div
                v-else
                class="text-center text-[var(--text-faint)] text-[12.5px] py-[30px] px-[10px]"
            >
                Toko tidak ditemukan.
            </div>
        </div>
    </template>

    <!-- STEP 2: pilih produk -->
    <template v-else>
        <div
            class="appbar flex-shrink-0 px-[18px] pt-[6px] pb-[16px] flex items-center gap-[10px]"
        >
            <div
                class="w-[32px] h-[32px] rounded-[10px] bg-[var(--surface-2)] border border-[var(--border)] flex items-center justify-center cursor-pointer flex-shrink-0"
                @click="backFromStep2"
            >
                <svg
                    class="w-4 h-4 stroke-[var(--text)]"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke-width="2.4"
                >
                    <path d="M15 18l-6-6 6-6" />
                </svg>
            </div>
            <h1
                class="font-['Space_Grotesk'] text-[19px] font-semibold tracking-[-0.01em]"
            >
                Request Order
            </h1>
        </div>

        <!--
          Wrapper ini WAJIB relative + overflow-hidden supaya order-bar (absolute, bottom-0)
          nempel pas di bawah AREA STEP 2 ini, bukan ke viewport (itu penyebab bug di screenshot:
          fixed lepas dari card karena parent-nya ada transform).
        -->
        <div
            class="step2-body flex-1 relative overflow-hidden flex flex-col min-h-0"
        >
            <div
                class="scroll flex-1 overflow-y-auto px-[18px] pb-[24px]"
                style="padding-bottom: 104px"
            >
                <span
                    class="font-mono text-[10.5px] tracking-[0.13em] uppercase text-[var(--accent)] mb-[4px] block"
                    >Untuk Customer</span
                >
                <div
                    class="card bg-[var(--surface)] border border-[var(--border)] rounded-[var(--radius)] p-[16px] flex justify-between items-center mb-4"
                >
                    <div
                        class="flex justify-between py-[10px] text-[13.5px]"
                        style="border: none; padding: 0"
                    >
                        <span
                            class="font-mono text-[12.5px] text-xl font-bold text-[var(--text)]"
                            >{{
                                currentCustomer ? currentCustomer.name : "-"
                            }}</span
                        >
                    </div>
                    <button
                        class="text-xs text-[var(--accent)] bg-transparent border-0 cursor-pointer"
                        @click="step = 1"
                    >
                        Ganti
                    </button>
                </div>
                <div
                    class="font-['Space_Grotesk'] text-[15px] font-semibold mt-[22px] mb-[10px]"
                    style="margin-top: 6px"
                >
                    Cari Produk
                </div>
                <div
                    class="search-box flex items-center gap-[8px] bg-[var(--surface-2)] border border-[var(--border)] rounded-[12px] px-[13px] py-[10px] mt-[14px] mb-[12px]"
                >
                    <svg
                        class="w-[16px] h-[16px] stroke-[var(--text-faint)] flex-shrink-0"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke-width="2"
                    >
                        <circle cx="11" cy="11" r="7" />
                        <path d="m21 21-4.3-4.3" />
                    </svg>
                    <input
                        class="bg-transparent border-none outline-none text-[var(--text)] text-[14px] flex-1 font-sans"
                        v-model="productSearch"
                        placeholder="Cari nama produk..."
                    />
                </div>
                <div v-if="productSearch.trim()" class="flex flex-col gap-2.5">
                    <div
                        v-if="!searchResults.length"
                        class="text-center text-[var(--text-faint)] text-[12.5px] py-[30px] px-[10px]"
                        style="padding: 12px 0"
                    >
                        Produk tidak ditemukan.
                    </div>
                    <div
                        v-for="p in searchResults"
                        :key="p.id"
                        class="card bg-[var(--surface)] border border-[var(--border)] rounded-[var(--radius)] p-[16px] flex justify-between items-center gap-3 cursor-pointer"
                        @click="addToCart(p)"
                    >
                        <div class="min-w-0">
                            <div class="font-semibold text-sm">
                                {{ p.name }}
                            </div>
                            <div
                                class="text-xs text-[var(--text-muted)] mt-0.5"
                            >
                                per {{ p.unit }}
                            </div>
                        </div>
                        <span
                            class="shrink-0 w-8 h-8 rounded-full bg-[var(--accent)] text-white flex items-center justify-center text-lg leading-none"
                            >+</span
                        >
                    </div>
                </div>

                <div
                    class="font-['Space_Grotesk'] text-[15px] font-semibold mt-[22px] mb-[10px]"
                    style="margin-top: 20px"
                >
                    Keranjang Order
                </div>
                <div v-if="cartItems.length" class="flex flex-col gap-2.5">
                    <div
                        v-for="(item, i) in cartItems"
                        :key="item.product_id"
                        class="card flex justify-between items-center bg-[var(--surface)] border border-[var(--border)] rounded-[var(--radius)] p-[16px]"
                    >
                        <div>
                            <div class="font-semibold text-sm">
                                {{ item.name }}
                            </div>
                            <div
                                class="text-xs text-[var(--text-muted)] mt-0.5"
                            >
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
                            <span
                                class="min-w-[20px] text-center font-semibold"
                                >{{ item.qty }}</span
                            >
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
                <div
                    v-else
                    class="text-center text-[var(--text-faint)] text-[12.5px] py-[30px] px-[10px]"
                >
                    Belum ada produk dipilih. Cari produk di atas untuk
                    menambahkan.
                </div>

                <div class="field mb-[16px]" style="margin-top: 16px">
                    <label
                        class="block text-[12.5px] text-[var(--text-muted)] mb-[7px] font-medium"
                        >Catatan Tambahan</label
                    >
                    <textarea
                        class="w-full bg-[var(--surface-2)] border border-[var(--border)] rounded-[10px] px-[13px] py-[12px] text-[var(--text)] text-[14.5px] outline-none resize-none h-[78px]"
                        v-model="orderNote"
                        placeholder="Contoh: minta dikirim akhir minggu"
                    ></textarea>
                </div>
            </div>

            <!--
              absolute + bottom-0, relatif ke .step2-body (relative) di atas.
              Ini yang bikin dia "sticky" nempel di bawah area konten step 2,
              bukan fixed ke viewport (yang tadi lepas dari card).
            -->
            <div
                class="order-bar flex-shrink-0 px-[18px] py-[14px] border-t border-[var(--border)] bg-[var(--surface)] flex items-center gap-[12px] absolute left-0 right-0 bottom-0 z-30"
            >
                <div
                    class="text-[12.5px] text-[var(--text-muted)] flex-shrink-0"
                >
                    Item dipilih<br /><b class="text-[var(--text)] font-mono">{{
                        cartItems.length
                    }}</b>
                </div>
                <button
                    class="btn btn-primary font-sans font-semibold text-[14.5px] rounded-[12px] border-none px-[18px] py-[14px] cursor-pointer flex items-center justify-center gap-[8px] w-full bg-[var(--accent)] text-[var(--accent-ink)] active:scale-[0.98] disabled:bg-[var(--border)] disabled:text-[var(--text-faint)] disabled:cursor-not-allowed flex-1"
                    :disabled="submitting"
                    @click="handleSubmitOrder"
                >
                    {{ submitting ? "Mengirim..." : "Kirim Request Order" }}
                </button>
            </div>
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
