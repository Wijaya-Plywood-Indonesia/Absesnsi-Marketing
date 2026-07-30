import { ref, computed } from "vue";

const visitList = ref([]);
let initialized = false;

function formatVisitDate(tanggal) {
    const todayStr = new Date().toISOString().split("T")[0];
    if (tanggal === todayStr) return "Hari ini";
    const dObj = new Date(tanggal);
    const months = [
        "Jan", "Feb", "Mar", "Apr", "Mei", "Jun",
        "Jul", "Agu", "Sep", "Okt", "Nov", "Des",
    ];
    return dObj.getDate() + " " + months[dObj.getMonth()];
}

function initVisits(initialVisits) {
    if (initialized) return;
    visitList.value = initialVisits.map((v) => ({
        id: v.id,
        custId: v.customer_id,
        date: formatVisitDate(v.tanggal),
        time: v.jam,
        hasil: v.hasil,
        note: v.catatan,
        rawDate: v.tanggal,
    }));
    initialized = true;
}

function addVisit(visit) {
    visitList.value.unshift(visit);
}

function updateVisit(id, patch) {
    const idx = visitList.value.findIndex((v) => v.id === id);
    if (idx !== -1) {
        visitList.value[idx] = { ...visitList.value[idx], ...patch };
    }
}

function hasVisitedToday(id) {
    return visitList.value.some(
        (v) => v.custId === id && v.date === "Hari ini",
    );
}

function customerVisitsFor(idRef) {
    return computed(() =>
        visitList.value.filter((v) => v.custId === idRef.value),
    );
}

const recentVisits = computed(() => visitList.value.slice(0, 3));

const totalVisitsThisMonth = computed(() => {
    const currentYearMonth = new Date().toISOString().slice(0, 7);
    return visitList.value.filter(
        (v) => v.rawDate && v.rawDate.startsWith(currentYearMonth),
    ).length;
});

const stats = computed(() => {
    const doneToday = visitList.value.filter(
        (v) => v.date === "Hari ini",
    ).length;
    const orderToday = visitList.value.filter(
        (v) => v.hasil === "Order",
    ).length;
    return { doneToday, orderToday };
});

export function useVisits() {
    return {
        visitList,
        recentVisits,
        totalVisitsThisMonth,
        stats,
        initVisits,
        addVisit,
        updateVisit,
        hasVisitedToday,
        customerVisitsFor,
    };
}
