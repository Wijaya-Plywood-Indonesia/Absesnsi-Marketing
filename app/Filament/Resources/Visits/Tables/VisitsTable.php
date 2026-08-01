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
