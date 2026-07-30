<?php

namespace App\Filament\Resources\Orders\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('customer_id')
                    ->label('Customer')
                    ->options(Customer::pluck('name', 'id'))
                    ->searchable()
                    ->required(),
                Select::make('user_id')
                    ->label('Marketer')
                    ->options(User::where('role', 'marketing')->pluck('name', 'id'))
                    ->searchable()
                    ->required(),
                TextInput::make('order_no')
                    ->label('Order Number')
                    ->required()
                    ->default(fn () => 'ORD-' . str_pad(\App\Models\Order::count() + 1, 4, '0', STR_PAD_LEFT))
                    ->readonly(),
                DatePicker::make('order_date')
                    ->label('Tanggal Order')
                    ->default(now())
                    ->required(),
                Repeater::make('orderItems')
                    ->relationship()
                    ->label('Produk Dipesan')
                    ->schema([
                        Select::make('product_id')
                            ->label('Produk')
                            ->options(Product::pluck('name', 'id'))
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $set('unit', Product::find($state)?->unit);
                            }),
                        TextInput::make('qty')
                            ->label('Jumlah')
                            ->numeric()
                            ->minValue(1)
                            ->required(),
                        TextInput::make('unit')
                            ->label('Satuan')
                            ->readonly()
                            ->dehydrated(),
                    ])
                    ->columns(3)
                    ->required()
                    ->minItems(1)
                    ->columnSpanFull(),
                Textarea::make('catatan')
                    ->columnSpanFull(),
            ]);
    }
}
