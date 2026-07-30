<?php

namespace App\Filament\Resources\Visits\Schemas;

use App\Models\Customer;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class VisitForm
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
                DatePicker::make('tanggal')
                    ->required()
                    ->default(now()),
                TextInput::make('jam')
                    ->required()
                    ->default(now()->format('H:i')),
                Select::make('hasil')
                    ->label('Hasil Kunjungan')
                    ->options([
                        'Order' => 'Order',
                        'Follow-up' => 'Follow-up',
                        'Komplain' => 'Komplain',
                        'Toko Tutup' => 'Toko Tutup',
                        'Tidak Ada Respon' => 'Tidak Ada Respon',
                    ])
                    ->required(),
                Textarea::make('catatan')
                    ->columnSpanFull(),
                TextInput::make('foto'),
                TextInput::make('latitude')
                    ->label('Latitude')
                    ->id('visit_latitude')
                    ->suffixAction(
                        Action::make('getLocationLat')
                            ->icon('heroicon-m-map-pin')
                            ->alpineClickHandler('
                                navigator.geolocation.getCurrentPosition(
                                    (position) => {
                                        $wire.set(\'data.latitude\', position.coords.latitude.toFixed(6));
                                        $wire.set(\'data.longitude\', position.coords.longitude.toFixed(6));
                                        $wire.set(\'data.accuracy\', position.coords.accuracy.toFixed(0) + \' m\');
                                    },
                                    (error) => alert(\'Gagal mendapatkan lokasi: \' + error.message)
                                )
                            ')
                    ),
                TextInput::make('longitude')
                    ->label('Longitude')
                    ->id('visit_longitude')
                    ->suffixAction(
                        Action::make('getLocationLng')
                            ->icon('heroicon-m-map-pin')
                            ->alpineClickHandler('
                                navigator.geolocation.getCurrentPosition(
                                    (position) => {
                                        $wire.set(\'data.latitude\', position.coords.latitude.toFixed(6));
                                        $wire.set(\'data.longitude\', position.coords.longitude.toFixed(6));
                                        $wire.set(\'data.accuracy\', position.coords.accuracy.toFixed(0) + \' m\');
                                    },
                                    (error) => alert(\'Gagal mendapatkan lokasi: \' + error.message)
                                )
                            ')
                    ),
                TextInput::make('accuracy')
                    ->label('GPS Accuracy')
                    ->readonly(),
            ]);
    }
}
