<?php

namespace App\Filament\Resources\Visits\Tables;

use App\Filament\Resources\Visits\VisitResource;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VisitsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('customer.name')
                    ->label('Customer')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('user.name')
                    ->label('Marketer')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('tanggal')
                    ->date()
                    ->sortable(),
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
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordUrl(
                fn ($record) => VisitResource::getUrl('view', ['record' => $record]),
            )
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
