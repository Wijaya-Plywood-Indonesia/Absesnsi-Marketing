<template>
    <div
        class="w-full h-dvh bg-[var(--bg-app)] overflow-hidden relative flex flex-col md:max-w-[480px] md:h-[92vh] md:border md:border-[var(--border)] md:rounded-[20px] md:shadow-[0_10px_40px_rgba(0,0,0,0.8)] md:my-auto"
    >
        <form
            id="logout-form"
            action="/logout"
            method="POST"
            style="display: none"
        >
            <input type="hidden" name="_token" :value="csrfToken" />
        </form>

        <div class="app-header"></div>
        <div class="brand-bar" v-if="shouldShowBrandBar">
            <div class="brand-logo">
                <span class="logo-dot"></span>
                WIJAYA PLYWOOD
            </div>
            <div class="brand-actions">
                <div class="mini-avatar" @click="nav('profile')">
                    {{ getInitials(user.name) }}
                </div>
            </div>
        </div>

        <router-view />

        <!-- Global Bottom Nav for Tab Screens -->
        <bottom-nav
            v-if="
                ['home', 'customers', 'order', 'history', 'profile'].includes(
                    currentScreen,
                )
            "
            :current-tab="currentScreen"
            @nav="nav"
        />
    </div>
</template>

<script setup>
import { computed, onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import BottomNav from "./Components/BottomNav.vue";
import { useAuth } from "./composables/useAuth";
import { useCustomers } from "./composables/useCustomers";
import { useVisits } from "./composables/useVisits";
import { useOrders } from "./composables/useOrders";
import { useAppNav } from "./composables/useAppNav";

const props = defineProps({
    initialUser: { type: Object, required: true },
    initialCustomers: { type: Array, required: true },
    initialVisits: { type: Array, required: true },
    initialOrders: { type: Array, required: true },
    initialProducts: { type: Array, required: true },
    csrfToken: { type: String, required: true },
});

const route = useRoute();
const router = useRouter();

const { user, csrfToken, initAuth, getInitials } = useAuth();
const { customerList, initCustomers } = useCustomers();
const { initVisits } = useVisits();
const { initOrders, initProducts } = useOrders();
const { nav } = useAppNav();

initAuth(props.initialUser, props.csrfToken);
initCustomers(props.initialCustomers);
initVisits(props.initialVisits);
initOrders(props.initialOrders);
initProducts(props.initialProducts);

const currentScreen = computed(() => route.name);

const shouldShowBrandBar = computed(() => {
    return ![
        "successStamp",
        "checkinLocation",
        "checkinResult",
        "newCustomer",
        "orderForm",
    ].includes(currentScreen.value);
});

// Guard: validasi id customer untuk route checkinLocation, checkinResult,
// dan customerDetail. Dijalankan SEBELUM navigasi selesai supaya tidak ada
// render sesaat dengan data kosong.
router.beforeEach((to, from, next) => {
    const checkinRouteNames = ["checkinLocation", "checkinResult"];
    if (checkinRouteNames.includes(to.name) || to.name === "customerDetail") {
        const id = to.params.id ? parseInt(to.params.id, 10) : null;
        const exists = id && customerList.value.some((c) => c.id === id);
        if (!exists) {
            next({ name: "customers" });
            return;
        }
    }
    next();
});

onMounted(async () => {
    await ensureGeoPermissionPrimed();
});

async function ensureGeoPermissionPrimed() {
    if (!("geolocation" in navigator)) return;

    try {
        if (navigator.permissions) {
            const status = await navigator.permissions.query({
                name: "geolocation",
            });
            if (status.state === "granted" || status.state === "denied") {
                return;
            }
        }
        navigator.geolocation.getCurrentPosition(
            () => {},
            () => {},
            { timeout: 8000 },
        );
    } catch (err) {
        console.warn("Gagal cek/priming permission geolocation:", err);
    }
}
</script>
