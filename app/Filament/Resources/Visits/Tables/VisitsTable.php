<?php

namespace App\Filament\Resources\Visits\Tables;

use App\Filament\Resources\Visits\VisitResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

class VisitsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable(),
                TextColumn::make('user.name')
                    ->label('Marketer')
                    ->searchable(),
                TextColumn::make('tanggal')
                    ->date(),
                TextColumn::make('jam')
                    ->searchable(),
                TextColumn::make('hasil')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Order' => 'success',
                        'Follow-up' => 'warning',
                        'Komplain' => 'danger',
                        'Toko Tutup' => 'gray',
                        'Tidak Ada Respon' => 'info',
                        default => 'gray',
                    })
                    ->searchable(),
                TextColumn::make('catatan')
                    ->limit(30)
                    ->toggleable(),
                TextColumn::make('latitude')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('longitude')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('accuracy')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('is_outside_area')
                    ->label('Area Kunjungan')
                    ->badge()
                    ->state(fn ($record) => $record->is_outside_area ? 'Di Luar Area' : 'Di Dalam Area')
                    ->color(fn ($record) => $record->is_outside_area ? 'danger' : 'success')
                    ->icon(fn ($record) => $record->is_outside_area ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->sortable(query: fn ($query, string $direction) => $query->orderBy('is_outside_area', $direction)),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->defaultSort(
                fn ($query) => $query
                    ->orderBy('tanggal', 'desc')
                    ->orderBy(
                        User::select('name')
                            ->whereColumn('users.id', 'visits.user_id')
                    )
            )
            ->groups([
                Group::make('tanggal')
                    ->label('Tanggal')
                    ->date()
                    ->orderQueryUsing(fn ($query) => $query->orderBy('tanggal', 'desc')),
            ])
            ->defaultGroup('tanggal')
            ->groupingSettingsHidden()
            ->recordUrl(
                fn ($record) => VisitResource::getUrl('view', ['record' => $record]),
            )
            ->headerActions([
                 Action::make('rekapHarian')
                    ->label('Rekap Harian')
                    ->icon('heroicon-o-document-text')
                    ->color('success')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('tanggal')
                            ->label('Tanggal')
                            ->required()
                            ->default(now()->toDateString()),
                    ])
                    ->action(function (array $data) {
                        $tanggal = $data['tanggal'];
                        $isMarketing = auth()->user()->role === 'marketing';

                        // 1. Fetch Visits
                        $visitsQuery = \App\Models\Visit::with(['customer', 'user'])
                            ->where('tanggal', $tanggal);
                        if ($isMarketing) {
                            $visitsQuery->where('user_id', auth()->id());
                        }
                        $visits = $visitsQuery->get()->sortBy(function($visit) {
                            return ($visit->user?->name ?? '') . '_' . ($visit->jam ?? '');
                        });

                        // 2. Fetch Added Customers
                        $addedCustomersQuery = \App\Models\Customer::with('user')
                            ->whereDate('created_at', $tanggal);
                        if ($isMarketing) {
                            $addedCustomersQuery->where('user_id', auth()->id());
                        }
                        $addedCustomers = $addedCustomersQuery->get()->sortBy(function($customer) {
                            return ($customer->user?->name ?? '') . '_' . ($customer->created_at->toTimeString());
                        });

                        // 3. Compute Summary
                        $summary = [];
                        $userIds = $visits->pluck('user_id')->merge($addedCustomers->pluck('user_id'))->unique();
                        $activeUsers = \App\Models\User::whereIn('id', $userIds)->get();

                        foreach ($activeUsers as $u) {
                            $userVisits = $visits->where('user_id', $u->id);
                            $summary[] = [
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
                        // Sort summary by name
                        usort($summary, function($a, $b) {
                            return strcmp($a['name'], $b['name']);
                        });

                        return response()->streamDownload(function () use ($visits, $addedCustomers, $summary, $tanggal) {
                            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
                            $sheet = $spreadsheet->getActiveSheet();
                            $sheet->setTitle('Rekap Harian');

                            // Set default font
                            $sheet->getParent()->getDefaultStyle()->getFont()->setName('Calibri');
                            $sheet->getParent()->getDefaultStyle()->getFont()->setSize(11);

                            // Styles
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
                                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                    'startColor' => ['rgb' => '1F4E78'],
                                ],
                            ];

                            $tableHeaderStyle = [
                                'font' => [
                                    'bold' => true,
                                    'color' => ['rgb' => 'FFFFFF'],
                                ],
                                'fill' => [
                                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                    'startColor' => ['rgb' => '2F5597'],
                                ],
                                'alignment' => [
                                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                                ],
                            ];

                            $borderThin = [
                                'borders' => [
                                    'allBorders' => [
                                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                                        'color' => ['rgb' => 'D9D9D9'],
                                    ],
                                ],
                            ];

                            $centerAlign = [
                                'alignment' => [
                                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                                ],
                            ];

                            // 1. Title Block
                            $sheet->setCellValue('A1', 'REKAP HARIAN AKTIVITAS SALES & MARKETING');
                            $sheet->getStyle('A1')->applyFromArray($titleStyle);
                            $sheet->mergeCells('A1:I1');

                            // 2. Metadata Block
                            $sheet->setCellValue('A3', 'Tanggal:');
                            $sheet->getStyle('A3')->applyFromArray($metaHeaderStyle);
                            $sheet->setCellValue('B3', \Carbon\Carbon::parse($tanggal)->translatedFormat('d F Y'));

                            // Row tracker
                            $row = 5;

                            // 3. Section 1: Ringkasan Aktivitas Sales (Summary)
                            $sheet->setCellValue("A{$row}", '1. RINGKASAN AKTIVITAS SALES');
                            $sheet->getStyle("A{$row}:I{$row}")->applyFromArray($sectionHeaderStyle);
                            $sheet->mergeCells("A{$row}:I{$row}");
                            $row++;

                            $headersSummary = ['No', 'Nama Sales', 'Total Kunjungan', 'Order', 'Follow-up', 'Komplain', 'Toko Tutup', 'Tidak Ada Respon', 'Total Customer Baru'];
                            foreach ($headersSummary as $colIdx => $headerTitle) {
                                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1);
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
                                
                                // Make total row at the end of summary
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

                            $row += 2; // Spacing

                            // 4. Section 2: Kunjungan
                            $sheet->setCellValue("A{$row}", '2. DAFTAR KUNJUNGAN CUSTOMER');
                            $sheet->getStyle("A{$row}:H{$row}")->applyFromArray($sectionHeaderStyle);
                            $sheet->mergeCells("A{$row}:H{$row}");
                            $row++;

                            $headers1 = ['No', 'Nama Sales', 'Nama Customer', 'Jam Kunjungan', 'Kecamatan', 'Hasil Kunjungan', 'Catatan', 'Alamat Lengkap Toko'];
                            foreach ($headers1 as $colIdx => $headerTitle) {
                                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1);
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

                            $row += 2; // Spacing

                            // 5. Section 3: Penambahan Customer Baru
                            $sheet->setCellValue("A{$row}", '3. PENAMBAHAN CUSTOMER BARU');
                            $sheet->getStyle("A{$row}:H{$row}")->applyFromArray($sectionHeaderStyle);
                            $sheet->mergeCells("A{$row}:H{$row}");
                            $row++;

                            $headers2 = ['No', 'Nama Sales', 'Nama Customer', 'Telepon', 'Tanggal Dibuat', 'Alamat', 'Kecamatan', 'Kabupaten/Kota'];
                            foreach ($headers2 as $colIdx => $headerTitle) {
                                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1);
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

                            // Auto-size columns
                            foreach (range('A', 'I') as $col) {
                                $sheet->getColumnDimension($col)->setAutoSize(true);
                            }

                            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
                            $writer->save('php://output');
                        }, "rekap_harian_{$tanggal}.xlsx", [
                            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        ]);
                    }),
            ])
            ->recordActions([
                Action::make('viewFoto')
                    ->label('Lihat Foto')
                    ->icon('heroicon-o-photo')
                    ->color('gray')
                    ->visible(fn ($record) => filled($record->foto))
                    ->modalHeading('Foto Kunjungan')
                    ->modalContent(fn ($record) => new HtmlString(
                        '<div style="display: flex; justify-content: center; align-items: center;">
            <img src="'.Storage::url($record->foto).'" style="max-height: 70vh; max-width: 100%; object-fit: contain; border-radius: 0.5rem;" />
        </div>'
                    ))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup'),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
