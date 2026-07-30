export async function reverseGeocode(lat, lng) {
    try {
        const url = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1&email=admin@wijayaplywood.com`;
        const res = await fetch(url, {
            headers: { "Accept-Language": "id" },
        });
        if (!res.ok) throw new Error("Gagal mengambil alamat dari Nominatim");
        const data = await res.json();
        const addr = data.address || {};

        const desa =
            addr.village ||
            addr.hamlet ||
            addr.suburb ||
            addr.neighbourhood ||
            addr.quarter ||
            null;

        const kecamatan =
            addr.city_district ||
            addr.subdistrict ||
            addr.district ||
            (addr.town && addr.town !== desa ? addr.town : null) ||
            null;

        const kota =
            addr.county ||
            addr.city ||
            addr.regency ||
            addr.municipality ||
            (addr.town && addr.town !== kecamatan ? addr.town : null) ||
            null;

        return {
            display_name: data.display_name || null,
            desa,
            kecamatan,
            kota,
        };
    } catch (err) {
        console.warn("Reverse geocode gagal:", err);
        return null;
    }
}

export function friendlyGeoError(error) {
    if (!error) return null;
    if (error.code === error.PERMISSION_DENIED) {
        return "Izin lokasi ditolak. Aktifkan izin lokasi di pengaturan browser/HP untuk melanjutkan.";
    }
    if (error.code === error.TIMEOUT) {
        return "Waktu pencarian lokasi habis. Pastikan GPS aktif dan sinyal bagus, lalu coba lagi.";
    }
    if (error.code === error.POSITION_UNAVAILABLE) {
        return "Lokasi tidak tersedia. Pastikan GPS aktif, lalu coba lagi.";
    }
    return "Gagal mendapatkan lokasi: " + error.message;
}

export function nowTime() {
    const d = new Date();
    return (
        String(d.getHours()).padStart(2, "0") +
        ":" +
        String(d.getMinutes()).padStart(2, "0")
    );
}

export function resetGeolocation(coordsRef, errorRef) {
    errorRef.value = null;
    coordsRef.value = {
        ...coordsRef.value,
        latitude: Infinity,
        longitude: Infinity,
    };
}
