<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
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
                DateTimePicker::make('email_verified_at'),
                TextInput::make('password')
                    ->password()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn ($state) => filled($state))
                    ->dehydrateStateUsing(fn ($state) => filled($state) ? $state : null),
                Select::make('role')
                    ->options([
                        'admin' => 'Admin',
                        'marketing' => 'Marketing',
                    ])
                    ->required()
                    ->default('marketing'),
                TextInput::make('daily_target')
                    ->numeric()
                    ->nullable()
                    ->default(8)
                    ->label('Target Harian'),
            ]);
    }
}
