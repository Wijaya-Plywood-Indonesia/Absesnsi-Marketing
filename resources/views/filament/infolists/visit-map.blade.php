@php
    $mapId = 'visit-map-' . $record->getKey();
    $visitLat = $record->latitude;
    $visitLng = $record->longitude;
    $accuracy = $record->accuracy;
    $storeLat = $record->customer?->latitude;
    $storeLng = $record->customer?->longitude;

    // Samain radius toleransi dengan yang dipakai di app mobile (CHECKIN_RADIUS_METERS).
    $checkinRadius = 100;
@endphp
<div id="{{ $mapId }}" wire:ignore x-data="{}" x-init="const visitLat = {{ $visitLat !== null ? $visitLat : 'null' }};
const visitLng = {{ $visitLng !== null ? $visitLng : 'null' }};
const accuracy = {{ $accuracy !== null ? (float) $accuracy : 'null' }};
const storeLat = {{ $storeLat !== null ? $storeLat : 'null' }};
const storeLng = {{ $storeLng !== null ? $storeLng : 'null' }};
const checkinRadius = {{ $checkinRadius }};
const el = $el;

if (visitLat === null || visitLng === null) {
    el.style.height = 'auto';
    el.textContent = 'Data lokasi kunjungan tidak tersedia.';
    el.classList.add('text-sm', 'text-gray-500');
} else {
    function ensureLeaflet(cb) {
        // Tunggu CSS *dan* JS sama-sama selesai sebelum init map — kalau
        // CSS belum kelar, pane/circle render kacau pas render pertama.
        let cssReady = !!window.__leafletCssLoaded;
        let jsReady = !!window.L;

        function tryDone() {
            if (cssReady && jsReady) cb();
        }

        if (!window.__leafletCssAdded) {
            window.__leafletCssAdded = true;
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
            link.onload = function() {
                window.__leafletCssLoaded = true;
                cssReady = true;
                tryDone();
            };
            document.head.appendChild(link);
        } else if (!cssReady) {
            const iv = setInterval(function() {
                if (window.__leafletCssLoaded) {
                    clearInterval(iv);
                    cssReady = true;
                    tryDone();
                }
            }, 50);
        }

        if (!window.L) {
            if (!window.__leafletJsLoading) {
                window.__leafletJsLoading = true;
                const s = document.createElement('script');
                s.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
                s.onload = function() {
                    // Set path icon default eksplisit ke CDN — kalau nggak,
                    // Leaflet nyari 'images/marker-icon.png' relatif yang
                    // nggak valid pas dimuat dari CDN, jadi marker sales blank.
                    L.Icon.Default.mergeOptions({
                        iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
                        iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
                        shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
                    });
                    jsReady = true;
                    tryDone();
                };
                document.head.appendChild(s);
            } else {
                const iv = setInterval(function() {
                    if (window.L) {
                        clearInterval(iv);
                        jsReady = true;
                        tryDone();
                    }
                }, 50);
            }
        } else {
            tryDone();
        }
    }

    function buildMap() {
        if (el._leaflet_id) return;

        const visit = [visitLat, visitLng];
        const map = L.map(el, {
            zoomControl: true,
            attributionControl: false,
        }).setView(visit, 17);

        function addLayersAndFit() {
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
            }).addTo(map);

            // Marker posisi kunjungan (default Leaflet pin, biar konsisten sama app mobile).
            L.marker(visit).addTo(map).bindPopup('Posisi Kunjungan Sales');

            // Lingkaran akurasi GPS di titik kunjungan.
            if (accuracy !== null && accuracy > 0) {
                L.circle(visit, {
                    radius: accuracy,
                    color: '#3b82f6',
                    fillColor: '#3b82f6',
                    fillOpacity: 0.15,
                    weight: 1.5,
                }).addTo(map);
            }

            if (storeLat !== null && storeLng !== null) {
                const store = [storeLat, storeLng];

                const storeIcon = L.divIcon({
                    className: 'store-marker-icon',
                    html: '<div style=\'font-size:20px;line-height:32px;text-align:center;\'>🏬</div>',
                    iconSize: [32, 32],
                    iconAnchor: [16, 32],
                });
                L.marker(store, { icon: storeIcon }).addTo(map).bindPopup('Posisi Toko Customer');

                // Lingkaran radius toleransi check-in di sekitar toko.
                L.circle(store, {
                    radius: checkinRadius,
                    color: '#f97316',
                    fillColor: '#f97316',
                    fillOpacity: 0.08,
                    weight: 1.5,
                    dashArray: '4 4',
                }).addTo(map);

                map.fitBounds(L.latLngBounds([visit, store]), { padding: [30, 30], maxZoom: 17 });
            }

            // Reflow terakhir buat jaga-jaga kalau container masih resize
            // (mis. section infolist animasi collapse/expand).
            setTimeout(function() {
                map.invalidateSize();
            }, 300);
        }

        // Paksa container settle ke ukuran final DULU (dua frame animasi),
        // baru tambahkan tile/marker/circle. Kalau circle ditambah waktu
        // container belum settle, SVG renderer Leaflet nge-cache viewport
        // lama dan radius baru kelihatan pas ada event zoom/pan — makanya
        // sebelumnya cuma muncul pas di-zoom out.
        requestAnimationFrame(function() {
            requestAnimationFrame(function() {
                map.invalidateSize();

                // Paksa z-index tiap pane Leaflet secara eksplisit — kalau
                // ada reset CSS (Tailwind/Filament) yang nimpa aturan bawaan
                // leaflet.css, overlayPane (tempat circle digambar) bisa
                // ketutup tilePane meskipun secara DOM sudah di atas.
                map.getPane('tilePane').style.zIndex = 200;
                map.getPane('overlayPane').style.zIndex = 400;
                map.getPane('shadowPane').style.zIndex = 500;
                map.getPane('markerPane').style.zIndex = 600;
                map.getPane('tooltipPane').style.zIndex = 650;
                map.getPane('popupPane').style.zIndex = 700;

                addLayersAndFit();
            });
        });
    }

    ensureLeaflet(buildMap);
}"
    style="height: 400px; width: 100%; border-radius: 0.5rem; position: relative;"></div>
