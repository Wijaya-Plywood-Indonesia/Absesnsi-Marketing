import { ref, computed, watch } from "vue";
import { useRoute } from "vue-router";

const customerList = ref([]);
const searchQuery = ref("");
const activeFilter = ref("Semua");
let initialized = false;

// ubah jadi state module-level yang bisa di-set manual
const currentCustomerId = ref(null);

function setCurrentCustomer(id) {
    currentCustomerId.value = id ?? null;
}

function formatAddr(c) {
    if (c.address) return c.address;
    const parts = [c.kecamatan, c.kota].filter(Boolean);
    return parts.length ? parts.join(", ") : null;
}

function initCustomers(initialCustomers) {
    if (initialized) return;
    customerList.value = initialCustomers.map((c) => ({
        id: c.id,
        name: c.name,
        addr: formatAddr(c) || "-",
        phone: c.phone || "-",
        status: c.status,
        pola: c.pola,
        jenis: c.jenis,
        lat: c.latitude || null,
        lng: c.longitude || null,
    }));
    initialized = true;
}

function getCustomerName(id) {
    const c = customerList.value.find((x) => x.id === id);
    return c ? c.name : "-";
}

function addCustomer(cust) {
    customerList.value.unshift(cust);
}

const filteredCustomers = computed(() => {
    const q = searchQuery.value.toLowerCase();
    return customerList.value.filter((c) => {
        const matchesQuery = c.name.toLowerCase().includes(q);
        const matchesFilter =
            activeFilter.value === "Semua" || c.status === activeFilter.value;
        return matchesQuery && matchesFilter;
    });
});

const plannedCustomers = computed(() => customerList.value.slice(0, 3));

const currentCustomer = computed(() =>
    customerList.value.find((c) => c.id === currentCustomerId.value),
);

export function useCustomers() {
    const route = useRoute();

    // sinkronkan currentCustomerId dari route param :id
    // setiap kali route punya param id (mis. masuk dari /customer-detail/5)
    watch(
        () => route.params.id,
        (id) => {
            if (id) {
                currentCustomerId.value = parseInt(id, 10);
            }
        },
        { immediate: true },
    );

    return {
        customerList,
        searchQuery,
        activeFilter,
        filteredCustomers,
        plannedCustomers,
        currentCustomerId,
        currentCustomer,
        initCustomers,
        getCustomerName,
        formatAddr,
        addCustomer,
        setCurrentCustomer, // <-- baru
    };
}
