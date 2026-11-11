import { ref, watch } from "vue";
import { useGeolocation } from "@vueuse/core";
import { useAuth } from "./useAuth";
import { reverseGeocode, friendlyGeoError, resetGeolocation, nowTime } from "./geocode";

// ── State global (singleton) ──────────────────────────────────────────────────
const absenMasuk  = ref(null);   // { jam: 'HH:MM', foto: url|null } | null
const absenKeluar = ref(null);   // { jam: 'HH:MM', foto: url|null } | null

// Pop-up & alert state
const showAbsenMasukPopup  = ref(false);
const showAbsenKeluarPopup = ref(false);
const showAbsenMasukAlert  = ref(false);
const showAbsenKeluarAlert = ref(false);

// Apakah sudah dicek ke server
let todayStatusFetched = false;

// Apakah pop-up sudah pernah di-dismiss (X) dalam sesi ini
let masukPopupDismissed  = false;
let keluarPopupDismissed = false;

// Geolocation singleton untuk absensi
const {
    coords: absensiGeoCoords,
    error:  absensiGeoError,
    resume: resumeAbsensiGeo,
    pause:  pauseAbsensiGeo,
} = useGeolocation({
    enableHighAccuracy: true,
    timeout: 15000,
    immediate: false,
});

// ── State untuk form absensi aktif ───────────────────────────────────────────
const absensiCoord           = ref(null);
const absensiLocating        = ref(false);
const absensiError           = ref(null);
const absensiAddress         = ref(null);
const absensiAddressLoading  = ref(false);
const absensiPhoto           = ref(null);
const absensiPhotoTempPath   = ref(null);
const absensiIsUploading     = ref(false);
const absensiUploadProgress  = ref(0);
const absensiSubmitting      = ref(false);
const absensiTipeAktif       = ref(null); // 'masuk' | 'keluar'

export function useAbsensi() {
    const { csrfToken } = useAuth();

    // ── Ambil status absensi hari ini ─────────────────────────────────────────
    async function fetchTodayStatus() {
        if (todayStatusFetched) return;
        todayStatusFetched = true;

        try {
            const res  = await fetch("/api/absensi/today");
            const data = await res.json();
            absenMasuk.value  = data.masuk  || null;
            absenKeluar.value = data.keluar || null;
            evaluatePopupState();
        } catch (e) {
            console.error("Gagal cek status absensi:", e);
        }
    }

    // ── Evaluasi apakah perlu tampilkan pop-up atau alert ────────────────────
    function evaluatePopupState() {
        const jam = new Date().getHours();

        // Absen Masuk
        if (!absenMasuk.value) {
            if (!masukPopupDismissed) {
                showAbsenMasukPopup.value  = true;
                showAbsenMasukAlert.value  = false;
                // Jika popup masuk tampil, jangan tampilkan popup keluar sekarang
                showAbsenKeluarPopup.value = false;
                showAbsenKeluarAlert.value = false;
            } else {
                showAbsenMasukPopup.value  = false;
                showAbsenMasukAlert.value  = true;
            }
            return; // Prioritas absen masuk dulu
        } else {
            showAbsenMasukPopup.value = false;
            showAbsenMasukAlert.value = false;
        }

        // Absen Keluar (jam >= 16, sudah masuk, belum keluar)
        if (absenMasuk.value && !absenKeluar.value && jam >= 16) {
            if (!keluarPopupDismissed) {
                showAbsenKeluarPopup.value = true;
                showAbsenKeluarAlert.value = false;
            } else {
                showAbsenKeluarPopup.value = false;
                showAbsenKeluarAlert.value = true;
            }
        } else {
            showAbsenKeluarPopup.value = false;
            showAbsenKeluarAlert.value = false;
        }
    }

    // ── Dismiss pop-up (klik X) ───────────────────────────────────────────────
    function dismissMasukPopup() {
        masukPopupDismissed = true;
        showAbsenMasukPopup.value = false;
        if (!absenMasuk.value) showAbsenMasukAlert.value = true;
    }

    function dismissKeluarPopup() {
        keluarPopupDismissed = true;
        showAbsenKeluarPopup.value = false;
        const jam = new Date().getHours();
        if (absenMasuk.value && !absenKeluar.value && jam >= 16) {
            showAbsenKeluarAlert.value = true;
        } else {
            showAbsenKeluarAlert.value = false;
        }
    }

    // ── Buka form absensi (set tipe & mulai GPS) ─────────────────────────────
    function bukaFormAbsensi(tipe) {
        absensiTipeAktif.value      = tipe;
        absensiCoord.value          = null;
        absensiError.value          = null;
        absensiAddress.value        = null;
        absensiAddressLoading.value = false;
        absensiPhoto.value          = null;
        absensiPhotoTempPath.value  = null;

        if (tipe === "masuk") {
            showAbsenMasukPopup.value = true;
            showAbsenKeluarPopup.value = false;
            showAbsenMasukAlert.value = false;
        } else {
            showAbsenKeluarPopup.value = true;
            showAbsenMasukPopup.value = false;
            showAbsenKeluarAlert.value = false;
        }

        // Mulai GPS
        startAbsensiGeo();
    }

    // ── GPS untuk absensi ─────────────────────────────────────────────────────
    function startAbsensiGeo() {
        absensiLocating.value = true;
        absensiCoord.value    = null;
        absensiError.value    = null;

        resetGeolocation(absensiGeoCoords, absensiGeoError);
        resumeAbsensiGeo();

        let stopCoords, stopErr;

        stopCoords = watch(
            absensiGeoCoords,
            (val) => {
                if (val.latitude !== Infinity && val.longitude !== Infinity) {
                    absensiCoord.value = {
                        lat:      val.latitude.toFixed(6),
                        lng:      val.longitude.toFixed(6),
                        accuracy: (val.accuracy ?? 0).toFixed(0),
                        time:     nowTime(),
                    };
                    absensiLocating.value = false;
                    pauseAbsensiGeo();
                    stopCoords?.();
                    stopErr?.();

                    // Ambil alamat reverse-geocode
                    absensiAddressLoading.value = true;
                    reverseGeocode(val.latitude, val.longitude).then((geo) => {
                        absensiAddress.value        = geo?.display_name || null;
                        absensiAddressLoading.value = false;
                    });
                }
            },
            { immediate: true },
        );

        stopErr = watch(
            absensiGeoError,
            (error) => {
                if (error) {
                    absensiError.value    = friendlyGeoError(error);
                    absensiLocating.value = false;
                    pauseAbsensiGeo();
                    stopCoords?.();
                    stopErr?.();
                }
            },
            { immediate: true },
        );
    }

    // ── Upload foto absensi ───────────────────────────────────────────────────
    async function uploadAbsensiFoto(file) {
        const Uppy      = (await import("@uppy/core")).default;
        const XHRUpload = (await import("@uppy/xhr-upload")).default;

        absensiIsUploading.value    = true;
        absensiUploadProgress.value = 0;

        const uppy = new Uppy({ autoProceed: false }).use(XHRUpload, {
            endpoint:  "/api/upload-temp",
            fieldName: "foto",
            headers:   { "X-CSRF-TOKEN": csrfToken.value },
        });

        uppy.on("progress", (p) => { absensiUploadProgress.value = p; });
        uppy.addFile({ name: file.name, type: file.type, data: file });

        try {
            const result = await uppy.upload();
            if (result.successful.length > 0) {
                absensiPhotoTempPath.value = result.successful[0].response.body.path;
            } else {
                alert("Upload foto absensi gagal.");
            }
        } catch (e) {
            alert("Upload foto gagal: " + e.message);
        } finally {
            absensiIsUploading.value = false;
        }
    }

    // ── Simpan absensi ke server ──────────────────────────────────────────────
    async function saveAbsensi() {
        if (!absensiCoord.value) {
            alert("Lokasi GPS belum berhasil didapatkan. Coba ambil ulang lokasi.");
            return { success: false };
        }
        if (!absensiPhotoTempPath.value) {
            alert("Foto wajib diambil sebelum menyimpan absensi.");
            return { success: false };
        }

        absensiSubmitting.value = true;

        const formData = new FormData();
        formData.append("tipe",           absensiTipeAktif.value);
        formData.append("latitude",        absensiCoord.value.lat);
        formData.append("longitude",       absensiCoord.value.lng);
        formData.append("accuracy",        absensiCoord.value.accuracy);
        if (absensiAddress.value) formData.append("alamat_text", absensiAddress.value);
        formData.append("foto_temp_path",  absensiPhotoTempPath.value);

        try {
            const res  = await fetch("/api/absensi", {
                method:  "POST",
                headers: { "X-CSRF-TOKEN": csrfToken.value },
                body:    formData,
            });
            const data = await res.json();

            absensiSubmitting.value = false;

            if (data.success) {
                const tipe = absensiTipeAktif.value;
                if (tipe === "masuk") {
                    absenMasuk.value          = { jam: data.absensi.jam, foto: null };
                    showAbsenMasukPopup.value  = false;
                    showAbsenMasukAlert.value  = false;
                } else {
                    absenKeluar.value          = { jam: data.absensi.jam, foto: null };
                    showAbsenKeluarPopup.value = false;
                    showAbsenKeluarAlert.value = false;
                }
                absensiTipeAktif.value = null;
            } else {
                alert(data.message || "Gagal menyimpan absensi.");
            }

            return data;
        } catch (err) {
            absensiSubmitting.value = false;
            console.error(err);
            alert("Terjadi kesalahan koneksi.");
            return { success: false };
        }
    }

    // ── Batalkan form absensi ─────────────────────────────────────────────────
    function batalAbsensi() {
        absensiTipeAktif.value = null;
        pauseAbsensiGeo();
    }

    return {
        absenMasuk,
        absenKeluar,
        showAbsenMasukPopup,
        showAbsenKeluarPopup,
        showAbsenMasukAlert,
        showAbsenKeluarAlert,
        absensiCoord,
        absensiLocating,
        absensiError,
        absensiAddress,
        absensiAddressLoading,
        absensiPhoto,
        absensiPhotoTempPath,
        absensiIsUploading,
        absensiUploadProgress,
        absensiSubmitting,
        absensiTipeAktif,
        fetchTodayStatus,
        evaluatePopupState,
        dismissMasukPopup,
        dismissKeluarPopup,
        bukaFormAbsensi,
        startAbsensiGeo,
        uploadAbsensiFoto,
        saveAbsensi,
        batalAbsensi,
    };
}
