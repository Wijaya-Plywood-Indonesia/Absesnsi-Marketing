<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required(),
                TextInput::make('password')
                    ->password()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn ($state) => filled($state))
                    ->dehydrateStateUsing(fn ($state) => filled($state) ? $state : null),
                Select::make('role')
                    ->options([
                        'admin' => 'Admin',
                        'visitor' => 'Visitor (Atasan)',
                        'marketing' => 'Marketing',
                    ])
                    ->required()
                    ->live()
                    ->default('marketing'),
                Select::make('toko_id')
                    ->label('Toko')
                    ->relationship(name: 'toko', titleAttribute: 'nama_toko')
                    ->searchable()
                    ->preload()
                    ->visible(fn (callable $get) => $get('role') === 'marketing')
                    ->required(fn (callable $get) => $get('role') === 'marketing'),
                TextInput::make('daily_target')
                    ->numeric()
                    ->nullable()
                    ->default(8)
                    ->label('Target Harian'),
            ]);
    }
}
