<x-filament-panels::page x-data="{ lightboxSrc: null }">
    {{ $this->form }}

    @php
        $summary = $this->getSummary();
        $absensisGrouped = $this->getAbsensisGrouped();
        $selectedUser = $this->getSelectedUser();
        $visits = $selectedUser ? $this->getVisits() : collect();
        $addedCustomers = $selectedUser ? $this->getAddedCustomers() : collect();

        $fotoUrl = fn(?string $path) => $path
            ? (str_starts_with($path, 'http')
                ? $path
                : '/storage/' . ltrim($path, '/'))
            : null;
    @endphp

    {{-- Lightbox modal: klik di luar gambar (backdrop) otomatis menutup --}}
    <div x-show="lightboxSrc" x-cloak x-transition.opacity @keydown.escape.window="lightboxSrc = null"
        class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/80 p-4" style="display: none;">
        <div @click="lightboxSrc = null" class="absolute inset-0"></div>

        <img :src="lightboxSrc" class="relative max-h-[90vh] max-w-[90vw] rounded-lg shadow-2xl" />

        <button @click="lightboxSrc = null"
            class="absolute top-4 right-4 flex h-10 w-10 items-center justify-center rounded-full bg-white/10 text-white hover:bg-white/20"
            type="button">
            <x-heroicon-o-x-mark class="h-6 w-6" />
        </button>
    </div>

    <x-filament::section heading="Ringkasan Aktivitas Sales">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-50 dark:bg-white/5">
                    <tr>
                        <th class="px-3 py-2 w-6"></th>
                        <th class="px-3 py-2">Nama Sales</th>
                        <th class="px-3 py-2 text-center">Total Kunjungan</th>
                        <th class="px-3 py-2 text-center">Order</th>
                        <th class="px-3 py-2 text-center">Follow-up</th>
                        <th class="px-3 py-2 text-center">Komplain</th>
                        <th class="px-3 py-2 text-center">Toko Tutup</th>
                        <th class="px-3 py-2 text-center">Tidak Ada Respon</th>
                        <th class="px-3 py-2 text-center">Total Customer Baru</th>
                        <th class="px-3 py-2 text-center text-green-600 dark:text-green-400">🟢 Absen Masuk</th>
                        <th class="px-3 py-2 text-center text-red-600 dark:text-red-400">🔴 Absen Keluar</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($summary as $item)
                        @php $isOpen = $selectedUser?->id == $item['id']; @endphp

                        {{-- Baris ringkasan, klik untuk expand/collapse --}}
                        <tr wire:click="selectSales('{{ $isOpen ? null : $item['id'] }}')" @class([
                            'cursor-pointer border-t transition hover:bg-primary-50 dark:hover:bg-primary-500/10',
                            'bg-primary-50 dark:bg-primary-500/10' => $isOpen,
                        ])>
                            <td class="px-3 py-2 text-gray-400">
                                <x-heroicon-o-chevron-down @class(['h-4 w-4 transition-transform', 'rotate-180' => $isOpen]) />
                            </td>
                            <td class="px-3 py-2 font-medium">{{ $item['name'] }}</td>
                            <td class="px-3 py-2 text-center">{{ $item['visits_count'] }}</td>
                            <td class="px-3 py-2 text-center">{{ $item['order_count'] }}</td>
                            <td class="px-3 py-2 text-center">{{ $item['followup_count'] }}</td>
                            <td class="px-3 py-2 text-center">{{ $item['komplain_count'] }}</td>
                            <td class="px-3 py-2 text-center">{{ $item['tutup_count'] }}</td>
                            <td class="px-3 py-2 text-center">{{ $item['no_response_count'] }}</td>
                            <td class="px-3 py-2 text-center">{{ $item['added_customers_count'] }}</td>
                            <td class="px-3 py-2 text-center">
                                @if ($item['absen_masuk'])
                                    <div class="inline-flex items-center gap-1.5">
                                        <x-filament::badge color="success">{{ $item['absen_masuk'] }}</x-filament::badge>
                                        @if ($item['foto_absen_masuk'])
                                            <button type="button"
                                                @click.stop="lightboxSrc = '{{ $fotoUrl($item['foto_absen_masuk']) }}'"
                                                title="Lihat foto absen masuk"
                                                class="text-gray-400 hover:text-primary-600 transition p-0.5 rounded hover:bg-gray-100 dark:hover:bg-white/10">
                                                <x-heroicon-m-camera class="h-4 w-4" />
                                            </button>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-gray-400 text-xs">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-center">
                                @if ($item['absen_keluar'])
                                    <div class="inline-flex items-center gap-1.5">
                                        <x-filament::badge color="danger">{{ $item['absen_keluar'] }}</x-filament::badge>
                                        @if ($item['foto_absen_keluar'])
                                            <button type="button"
                                                @click.stop="lightboxSrc = '{{ $fotoUrl($item['foto_absen_keluar']) }}'"
                                                title="Lihat foto absen keluar"
                                                class="text-gray-400 hover:text-primary-600 transition p-0.5 rounded hover:bg-gray-100 dark:hover:bg-white/10">
                                                <x-heroicon-m-camera class="h-4 w-4" />
                                            </button>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-gray-400 text-xs">—</span>
                                @endif
                            </td>
                        </tr>

                        {{-- Baris detail, langsung nempel di bawah baris sales yang diklik --}}
                        @if ($isOpen)
                            @php
                                $userAbsensi = $absensisGrouped[$item['id']] ?? ['masuk' => null, 'keluar' => null];
                            @endphp
                            <tr>
                                <td colspan="11" class="bg-gray-50 p-4 dark:bg-white/5" x-data="{ tab: 'visits' }">
                                    {{-- Info Kartu Absensi Masuk & Keluar Sales --}}
                                    <div class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                        <div class="flex items-center gap-3 rounded-lg border border-gray-200 bg-white p-3 shadow-sm dark:border-white/10 dark:bg-gray-800">
                                            @if (!empty($userAbsensi['masuk']?->foto))
                                                <img src="{{ $fotoUrl($userAbsensi['masuk']->foto) }}"
                                                    @click.stop="lightboxSrc = '{{ $fotoUrl($userAbsensi['masuk']->foto) }}'"
                                                    class="h-16 w-16 flex-shrink-0 cursor-pointer rounded-lg object-cover ring-1 ring-gray-200 transition hover:ring-2 hover:ring-success-500"
                                                    alt="Foto Absen Masuk" />
                                            @else
                                                <div class="flex h-16 w-16 flex-shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-400 dark:bg-gray-700">
                                                    <x-heroicon-o-camera class="h-6 w-6" />
                                                </div>
                                            @endif
                                            <div class="min-w-0 flex-1">
                                                <div class="flex items-center gap-2">
                                                    <span class="inline-block h-2 w-2 rounded-full bg-success-500"></span>
                                                    <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Absen Masuk</span>
                                                </div>
                                                <div class="mt-1 text-base font-bold text-gray-900 dark:text-white">
                                                    {{ $userAbsensi['masuk']?->jam ?? 'Belum Absen' }}
                                                </div>
                                                @if (!empty($userAbsensi['masuk']?->catatan))
                                                    <div class="mt-0.5 truncate text-xs text-gray-500" title="{{ $userAbsensi['masuk']->catatan }}">
                                                        {{ $userAbsensi['masuk']->catatan }}
                                                    </div>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-3 rounded-lg border border-gray-200 bg-white p-3 shadow-sm dark:border-white/10 dark:bg-gray-800">
                                            @if (!empty($userAbsensi['keluar']?->foto))
                                                <img src="{{ $fotoUrl($userAbsensi['keluar']->foto) }}"
                                                    @click.stop="lightboxSrc = '{{ $fotoUrl($userAbsensi['keluar']->foto) }}'"
                                                    class="h-16 w-16 flex-shrink-0 cursor-pointer rounded-lg object-cover ring-1 ring-gray-200 transition hover:ring-2 hover:ring-danger-500"
                                                    alt="Foto Absen Keluar" />
                                            @else
                                                <div class="flex h-16 w-16 flex-shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-400 dark:bg-gray-700">
                                                    <x-heroicon-o-camera class="h-6 w-6" />
                                                </div>
                                            @endif
                                            <div class="min-w-0 flex-1">
                                                <div class="flex items-center gap-2">
                                                    <span class="inline-block h-2 w-2 rounded-full bg-danger-500"></span>
                                                    <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Absen Keluar</span>
                                                </div>
                                                <div class="mt-1 text-base font-bold text-gray-900 dark:text-white">
                                                    {{ $userAbsensi['keluar']?->jam ?? 'Belum Absen' }}
                                                </div>
                                                @if (!empty($userAbsensi['keluar']?->catatan))
                                                    <div class="mt-0.5 truncate text-xs text-gray-500" title="{{ $userAbsensi['keluar']->catatan }}">
                                                        {{ $userAbsensi['keluar']->catatan }}
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Tab switcher: Kunjungan & Customer Baru berbagi 1 tanggal yang sama --}}
                                    <div class="mb-4 flex gap-1 border-b border-gray-200 dark:border-white/10">
                                        <button type="button" @click.stop="tab = 'visits'"
                                            :class="tab === 'visits'
                                                ?
                                                'border-primary-600 text-primary-600 dark:text-primary-400' :
                                                'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'"
                                            class="-mb-px border-b-2 px-4 py-2 text-sm font-medium transition">
                                            Kunjungan
                                            <span class="ml-1 text-xs text-gray-400">({{ $visits->count() }})</span>
                                        </button>
                                        <button type="button" @click.stop="tab = 'customers'"
                                            :class="tab === 'customers'
                                                ?
                                                'border-primary-600 text-primary-600 dark:text-primary-400' :
                                                'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'"
                                            class="-mb-px border-b-2 px-4 py-2 text-sm font-medium transition">
                                            Customer Baru
                                            <span
                                                class="ml-1 text-xs text-gray-400">({{ $addedCustomers->count() }})</span>
                                        </button>
                                    </div>

                                    {{-- Tab: Daftar Kunjungan Customer --}}
                                    <div x-show="tab === 'visits'" x-cloak>
                                        <div class="overflow-x-auto">
                                            <table class="w-full text-sm text-left">
                                                <thead class="bg-gray-50 dark:bg-white/5">
                                                    <tr>
                                                        <th class="px-3 py-2">Nama Customer</th>
                                                        <th class="px-3 py-2 text-center">Jam Kunjungan</th>
                                                        <th class="px-3 py-2">Kecamatan</th>
                                                        <th class="px-3 py-2 text-center">Hasil Kunjungan</th>
                                                        <th class="px-3 py-2">Catatan</th>
                                                        <th class="px-3 py-2">Alamat Lengkap Toko</th>
                                                        <th class="px-3 py-2 text-center">Foto Selfie</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse ($visits as $visit)
                                                        <tr class="border-t">
                                                            <td class="px-3 py-2">{{ $visit->customer?->name ?? '-' }}
                                                            </td>
                                                            <td class="px-3 py-2 text-center">{{ $visit->jam }}</td>
                                                            <td class="px-3 py-2">
                                                                {{ $visit->customer?->kecamatan ?? '-' }}</td>
                                                            <td class="px-3 py-2 text-center">
                                                                <x-filament::badge :color="match ($visit->hasil) {
                                                                    'Order' => 'success',
                                                                    'Follow-up' => 'warning',
                                                                    'Komplain' => 'danger',
                                                                    'Toko Tutup' => 'gray',
                                                                    'Tidak Ada Respon' => 'info',
                                                                    default => 'gray',
                                                                }">
                                                                    {{ $visit->hasil }}
                                                                </x-filament::badge>
                                                            </td>
                                                            <td class="px-3 py-2">{{ $visit->catatan }}</td>
                                                            <td class="px-3 py-2">
                                                                {{ $visit->customer?->address ?? '-' }}</td>
                                                            <td class="px-3 py-2 text-center">
                                                                @if ($visit->foto)
                                                                    <img src="{{ $fotoUrl($visit->foto) }}"
                                                                        @click.stop="lightboxSrc = '{{ $fotoUrl($visit->foto) }}'"
                                                                        class="mx-auto h-12 w-12 cursor-pointer object-cover ring-2 ring-transparent transition hover:ring-primary-500"
                                                                        alt="Foto selfie kunjungan" />
                                                                @else
                                                                    <span class="text-gray-400">-</span>
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="7"
                                                                class="px-3 py-4 text-center italic text-gray-500">
                                                                Tidak ada data kunjungan untuk sales ini pada tanggal
                                                                ini.
                                                            </td>
                                                        </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    {{-- Tab: Penambahan Customer Baru --}}
                                    <div x-show="tab === 'customers'" x-cloak>
                                        <div class="overflow-x-auto">
                                            <table class="w-full text-sm text-left">
                                                <thead class="bg-gray-50 dark:bg-white/5">
                                                    <tr>
                                                        <th class="px-3 py-2">Nama Customer</th>
                                                        <th class="px-3 py-2">Telepon</th>
                                                        <th class="px-3 py-2 text-center">Tanggal Dibuat</th>
                                                        <th class="px-3 py-2">Alamat</th>
                                                        <th class="px-3 py-2">Kecamatan</th>
                                                        <th class="px-3 py-2">Kabupaten/Kota</th>
                                                        <th class="px-3 py-2 text-center">Foto Depan Toko</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse ($addedCustomers as $customer)
                                                        <tr class="border-t">
                                                            <td class="px-3 py-2">{{ $customer->name }}</td>
                                                            <td class="px-3 py-2">{{ $customer->phone ?? '-' }}</td>
                                                            <td class="px-3 py-2 text-center">
                                                                {{ $customer->created_at->format('H:i:s') }}</td>
                                                            <td class="px-3 py-2">{{ $customer->address ?? '-' }}</td>
                                                            <td class="px-3 py-2">{{ $customer->kecamatan ?? '-' }}
                                                            </td>
                                                            <td class="px-3 py-2">{{ $customer->kota ?? '-' }}</td>
                                                            <td class="px-3 py-2 text-center">
                                                                @if ($customer->foto)
                                                                    <img src="{{ $fotoUrl($customer->foto) }}"
                                                                        @click.stop="lightboxSrc = '{{ $fotoUrl($customer->foto) }}'"
                                                                        class="mx-auto h-12 w-12 cursor-pointer object-cover ring-2 ring-transparent transition hover:ring-primary-500"
                                                                        alt="Foto depan toko" />
                                                                @else
                                                                    <span class="text-gray-400">-</span>
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="7"
                                                                class="px-3 py-4 text-center italic text-gray-500">
                                                                Tidak ada penambahan customer baru untuk sales ini pada
                                                                tanggal ini.
                                                            </td>
                                                        </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="11" class="px-3 py-4 text-center italic text-gray-500">
                                Tidak ada aktivitas sales pada tanggal ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if (count($summary) > 0)
            <p class="mt-2 text-xs text-gray-500">Klik salah satu baris sales untuk melihat/menutup detail kunjungan
                &amp; customer barunya.</p>
        @endif
    </x-filament::section>
</x-filament-panels::page>
