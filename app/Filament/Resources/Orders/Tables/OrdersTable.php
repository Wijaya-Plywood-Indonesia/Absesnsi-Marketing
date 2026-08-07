<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Filament\Resources\Orders\OrderResource;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('order_no')
                    ->label('Order Number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customer.name')
                    ->label('Customer')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('user.name')
                    ->label('Marketer')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('order_date')
                    ->label('Tanggal Order')
                    ->date()
                    ->sortable(),
                TextColumn::make('order_items_count')
                    ->label('Items Count')
                    ->counts('orderItems')
                    ->state(fn ($record) => $record->order_items_count.' items'),
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
                fn ($record) => OrderResource::getUrl('view', ['record' => $record]),
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
