<x-filament-panels::page x-data="{ lightboxSrc: null }">
    {{ $this->form }}

    @php
        $summary = $this->getSummary();
        $selectedUser = $this->getSelectedUser();
        $visits = $selectedUser ? $this->getVisits() : collect();
        $addedCustomers = $selectedUser ? $this->getAddedCustomers() : collect();

        $fotoUrl = fn(?string $path) => $path
            ? (str_starts_with($path, 'http')
                ? $path
                : \Illuminate\Support\Facades\Storage::disk('public')->url($path))
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
                        </tr>

                        {{-- Baris detail, langsung nempel di bawah baris sales yang diklik --}}
                        @if ($isOpen)
                            <tr>
                                <td colspan="9" class="bg-gray-50 p-4 dark:bg-white/5" x-data="{ tab: 'visits' }">
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
                                        <div class="overflow-x-auto rounded-lg border">
                                            <table class="w-full bg-white text-sm text-left dark:bg-gray-900">
                                                <thead class="bg-gray-100 dark:bg-white/5">
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
                                        <div class="overflow-x-auto rounded-lg border">
                                            <table class="w-full bg-white text-sm text-left dark:bg-gray-900">
                                                <thead class="bg-gray-100 dark:bg-white/5">
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
                            <td colspan="9" class="px-3 py-4 text-center italic text-gray-500">
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
