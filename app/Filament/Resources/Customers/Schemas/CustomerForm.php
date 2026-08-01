<?php

namespace App\Filament\Resources\Customers\Schemas;

use App\Models\User;
use App\Models\Wilayah;
use Dotswan\MapPicker\Fields\Map;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->label('Marketer yang Ditugaskan')
                    ->options(User::where('role', 'marketing')->pluck('name', 'id'))
                    ->searchable()
                    ->placeholder('Pilih marketer untuk ditugaskan'),

                TextInput::make('name')
                    ->label('Nama Customer')
                    ->required(),

                TextInput::make('phone')
                    ->label('Nomor Telepon')
                    ->tel()
                    ->required(),

                Textarea::make('address')
                    ->label('Alamat Lengkap')
                    ->helperText('Jalan, patokan, detail lokasi')
                    ->columnSpanFull()
                    ->required(),

                Grid::make(2)
                    ->schema([
                        Select::make('kota')
                            ->label('Kota/Kabupaten')
                            ->options(Wilayah::daftarKota())
                            ->searchable()
                            ->live()
                            ->required()
                            ->afterStateUpdated(fn (callable $set) => $set('kecamatan', null)),

                        Select::make('kecamatan')
                            ->label('Kecamatan')
                            ->options(fn (callable $get) => Wilayah::daftarKecamatan($get('kota')))
                            ->searchable()
                            ->required()
                            ->disabled(fn (callable $get) => blank($get('kota')))
                            ->helperText(fn (callable $get) => blank($get('kota')) ? 'Pilih kota dulu' : null),
                    ]),

                // === MAP PICKER — cuma buat nandain titik koordinat ===
                Map::make('location')
                    ->label('Tandai Titik Lokasi di Peta')
                    ->columnSpanFull()
                    ->defaultLocation(latitude: -7.834384, longitude: 112.692398) // default Kota Malang
                    ->zoom(15)
                    ->detectRetina()
                    ->showMarker(true)
                    ->draggable(true)
                    ->clickable(true)
                    ->showFullscreenControl(true)
                    ->showZoomControl(true)
                    ->extraStyles([
                        'min-height: 300px',
                        'border-radius: 12px',
                    ])
                    ->afterStateUpdated(function (callable $set, array $state) {
                        $set('latitude', number_format($state['lat'], 6, '.', ''));
                        $set('longitude', number_format($state['lng'], 6, '.', ''));
                    })
                    ->live()
                    ->afterStateHydrated(function (callable $set, callable $get) {
                        $lat = $get('latitude');
                        $lng = $get('longitude');

                        if ($lat && $lng) {
                            $set('location', [
                                'lat' => (float) $lat,
                                'lng' => (float) $lng,
                            ]);
                        }
                    })
                    ->dehydrated(false),

                TextInput::make('latitude')
                    ->label('Latitude')
                    ->id('latitude')
                    ->live(onBlur: true)
                    ->required()
                    ->afterStateUpdated(function (callable $set, callable $get) {
                        $lat = $get('latitude');
                        $lng = $get('longitude');
                        if ($lat && $lng) {
                            $set('location', [
                                'lat' => (float) $lat,
                                'lng' => (float) $lng,
                            ]);
                        }
                    })
                    ->suffixAction(
                        Action::make('getLocationLat')
                            ->icon('heroicon-m-map-pin')
                            ->extraAttributes([
                                'x-on:click' => <<<'JS'
                                    navigator.geolocation.getCurrentPosition(
                                        (position) => {
                                            $wire.set('data.latitude', position.coords.latitude.toFixed(6));
                                            $wire.set('data.longitude', position.coords.longitude.toFixed(6));
                                        },
                                        (error) => alert('Gagal mendapatkan lokasi: ' + error.message)
                                    )
                                JS,
                            ])
                    ),

                TextInput::make('longitude')
                    ->label('Longitude')
                    ->id('longitude')
                    ->live(onBlur: true)
                    ->required()
                    ->afterStateUpdated(function (callable $set, callable $get) {
                        $lat = $get('latitude');
                        $lng = $get('longitude');
                        if ($lat && $lng) {
                            $set('location', [
                                'lat' => (float) $lat,
                                'lng' => (float) $lng,
                            ]);
                        }
                    })
                    ->suffixAction(
                        Action::make('getLocationLng')
                            ->icon('heroicon-m-map-pin')
                            ->extraAttributes([
                                'x-on:click' => <<<'JS'
                                    navigator.geolocation.getCurrentPosition(
                                        (position) => {
                                            $wire.set('data.latitude', position.coords.latitude.toFixed(6));
                                            $wire.set('data.longitude', position.coords.longitude.toFixed(6));
                                        },
                                        (error) => alert('Gagal mendapatkan lokasi: ' + error.message)
                                    )
                                JS,
                            ])
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
