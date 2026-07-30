import { ref, watch } from "vue";
import { useGeolocation } from "@vueuse/core";
import { useRouter } from "vue-router";
import { useAuth } from "./useAuth";
import { useCustomers } from "./useCustomers";
import { reverseGeocode, friendlyGeoError, resetGeolocation } from "./geocode";

const ncName = ref("");
const ncPhone = ref("");
const ncJalan = ref("");
const ncDesa = ref("");
const ncKecamatan = ref("");
const ncKota = ref("");
const ncCoord = ref(null);
const ncLocating = ref(false);
const ncJenis = ref("Mebel");
const ncJenisLain = ref("");
const submitting = ref(false);

// Singleton geolocation watch — kalau dibuat di dalam useNewCustomerForm(),
// tiap komponen yang memanggilnya bakal bikin instance useGeolocation baru.
const {
    coords: ncGeoCoords,
    error: ncGeoError,
    resume: resumeNcGeo,
    pause: pauseNcGeo,
} = useGeolocation({
    enableHighAccuracy: true,
    timeout: 15000,
    immediate: false,
});

export function useNewCustomerForm() {
    const router = useRouter();
    const { csrfToken } = useAuth();
    const { formatAddr, addCustomer } = useCustomers();

    function locateNewCustomer() {
        ncLocating.value = true;
        ncCoord.value = null;

        resetGeolocation(ncGeoCoords, ncGeoError);
        resumeNcGeo();

        let stopCoords, stopErr;

        stopCoords = watch(
            ncGeoCoords,
            async (val) => {
                if (val.latitude !== Infinity && val.longitude !== Infinity) {
                    const lat = val.latitude.toFixed(6);
                    const lng = val.longitude.toFixed(6);

                    ncCoord.value = { lat, lng, address: null };
                    ncLocating.value = false;
                    pauseNcGeo();
                    stopCoords?.();
                    stopErr?.();

                    const geo = await reverseGeocode(lat, lng);
                    if (ncCoord.value) {
                        ncCoord.value = {
                            ...ncCoord.value,
                            address: geo?.display_name || null,
                        };
                    }
                    if (geo) {
                        if (geo.desa && !ncDesa.value.trim()) {
                            ncDesa.value = geo.desa;
                        }
                        if (geo.kecamatan && !ncKecamatan.value.trim()) {
                            ncKecamatan.value = geo.kecamatan;
                        }
                        if (geo.kota && !ncKota.value.trim()) {
                            ncKota.value = geo.kota;
                        }
                    }
                }
            },
            { immediate: true },
        );

        stopErr = watch(
            ncGeoError,
            (error) => {
                if (error) {
                    alert(friendlyGeoError(error));
                    ncLocating.value = false;
                    pauseNcGeo();
                    stopCoords?.();
                    stopErr?.();
                }
            },
            { immediate: true },
        );
    }

    function saveCustomer() {
        if (!ncName.value.trim()) {
            alert("Nama toko wajib diisi.");
            return;
        }

        if (!ncCoord.value) {
            alert(
                "Lokasi toko belum diambil. Tekan 'Ambil Lokasi Sekarang' terlebih dahulu.",
            );
            return;
        }

        submitting.value = true;

        const lat = ncCoord.value.lat;
        const lng = ncCoord.value.lng;
        const jenisValue =
            ncJenis.value === "Lainnya"
                ? ncJenisLain.value || "Lainnya"
                : ncJenis.value;

        fetch("/api/customer", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrfToken.value,
            },
            body: JSON.stringify({
                name: ncName.value,
                phone: ncPhone.value,
                jalan: ncJalan.value,
                desa: ncDesa.value,
                kecamatan: ncKecamatan.value,
                kota: ncKota.value,
                pola: "Eceran",
                jenis: jenisValue,
                latitude: lat,
                longitude: lng,
            }),
        })
            .then((res) => res.json())
            .then((data) => {
                submitting.value = false;
                if (data.success) {
                    const newCustId = data.customer.id;

                    addCustomer({
                        id: newCustId,
                        name: ncName.value,
                        addr:
                            formatAddr({
                                jalan: ncJalan.value,
                                desa: ncDesa.value,
                                kecamatan: ncKecamatan.value,
                                kota: ncKota.value,
                            }) || "-",
                        phone: ncPhone.value || "-",
                        status: "Baru",
                        pola: "Eceran",
                        jenis: jenisValue,
                        lat,
                        lng,
                    });

                    ncName.value = "";
                    ncPhone.value = "";
                    ncJalan.value = "";
                    ncDesa.value = "";
                    ncKecamatan.value = "";
                    ncKota.value = "";
                    ncCoord.value = null;

                    router.push({
                        name: "checkinLocation",
                        params: { id: newCustId },
                    });
                } else {
                    alert("Gagal mendaftarkan customer.");
                }
            })
            .catch((err) => {
                submitting.value = false;
                console.error(err);
                alert("Terjadi kesalahan koneksi.");
            });
    }

    return {
        ncName,
        ncPhone,
        ncJalan,
        ncDesa,
        ncKecamatan,
        ncKota,
        ncCoord,
        ncLocating,
        ncJenis,
        ncJenisLain,
        submitting,
        locateNewCustomer,
        saveCustomer,
    };
}
