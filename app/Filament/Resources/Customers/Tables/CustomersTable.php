<?php

namespace App\Filament\Resources\Customers\Tables;

use App\Filament\Resources\Customers\CustomerResource;
use App\Models\Customer;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([

                TextColumn::make('name')
                    ->label('Nama Customer')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('user.name')
                    ->label('Marketer')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('phone')
                    ->label('No. Telepon')
                    ->searchable(),

                TextColumn::make('address')
                    ->label('Alamat')
                    ->limit(30)
                    ->toggleable(),

                TextColumn::make('kota')
                    ->label('Kota')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('kecamatan')
                    ->label('Kecamatan')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('pola')
                    ->label('Pola Beli')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Eceran' => 'info',
                        'Partai' => 'success',
                        default => 'gray',
                    }),

                TextColumn::make('jenis')
                    ->label('Jenis Usaha')
                    ->badge()
                    ->toggleable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Baru' => 'warning',
                        'Lama' => 'success',
                        default => 'gray',
                    }),

                TextColumn::make('latitude')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('longitude')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('kota')
                    ->label('Kota')
                    ->options(fn () => Customer::query()
                        ->whereNotNull('kota')
                        ->distinct()
                        ->pluck('kota', 'kota')
                        ->toArray()),

                SelectFilter::make('pola')
                    ->label('Pola Beli')
                    ->options([
                        'Eceran' => 'Eceran',
                        'Partai' => 'Partai',
                    ]),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'Baru' => 'Baru',
                        'Lama' => 'Lama',
                    ]),

                SelectFilter::make('user_id')
                    ->label('Marketer')
                    ->relationship('user', 'name')
                    ->options(fn () => User::where('role', 'marketing')->pluck('name', 'id')),
            ])
            ->defaultSort('name')
            ->recordUrl(
                fn ($record) => CustomerResource::getUrl('view', ['record' => $record]),
            )
            ->recordActions([
                Action::make('viewFoto')
                    ->label('Lihat Foto')
                    ->icon('heroicon-o-photo')
                    ->color('gray')
                    ->visible(fn ($record) => filled($record->foto))
                    ->modalHeading('Foto Toko')
                    ->modalContent(fn ($record) => new HtmlString(
                        '<div style="display: flex; justify-content: center; align-items: center;">
            <img src="'.Storage::disk('public')->url($record->foto).'" style="max-height: 70vh; max-width: 100%; object-fit: contain; border-radius: 0.5rem;" />
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
