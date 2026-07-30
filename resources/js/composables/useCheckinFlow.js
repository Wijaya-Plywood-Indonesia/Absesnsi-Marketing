import { ref, computed, watch } from "vue";
import { useGeolocation } from "@vueuse/core";
import { useRouter } from "vue-router";
import { useAuth } from "./useAuth";
import { useCustomers } from "./useCustomers";
import { useVisits } from "./useVisits";
import { reverseGeocode, friendlyGeoError, resetGeolocation, nowTime } from "./geocode";

const checkinCoord = ref(null);
const checkinLocating = ref(false);
const checkinError = ref(null);
const checkinAddress = ref(null);
const checkinAddressLoading = ref(false);
// Customer id pemilik checkinCoord saat ini — dipakai buat deteksi kalau
// checkinCoord ternyata nyasar dari sesi customer lain.
const checkinCoordCustomerId = ref(null);
const ciHasil = ref("Order");
const ciNote = ref("");
const checkinPhoto = ref(null);
const existingVisitId = ref(null);
const existingPhotoUrl = ref(null);
const submitting = ref(false);

// Singleton geolocation watch — instance dibuat sekali di module scope,
// bukan tiap kali useCheckinFlow() dipanggil dari komponen berbeda.
const {
    coords: checkinGeoCoords,
    error: checkinGeoError,
    resume: resumeCheckinGeo,
    pause: pauseCheckinGeo,
} = useGeolocation({
    enableHighAccuracy: true,
    timeout: 15000,
    immediate: false,
});

export function useCheckinFlow() {
    const router = useRouter();
    const { csrfToken } = useAuth();
    const { currentCustomerId, currentCustomer } = useCustomers();
    const { addVisit, updateVisit } = useVisits();

    const isEditMode = computed(() => existingVisitId.value !== null);

    // Dipanggil saat masuk step 1 (entry point checkin baru).
    function resetCheckinForm() {
        checkinCoord.value = null;
        checkinError.value = null;
        checkinAddress.value = null;
        checkinAddressLoading.value = false;
        checkinCoordCustomerId.value = null;
        checkinPhoto.value = null;
        existingVisitId.value = null;
        existingPhotoUrl.value = null;
        ciHasil.value = "Order";
        ciNote.value = "";
    }

    async function fetchCheckinAddress(lat, lng) {
        checkinAddressLoading.value = true;
        checkinAddress.value = null;

        const geo = await reverseGeocode(lat, lng);

        // Kalau user keburu pindah customer/keluar sebelum fetch selesai, buang hasilnya.
        if (!checkinCoord.value) {
            checkinAddressLoading.value = false;
            return;
        }

        checkinAddress.value = geo?.display_name || null;
        checkinAddressLoading.value = false;
    }

    function startCheckinGeo() {
        checkinLocating.value = true;
        checkinCoord.value = null;
        checkinError.value = null;

        resetGeolocation(checkinGeoCoords, checkinGeoError);
        resumeCheckinGeo();

        let stopCoords, stopErr;

        stopCoords = watch(
            checkinGeoCoords,
            (val) => {
                if (val.latitude !== Infinity && val.longitude !== Infinity) {
                    checkinCoord.value = {
                        lat: val.latitude.toFixed(6),
                        lng: val.longitude.toFixed(6),
                        accuracy: (val.accuracy ?? 0).toFixed(0),
                        time: nowTime(),
                    };
                    checkinCoordCustomerId.value = currentCustomerId.value;
                    checkinLocating.value = false;
                    pauseCheckinGeo();
                    stopCoords?.();
                    stopErr?.();

                    fetchCheckinAddress(val.latitude, val.longitude);
                }
            },
            { immediate: true },
        );

        stopErr = watch(
            checkinGeoError,
            (error) => {
                if (error) {
                    console.warn("Geolocation error (checkin):", error);
                    checkinError.value = friendlyGeoError(error);
                    checkinLocating.value = false;
                    pauseCheckinGeo();
                    stopCoords?.();
                    stopErr?.();
                }
            },
            { immediate: true },
        );
    }

    function fetchTodayVisit() {
        if (!currentCustomerId.value) return;

        fetch(`/api/visits/today/${currentCustomerId.value}`)
            .then((res) => res.json())
            .then((data) => {
                if (data.exists) {
                    existingVisitId.value = data.visit.id;
                    ciHasil.value = data.visit.hasil;
                    ciNote.value = data.visit.catatan;
                    existingPhotoUrl.value = data.visit.foto || null;

                    // "Edit Kunjungan" masuk langsung ke step 2 tanpa lewat step 1,
                    // jadi GPS/alamat belum pernah diambil di sesi ini. Reverse-geocode
                    // pakai koordinat kunjungan yang SUDAH tersimpan, supaya watermark
                    // foto tetap dapat alamat saat edit.
                    if (
                        !checkinCoord.value &&
                        data.visit.latitude &&
                        data.visit.longitude
                    ) {
                        checkinCoord.value = {
                            lat: data.visit.latitude,
                            lng: data.visit.longitude,
                            accuracy: null,
                            time: null,
                        };
                        checkinCoordCustomerId.value = currentCustomerId.value;
                        fetchCheckinAddress(
                            parseFloat(data.visit.latitude),
                            parseFloat(data.visit.longitude),
                        );
                    }
                } else {
                    existingVisitId.value = null;
                    existingPhotoUrl.value = null;
                }
            })
            .catch((err) => {
                console.error("Gagal cek visit hari ini:", err);
            });
    }

    // Dipanggil saat masuk step 2 (route checkinResult).
    function enterCheckinResult() {
        // checkinCoord/checkinAddress cuma valid dipakai kalau memang milik
        // customer yang SAMA. Kalau beda (mis. "Edit Kunjungan" loncat langsung
        // ke sini tanpa lewat step 1), reset dan ambil ulang dari data visit.
        if (checkinCoordCustomerId.value !== currentCustomerId.value) {
            checkinCoord.value = null;
            checkinAddress.value = null;
            checkinAddressLoading.value = false;
        }
        fetchTodayVisit();
    }

    function goToCheckinStep(step) {
        const routeName = step === 2 ? "checkinResult" : "checkinLocation";
        router.push({ name: routeName, params: { id: currentCustomerId.value } });
    }

    async function saveCheckin() {
        if (!currentCustomerId.value) return { success: false };

        // ===== Mode edit (checkin sudah ada hari ini) =====
        if (isEditMode.value) {
            submitting.value = true;

            const formData = new FormData();
            formData.append("hasil", ciHasil.value);
            formData.append("catatan", ciNote.value || "Tanpa catatan tambahan.");
            if (checkinAddress.value) {
                formData.append("alamat_text", checkinAddress.value);
            }
            if (checkinPhoto.value) {
                formData.append("foto", checkinPhoto.value);
            }

            try {
                const res = await fetch(
                    `/api/checkin/${existingVisitId.value}/update`,
                    {
                        method: "POST",
                        headers: { "X-CSRF-TOKEN": csrfToken.value },
                        body: formData,
                    },
                );
                const data = await res.json();
                submitting.value = false;

                if (data.success) {
                    updateVisit(existingVisitId.value, {
                        hasil: ciHasil.value,
                        note: ciNote.value || "Tanpa catatan tambahan.",
                    });

                    return {
                        success: true,
                        title: "TERUPDATE",
                        isOrder: false,
                        details: [
                            { k: "Customer", v: currentCustomer.value.name },
                            { k: "Hasil", v: ciHasil.value },
                        ],
                    };
                }

                alert(data.message || "Gagal mengupdate kunjungan.");
                return { success: false };
            } catch (err) {
                submitting.value = false;
                console.error(err);
                alert("Terjadi kesalahan koneksi.");
                return { success: false };
            }
        }

        // ===== Mode create (checkin baru) =====
        if (!checkinCoord.value) {
            alert(
                "Lokasi GPS belum berhasil didapatkan. Coba ambil ulang lokasi sebelum menyimpan.",
            );
            return { success: false };
        }

        submitting.value = true;

        let alamatText = checkinAddress.value;
        if (!alamatText && !checkinAddressLoading.value) {
            const geo = await reverseGeocode(
                checkinCoord.value.lat,
                checkinCoord.value.lng,
            );
            alamatText = geo?.display_name || null;
        }

        const formData = new FormData();
        formData.append("customer_id", currentCustomerId.value);
        formData.append("hasil", ciHasil.value);
        formData.append("catatan", ciNote.value || "Tanpa catatan tambahan.");
        formData.append("latitude", checkinCoord.value.lat);
        formData.append("longitude", checkinCoord.value.lng);
        formData.append("accuracy", checkinCoord.value.accuracy);
        if (alamatText) {
            formData.append("alamat_text", alamatText);
        }
        if (checkinPhoto.value) {
            formData.append("foto", checkinPhoto.value);
        }

        try {
            const res = await fetch("/api/checkin", {
                method: "POST",
                headers: { "X-CSRF-TOKEN": csrfToken.value },
                body: formData,
            });
            const data = await res.json();
            submitting.value = false;

            if (data.success) {
                addVisit({
                    id: data.visit.id,
                    custId: currentCustomerId.value,
                    date: "Hari ini",
                    time: data.visit.jam,
                    hasil: ciHasil.value,
                    note: ciNote.value || "Tanpa catatan tambahan.",
                    rawDate:
                        data.visit.tanggal ||
                        new Date().toISOString().split("T")[0],
                });

                return {
                    success: true,
                    title: "TERCATAT",
                    isOrder: false,
                    details: [
                        { k: "Customer", v: currentCustomer.value.name },
                        { k: "Hasil", v: ciHasil.value },
                        {
                            k: "Lokasi",
                            v:
                                alamatText ||
                                `${checkinCoord.value.lat}, ${checkinCoord.value.lng}`,
                        },
                        { k: "Jam", v: data.visit.jam },
                    ],
                };
            }

            if (data.existing_visit_id) {
                alert(data.message);
                router.push({
                    name: "checkinResult",
                    params: { id: currentCustomerId.value },
                });
            } else {
                alert(data.message || "Gagal menyimpan kunjungan.");
            }
            return { success: false };
        } catch (err) {
            submitting.value = false;
            console.error(err);
            alert("Terjadi kesalahan koneksi.");
            return { success: false };
        }
    }

    return {
        checkinCoord,
        checkinLocating,
        checkinError,
        checkinAddress,
        checkinAddressLoading,
        ciHasil,
        ciNote,
        checkinPhoto,
        existingPhotoUrl,
        isEditMode,
        submitting,
        resetCheckinForm,
        startCheckinGeo,
        enterCheckinResult,
        goToCheckinStep,
        saveCheckin,
    };
}
