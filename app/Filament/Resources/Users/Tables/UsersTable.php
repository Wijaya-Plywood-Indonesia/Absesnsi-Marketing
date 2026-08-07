<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort(function ($query) {
                return $query->orderByRaw('
                    CASE
                        WHEN validated_at IS NULL THEN 0
                        WHEN validated_at > NOW() THEN 2
                        ELSE 1
                    END
                ')->orderByDesc('created_at');
            })
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
                TextColumn::make('role')
                    ->badge()
                    ->color(fn (string $state) => $state === 'admin' ? 'warning' : 'info')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->state(function (User $record): string {
                        if ($record->isPending()) {
                            return 'Belum Diverifikasi';
                        }

                        if ($record->isBanned()) {
                            return 'Dibanned s/d '.$record->validated_at->translatedFormat('d M Y, H:i');
                        }

                        return 'Aktif sejak '.$record->validated_at?->translatedFormat('d M Y, H:i');
                    })
                    ->badge()
                    ->color(function (User $record) {
                        if ($record->isPending()) {
                            return 'gray';
                        }

                        if ($record->isBanned()) {
                            return 'danger';
                        }

                        return 'success';
                    }),
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
            ->recordActions([
                // Untuk user PENDING saja
                Action::make('verify')
                    ->label('Verifikasi')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalDescription('Verifikasi akun ini sehingga bisa login sekarang juga?')
                    ->visible(fn (User $record) => $record->isPending())
                    ->action(fn (User $record) => $record->verify()),

                // Untuk user AKTIF saja
                Action::make('ban')
                    ->label('Ban')
                    ->icon(Heroicon::OutlinedNoSymbol)
                    ->color('danger')
                    ->visible(fn (User $record) => ! $record->isPending() && ! $record->isBanned())
                    ->schema([
                        Select::make('duration')
                            ->label('Durasi Ban')
                            ->options([
                                '1' => '1 Hari',
                                '3' => '3 Hari',
                                '7' => '7 Hari',
                                '30' => '30 Hari',
                                'custom' => 'Custom (isi jumlah hari)',
                                'forever' => 'Selamanya',
                            ])
                            ->required()
                            ->live()
                            ->default('7'),
                        TextInput::make('custom_days')
                            ->label('Jumlah Hari')
                            ->numeric()
                            ->minValue(1)
                            ->required()
                            ->visible(fn (callable $get) => $get('duration') === 'custom'),
                    ])
                    ->action(function (User $record, array $data): void {
                        $until = match ($data['duration']) {
                            'forever' => now()->addYears(100),
                            'custom' => now()->addDays((int) $data['custom_days']),
                            default => now()->addDays((int) $data['duration']),
                        };

                        $record->ban($until);
                    }),

                // Untuk user yang SEDANG DIBANNED saja
                Action::make('unban')
                    ->label('Cabut Ban')
                    ->icon(Heroicon::OutlinedLockOpen)
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalDescription('Cabut ban akun ini sehingga bisa login kembali sekarang juga?')
                    ->visible(fn (User $record) => $record->isBanned())
                    ->action(fn (User $record) => $record->verify()),

                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
