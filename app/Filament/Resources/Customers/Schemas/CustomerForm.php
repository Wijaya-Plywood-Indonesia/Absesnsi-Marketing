<?php

namespace App\Filament\Resources\Customers\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use App\Models\User;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->label('Assigned Marketer')
                    ->options(User::where('role', 'marketing')->pluck('name', 'id'))
                    ->searchable()
                    ->placeholder('Select marketer to assign'),
                TextInput::make('name')
                    ->label('Customer Name')
                    ->required(),
                TextInput::make('phone')
                    ->label('Phone Number')
                    ->tel(),
                Textarea::make('address')
                    ->label('Address')
                    ->columnSpanFull(),
                TextInput::make('latitude')
                    ->label('Latitude')
                    ->id('latitude')
                    ->suffixAction(
                        \Filament\Forms\Components\Actions\Action::make('getLocationLat')
                            ->icon('heroicon-m-map-pin')
                            ->alpineClickHandler('
                                navigator.geolocation.getCurrentPosition(
                                    (position) => {
                                        $wire.set(\'data.latitude\', position.coords.latitude.toFixed(6));
                                        $wire.set(\'data.longitude\', position.coords.longitude.toFixed(6));
                                    },
                                    (error) => alert(\'Gagal mendapatkan lokasi: \' + error.message)
                                )
                            ')
                    ),
                TextInput::make('longitude')
                    ->label('Longitude')
                    ->id('longitude')
                    ->suffixAction(
                        \Filament\Forms\Components\Actions\Action::make('getLocationLng')
                            ->icon('heroicon-m-map-pin')
                            ->alpineClickHandler('
                                navigator.geolocation.getCurrentPosition(
                                    (position) => {
                                        $wire.set(\'data.latitude\', position.coords.latitude.toFixed(6));
                                        $wire.set(\'data.longitude\', position.coords.longitude.toFixed(6));
                                    },
                                    (error) => alert(\'Gagal mendapatkan lokasi: \' + error.message)
                                )
                            ')
                    ),
                Select::make('pola')
                    ->label('Pola Beli')
                    ->options([
                        'Eceran' => 'Eceran',
                        'Partai' => 'Partai',
                    ])
                    ->required()
                    ->default('Eceran'),
                Select::make('jenis')
                    ->label('Jenis Usaha')
                    ->options([
                        'Mebel' => 'Mebel',
                        'Reseller' => 'Reseller',
                        'Toko Bangunan' => 'Toko Bangunan',
                        'Pabrik Lain' => 'Pabrik Lain',
                        'Lainnya' => 'Lainnya',
                    ])
                    ->required(),
                Select::make('status')
                    ->label('Status Customer')
                    ->options([
                        'Baru' => 'Baru',
                        'Lama' => 'Lama',
                    ])
                    ->required()
                    ->default('Baru'),
            ]);
    }
}
