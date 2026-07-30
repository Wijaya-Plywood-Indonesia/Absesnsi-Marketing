import { ref, computed } from "vue";
import { useRoute } from "vue-router";

const customerList = ref([]);
const searchQuery = ref("");
const activeFilter = ref("Semua");
let initialized = false;

function formatAddr(c) {
    const parts = [c.jalan, c.desa, c.kecamatan, c.kota].filter(Boolean);
    if (parts.length) return parts.join(", ");
    return c.address || null;
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

export function useCustomers() {
    const route = useRoute();

    const currentCustomerId = computed(() => {
        const id = route.params.id;
        return id ? parseInt(id, 10) : null;
    });

    const currentCustomer = computed(() =>
        customerList.value.find((c) => c.id === currentCustomerId.value),
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
    };
}
