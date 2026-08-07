<?php

namespace App\Filament\Resources\Customers\Schemas;

use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class CustomerInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                ImageEntry::make('foto')
                                    ->label(false)
                                    ->disk('public')
                                    ->height(220)
                                    ->extraImgAttributes([
                                        'style' => 'border-radius: 0.75rem; object-fit: cover; width: 100%;',
                                        'loading' => 'lazy',
                                    ])
                                    ->visible(fn ($record) => filled($record->foto))
                                    ->columnSpan(1),

                                Group::make([
                                    TextEntry::make('name')
                                        ->label('Nama Customer')
                                        ->weight('bold')
                                        ->size('lg'),

                                    TextEntry::make('status')
                                        ->label('Status')
                                        ->badge()
                                        ->icon(fn (string $state): string => match ($state) {
                                            'Baru' => 'heroicon-o-sparkles',
                                            'Lama' => 'heroicon-o-check-badge',
                                            default => 'heroicon-o-tag',
                                        })
                                        ->color(fn (string $state): string => match ($state) {
                                            'Baru' => 'warning',
                                            'Lama' => 'success',
                                            default => 'gray',
                                        }),

                                    TextEntry::make('pola')
                                        ->label('Pola Beli')
                                        ->badge()
                                        ->icon(fn (string $state): string => match ($state) {
                                            'Eceran' => 'heroicon-o-shopping-bag',
                                            'Partai' => 'heroicon-o-truck',
                                            default => 'heroicon-o-tag',
                                        })
                                        ->color(fn (string $state): string => match ($state) {
                                            'Eceran' => 'info',
                                            'Partai' => 'success',
                                            default => 'gray',
                                        }),

                                    TextEntry::make('jenis')
                                        ->label('Jenis Usaha')
                                        ->badge()
                                        ->icon('heroicon-o-building-storefront')
                                        ->color('gray'),

                                    TextEntry::make('user.name')
                                        ->label('Marketer yang Ditugaskan')
                                        ->icon('heroicon-o-user-circle')
                                        ->placeholder('Belum ditugaskan')
                                        ->badge()
                                        ->color('primary'),
                                ])
                                    ->columnSpan(2),
                            ]),
                    ]),

                Section::make('Kontak & Alamat')
                    ->icon('heroicon-o-identification')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('phone')
                                    ->label('No. Telepon')
                                    ->icon('heroicon-o-phone')
                                    ->copyable()
                                    ->copyMessage('Nomor disalin')
                                    ->url(fn ($record) => filled($record->phone)
                                        ? 'https://wa.me/'.preg_replace('/[^0-9]/', '', preg_replace('/^0/', '62', $record->phone))
                                        : null)
                                    ->openUrlInNewTab()
                                    ->color('primary'),

                                TextEntry::make('kota')
                                    ->label('Kota/Kabupaten')
                                    ->icon('heroicon-o-map'),

                                TextEntry::make('kecamatan')
                                    ->label('Kecamatan')
                                    ->icon('heroicon-o-map-pin'),
                            ]),

                        TextEntry::make('address')
                            ->label('Alamat Lengkap')
                            ->icon('heroicon-o-home')
                            ->columnSpanFull()
                            ->prose(),
                    ])
                    ->columns(1)
                    ->collapsible(),

                Section::make('Titik Lokasi')
                    ->icon('heroicon-o-globe-asia-australia')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('latitude')
                                    ->label('Latitude')
                                    ->icon('heroicon-o-arrows-up-down')
                                    ->copyable()
                                    ->placeholder('—'),

                                TextEntry::make('longitude')
                                    ->label('Longitude')
                                    ->icon('heroicon-o-arrows-right-left')
                                    ->copyable()
                                    ->placeholder('—'),

                                TextEntry::make('maps_link')
                                    ->label('Peta')
                                    ->state('Buka di Google Maps')
                                    ->icon('heroicon-o-map-pin')
                                    ->color('primary')
                                    ->visible(fn ($record) => filled($record->latitude) && filled($record->longitude))
                                    ->url(fn ($record) => filled($record->latitude) && filled($record->longitude)
                                        ? "https://www.google.com/maps?q={$record->latitude},{$record->longitude}"
                                        : null)
                                    ->openUrlInNewTab(),
                            ]),

                        TextEntry::make('map_preview')
                            ->hiddenLabel()
                            ->visible(fn ($record) => filled($record->latitude) && filled($record->longitude))
                            ->state(fn ($record) => new HtmlString(
                                '<iframe
                                    src="https://maps.google.com/maps?q='.$record->latitude.','.$record->longitude.'&z=16&output=embed"
                                    style="width:100%; height:280px; border:0; border-radius:0.75rem;"
                                    loading="lazy"
                                ></iframe>'
                            ))
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                Section::make('Ringkasan Aktivitas')
                    ->icon('heroicon-o-chart-bar')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('visits_count')
                                    ->label('Total Kunjungan')
                                    ->icon('heroicon-o-map-pin')
                                    ->badge()
                                    ->color('info')
                                    ->state(fn ($record) => $record->visits()->count()),

                                TextEntry::make('last_visit')
                                    ->label('Kunjungan Terakhir')
                                    ->icon('heroicon-o-clock')
                                    ->placeholder('Belum pernah dikunjungi')
                                    ->state(fn ($record) => optional($record->visits()->latest('tanggal')->first())->tanggal)
                                    ->date(),

                                TextEntry::make('orders_count')
                                    ->label('Total Pesanan')
                                    ->icon('heroicon-o-shopping-cart')
                                    ->badge()
                                    ->color('success')
                                    ->state(fn ($record) => $record->orders()->count()),
                            ]),
                    ])
                    ->collapsible(),

                Section::make('Riwayat Sistem')
                    ->icon('heroicon-o-clock')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('created_at')
                                    ->label('Dibuat')
                                    ->icon('heroicon-o-calendar')
                                    ->dateTime('d M Y, H:i')
                                    ->since()
                                    ->dateTimeTooltip(),

                                TextEntry::make('updated_at')
                                    ->label('Diperbarui')
                                    ->icon('heroicon-o-calendar-days')
                                    ->dateTime('d M Y, H:i')
                                    ->since()
                                    ->dateTimeTooltip(),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}
