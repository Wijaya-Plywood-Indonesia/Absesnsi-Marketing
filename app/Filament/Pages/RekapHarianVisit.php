<?php

namespace App\Filament\Pages;

use App\Models\Customer;
use App\Models\User;
use App\Models\Visit;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class RekapHarianVisit extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static ?string $navigationLabel = 'Rekap Harian Visit';

    protected static ?string $title = 'Rekap Harian Visit';

    protected string $view = 'filament.pages.rekap-harian-visit';

    // Satu-satunya sumber kebenaran untuk tanggal. Karena tabel-tabel di blade
    // langsung membaca method di komponen ini (bukan widget terpisah),
    // begitu properti ini berubah lewat wire:model.live, semuanya otomatis
    // ikut re-render tanpa perlu event dispatch/listener sama sekali.
    public ?string $tanggal = null;

    // Sales yang sedang dipilih user untuk dilihat detailnya.
    // Kalau null -> hanya tampilkan ringkasan semua sales.
    public ?string $selectedUserId = null;

    public function mount(): void
    {
        $this->tanggal = now()->toDateString();
        $this->form->fill([
            'tanggal' => $this->tanggal,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('tanggal')
                    ->label('Pilih Tanggal Laporan')
                    ->native(false)
                    ->live()
                    ->afterStateUpdated(function ($state) {
                        $this->tanggal = $state;
                        // Tanggal berubah -> daftar sales aktif bisa berbeda, reset pilihan.
                        $this->selectedUserId = null;
                    }),
            ]);
    }

    public function selectSales(?string $userId): void
    {
        $this->selectedUserId = $userId;
    }

    public function getSelectedUser(): ?User
    {
        if (! $this->selectedUserId) {
            return null;
        }

        return User::find($this->selectedUserId);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportExcel')
                ->label('Export ke Excel')
                ->color('warning')
                ->icon('heroicon-o-document-arrow-down')
                ->action('exportExcel'),
        ];
    }

    protected function tanggal(): string
    {
        return $this->tanggal ?? now()->toDateString();
    }

    protected function isMarketing(): bool
    {
        return auth()->user()->role === 'marketing';
    }

    public function getVisits(): Collection
    {
        $query = Visit::with(['customer', 'user'])
            ->where('tanggal', $this->tanggal());

        if ($this->isMarketing()) {
            $query->where('user_id', auth()->id());
        } elseif ($this->selectedUserId) {
            $query->where('user_id', $this->selectedUserId);
        }

        return $query->get()->sortBy(function ($visit) {
            return ($visit->user?->name ?? '').'_'.($visit->jam ?? '');
        })->values();
    }

    public function getAddedCustomers(): Collection
    {
        $query = Customer::with('user')
            ->whereDate('created_at', $this->tanggal());

        if ($this->isMarketing()) {
            $query->where('user_id', auth()->id());
        } elseif ($this->selectedUserId) {
            $query->where('user_id', $this->selectedUserId);
        }

        return $query->get()->sortBy(function ($customer) {
            return ($customer->user?->name ?? '').'_'.($customer->created_at->toTimeString());
        })->values();
    }

    public function getSummary(): array
    {
        // Sengaja query ulang tanpa filter selectedUserId, karena ringkasan
        // harus selalu menampilkan SEMUA sales (untuk dipilih), bukan cuma satu.
        $visitsQuery = Visit::where('tanggal', $this->tanggal());
        $addedCustomersQuery = Customer::whereDate('created_at', $this->tanggal());

        if ($this->isMarketing()) {
            $visitsQuery->where('user_id', auth()->id());
            $addedCustomersQuery->where('user_id', auth()->id());
        }

        $visits = $visitsQuery->get();
        $addedCustomers = $addedCustomersQuery->get();

        $summary = [];
        $userIds = $visits->pluck('user_id')->merge($addedCustomers->pluck('user_id'))->unique();
        $activeUsers = User::whereIn('id', $userIds)->get();

        foreach ($activeUsers as $u) {
            $userVisits = $visits->where('user_id', $u->id);
            $summary[] = [
                'id' => $u->id,
                'name' => $u->name,
                'visits_count' => $userVisits->count(),
                'order_count' => $userVisits->where('hasil', 'Order')->count(),
                'followup_count' => $userVisits->where('hasil', 'Follow-up')->count(),
                'komplain_count' => $userVisits->where('hasil', 'Komplain')->count(),
                'tutup_count' => $userVisits->where('hasil', 'Toko Tutup')->count(),
                'no_response_count' => $userVisits->where('hasil', 'Tidak Ada Respon')->count(),
                'added_customers_count' => $addedCustomers->where('user_id', $u->id)->count(),
            ];
        }

        usort($summary, fn ($a, $b) => strcmp($a['name'], $b['name']));

        return $summary;
    }

    public function exportExcel()
    {
        $tanggal = $this->tanggal();

        // Export selalu berisi SEMUA sales, terlepas dari sales mana yang
        // sedang dipilih/ditampilkan di layar.
        $isMarketing = $this->isMarketing();

        $visitsQuery = Visit::with(['customer', 'user'])->where('tanggal', $tanggal);
        $addedCustomersQuery = Customer::with('user')->whereDate('created_at', $tanggal);
        if ($isMarketing) {
            $visitsQuery->where('user_id', auth()->id());
            $addedCustomersQuery->where('user_id', auth()->id());
        }

        $visits = $visitsQuery->get()->sortBy(fn ($visit) => ($visit->user?->name ?? '').'_'.($visit->jam ?? ''))->values();
        $addedCustomers = $addedCustomersQuery->get()->sortBy(fn ($customer) => ($customer->user?->name ?? '').'_'.($customer->created_at->toTimeString()))->values();
        $summary = $this->getSummary();

        if ($visits->isEmpty() && $addedCustomers->isEmpty()) {
            Notification::make()
                ->title('Tidak ada data untuk diexport')
                ->warning()
                ->send();

            return null;
        }

        return response()->streamDownload(function () use ($visits, $addedCustomers, $summary, $tanggal) {
            $spreadsheet = new Spreadsheet;
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Rekap Harian');

            $sheet->getParent()->getDefaultStyle()->getFont()->setName('Calibri');
            $sheet->getParent()->getDefaultStyle()->getFont()->setSize(11);

            $titleStyle = [
                'font' => [
                    'bold' => true,
                    'size' => 16,
                    'color' => ['rgb' => '1F4E78'],
                ],
            ];

            $metaHeaderStyle = [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => '333333'],
                ],
            ];

            $sectionHeaderStyle = [
                'font' => [
                    'bold' => true,
                    'size' => 12,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1F4E78'],
                ],
            ];

            $tableHeaderStyle = [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '2F5597'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ];

            $borderThin = [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'D9D9D9'],
                    ],
                ],
            ];

            $centerAlign = [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ];

            $sheet->setCellValue('A1', 'REKAP HARIAN AKTIVITAS SALES & MARKETING');
            $sheet->getStyle('A1')->applyFromArray($titleStyle);
            $sheet->mergeCells('A1:I1');

            $sheet->setCellValue('A3', 'Tanggal:');
            $sheet->getStyle('A3')->applyFromArray($metaHeaderStyle);
            $sheet->setCellValue('B3', Carbon::parse($tanggal)->translatedFormat('d F Y'));

            $row = 5;

            $sheet->setCellValue("A{$row}", '1. RINGKASAN AKTIVITAS SALES');
            $sheet->getStyle("A{$row}:I{$row}")->applyFromArray($sectionHeaderStyle);
            $sheet->mergeCells("A{$row}:I{$row}");
            $row++;

            $headersSummary = ['No', 'Nama Sales', 'Total Kunjungan', 'Order', 'Follow-up', 'Komplain', 'Toko Tutup', 'Tidak Ada Respon', 'Total Customer Baru'];
            foreach ($headersSummary as $colIdx => $headerTitle) {
                $colLetter = Coordinate::stringFromColumnIndex($colIdx + 1);
                $sheet->setCellValue("{$colLetter}{$row}", $headerTitle);
            }
            $sheet->getStyle("A{$row}:I{$row}")->applyFromArray($tableHeaderStyle);
            $sheet->getRowDimension($row)->setRowHeight(25);
            $startDataRowSum = $row + 1;
            $row++;

            $noSum = 1;
            foreach ($summary as $item) {
                $sheet->setCellValue("A{$row}", $noSum++);
                $sheet->setCellValue("B{$row}", $item['name']);
                $sheet->setCellValue("C{$row}", $item['visits_count']);
                $sheet->setCellValue("D{$row}", $item['order_count']);
                $sheet->setCellValue("E{$row}", $item['followup_count']);
                $sheet->setCellValue("F{$row}", $item['komplain_count']);
                $sheet->setCellValue("G{$row}", $item['tutup_count']);
                $sheet->setCellValue("H{$row}", $item['no_response_count']);
                $sheet->setCellValue("I{$row}", $item['added_customers_count']);

                $sheet->getRowDimension($row)->setRowHeight(20);
                $row++;
            }
            $endDataRowSum = $row - 1;
            if ($endDataRowSum >= $startDataRowSum) {
                $sheet->getStyle("A{$startDataRowSum}:I{$endDataRowSum}")->applyFromArray($borderThin);
                $sheet->getStyle("A{$startDataRowSum}:A{$endDataRowSum}")->applyFromArray($centerAlign);
                $sheet->getStyle("C{$startDataRowSum}:I{$endDataRowSum}")->applyFromArray($centerAlign);

                $sheet->setCellValue("A{$row}", 'TOTAL');
                $sheet->mergeCells("A{$row}:B{$row}");
                $sheet->setCellValue("C{$row}", "=SUM(C{$startDataRowSum}:C{$endDataRowSum})");
                $sheet->setCellValue("D{$row}", "=SUM(D{$startDataRowSum}:D{$endDataRowSum})");
                $sheet->setCellValue("E{$row}", "=SUM(E{$startDataRowSum}:E{$endDataRowSum})");
                $sheet->setCellValue("F{$row}", "=SUM(F{$startDataRowSum}:F{$endDataRowSum})");
                $sheet->setCellValue("G{$row}", "=SUM(G{$startDataRowSum}:G{$endDataRowSum})");
                $sheet->setCellValue("H{$row}", "=SUM(H{$startDataRowSum}:H{$endDataRowSum})");
                $sheet->setCellValue("I{$row}", "=SUM(I{$startDataRowSum}:I{$endDataRowSum})");
                $sheet->getStyle("A{$row}:I{$row}")->getFont()->setBold(true);
                $sheet->getStyle("A{$row}:I{$row}")->applyFromArray($borderThin);
                $sheet->getStyle("C{$row}:I{$row}")->applyFromArray($centerAlign);
                $row++;
            } else {
                $sheet->setCellValue("A{$row}", 'Tidak ada aktivitas sales pada tanggal ini.');
                $sheet->mergeCells("A{$row}:I{$row}");
                $sheet->getStyle("A{$row}")->getFont()->setItalic(true);
                $sheet->getStyle("A{$row}:I{$row}")->applyFromArray($borderThin);
                $row++;
            }

            $row += 2;

            $sheet->setCellValue("A{$row}", '2. DAFTAR KUNJUNGAN CUSTOMER');
            $sheet->getStyle("A{$row}:H{$row}")->applyFromArray($sectionHeaderStyle);
            $sheet->mergeCells("A{$row}:H{$row}");
            $row++;

            $headers1 = ['No', 'Nama Sales', 'Nama Customer', 'Jam Kunjungan', 'Kecamatan', 'Hasil Kunjungan', 'Catatan', 'Alamat Lengkap Toko'];
            foreach ($headers1 as $colIdx => $headerTitle) {
                $colLetter = Coordinate::stringFromColumnIndex($colIdx + 1);
                $sheet->setCellValue("{$colLetter}{$row}", $headerTitle);
            }
            $sheet->getStyle("A{$row}:H{$row}")->applyFromArray($tableHeaderStyle);
            $sheet->getRowDimension($row)->setRowHeight(25);
            $startDataRow1 = $row + 1;
            $row++;

            $no = 1;
            foreach ($visits as $visit) {
                $sheet->setCellValue("A{$row}", $no++);
                $sheet->setCellValue("B{$row}", $visit->user?->name ?? '-');
                $sheet->setCellValue("C{$row}", $visit->customer?->name ?? '-');
                $sheet->setCellValue("D{$row}", $visit->jam);
                $sheet->setCellValue("E{$row}", $visit->customer?->kecamatan ?? '-');
                $sheet->setCellValue("F{$row}", $visit->hasil);
                $sheet->setCellValue("G{$row}", $visit->catatan);
                $sheet->setCellValue("H{$row}", $visit->customer?->address ?? '-');

                $sheet->getRowDimension($row)->setRowHeight(20);
                $row++;
            }
            $endDataRow1 = $row - 1;
            if ($endDataRow1 >= $startDataRow1) {
                $sheet->getStyle("A{$startDataRow1}:H{$endDataRow1}")->applyFromArray($borderThin);
                $sheet->getStyle("A{$startDataRow1}:A{$endDataRow1}")->applyFromArray($centerAlign);
                $sheet->getStyle("D{$startDataRow1}:D{$endDataRow1}")->applyFromArray($centerAlign);
                $sheet->getStyle("F{$startDataRow1}:F{$endDataRow1}")->applyFromArray($centerAlign);
            } else {
                $sheet->setCellValue("A{$row}", 'Tidak ada data kunjungan pada tanggal ini.');
                $sheet->mergeCells("A{$row}:H{$row}");
                $sheet->getStyle("A{$row}")->getFont()->setItalic(true);
                $sheet->getStyle("A{$row}:H{$row}")->applyFromArray($borderThin);
                $row++;
            }

            $row += 2;

            $sheet->setCellValue("A{$row}", '3. PENAMBAHAN CUSTOMER BARU');
            $sheet->getStyle("A{$row}:H{$row}")->applyFromArray($sectionHeaderStyle);
            $sheet->mergeCells("A{$row}:H{$row}");
            $row++;

            $headers2 = ['No', 'Nama Sales', 'Nama Customer', 'Telepon', 'Tanggal Dibuat', 'Alamat', 'Kecamatan', 'Kabupaten/Kota'];
            foreach ($headers2 as $colIdx => $headerTitle) {
                $colLetter = Coordinate::stringFromColumnIndex($colIdx + 1);
                $sheet->setCellValue("{$colLetter}{$row}", $headerTitle);
            }
            $sheet->getStyle("A{$row}:H{$row}")->applyFromArray($tableHeaderStyle);
            $sheet->getRowDimension($row)->setRowHeight(25);
            $startDataRow2 = $row + 1;
            $row++;

            $noCustomer = 1;
            foreach ($addedCustomers as $customer) {
                $sheet->setCellValue("A{$row}", $noCustomer++);
                $sheet->setCellValue("B{$row}", $customer->user?->name ?? '-');
                $sheet->setCellValue("C{$row}", $customer->name);
                $sheet->setCellValue("D{$row}", $customer->phone ?? '-');
                $sheet->setCellValue("E{$row}", $customer->created_at->format('H:i:s'));
                $sheet->setCellValue("F{$row}", $customer->address ?? '-');
                $sheet->setCellValue("G{$row}", $customer->kecamatan ?? '-');
                $sheet->setCellValue("H{$row}", $customer->kota ?? '-');

                $sheet->getRowDimension($row)->setRowHeight(20);
                $row++;
            }
            $endDataRow2 = $row - 1;
            if ($endDataRow2 >= $startDataRow2) {
                $sheet->getStyle("A{$startDataRow2}:H{$endDataRow2}")->applyFromArray($borderThin);
                $sheet->getStyle("A{$startDataRow2}:A{$endDataRow2}")->applyFromArray($centerAlign);
                $sheet->getStyle("E{$startDataRow2}:E{$endDataRow2}")->applyFromArray($centerAlign);
            } else {
                $sheet->setCellValue("A{$row}", 'Tidak ada penambahan customer baru pada tanggal ini.');
                $sheet->mergeCells("A{$row}:H{$row}");
                $sheet->getStyle("A{$row}")->getFont()->setItalic(true);
                $sheet->getStyle("A{$row}:H{$row}")->applyFromArray($borderThin);
                $row++;
            }

            foreach (range('A', 'I') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, "rekap_harian_{$tanggal}.xlsx", [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
