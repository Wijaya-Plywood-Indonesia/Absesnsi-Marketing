<?php

namespace App\Filament\Resources\Orders\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Order')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('order_no')
                                    ->label('Order Number'),
                                TextEntry::make('order_date')
                                    ->label('Tanggal Order')
                                    ->date('d M Y'),
                                TextEntry::make('customer.name')
                                    ->label('Customer'),
                                TextEntry::make('user.name')
                                    ->label('Marketer'),
                            ]),
                        TextEntry::make('catatan')
                            ->label('Catatan')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),

                Section::make('Produk Dipesan')
                    ->schema([
                        RepeatableEntry::make('orderItems')
                            ->label('')
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        TextEntry::make('product.name')
                                            ->label('Produk'),
                                        TextEntry::make('qty')
                                            ->label('Jumlah'),
                                        TextEntry::make('unit')
                                            ->label('Satuan'),
                                    ]),
                            ]),
                    ]),
            ]);
    }
}
