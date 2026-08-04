<?php

namespace App\Filament\Resources\Tokos;

use App\Models\Toko;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TokoResource extends Resource
{
    protected static ?string $model = Toko::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static ?string $navigationLabel = 'Toko';

    protected static ?string $modelLabel = 'Toko';

    protected static ?string $pluralModelLabel = 'Toko';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nama_toko')
                    ->label('Nama Toko')
                    ->required()
                    ->maxLength(255),

                TextInput::make('no_telepon')
                    ->label('No. Telepon')
                    ->tel()
                    ->maxLength(20),

                Textarea::make('alamat')
                    ->label('Alamat')
                    ->rows(3)
                    ->columnSpanFull(),

                Select::make('marketers')
                    ->label('Marketer yang Bertugas')
                    ->relationship(
                        name: 'marketers',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn ($query) => $query->where('role', 'marketing'),
                    )
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Toko')
                    ->schema([
                        TextEntry::make('nama_toko')
                            ->label('Nama Toko'),

                        TextEntry::make('no_telepon')
                            ->label('No. Telepon')
                            ->placeholder('-'),

                        TextEntry::make('alamat')
                            ->label('Alamat')
                            ->placeholder('-')
                            ->columnSpanFull(),

                        TextEntry::make('marketers.name')
                            ->label('Marketer Bertugas')
                            ->listWithLineBreaks()
                            ->bulleted()
                            ->placeholder('Belum ada marketer ditugaskan')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Meta')
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Dibuat')
                            ->dateTime('d M Y, H:i'),

                        TextEntry::make('updated_at')
                            ->label('Diperbarui')
                            ->dateTime('d M Y, H:i'),
                    ])
                    ->columns(2)
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama_toko')
                    ->label('Nama Toko')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('marketers.name')
                    ->label('Marketer')
                    ->badge()
                    ->searchable()
                    ->separator(','),

                TextColumn::make('no_telepon')
                    ->label('No. Telepon'),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('marketers')
                    ->label('Marketer')
                    ->relationship('marketers', 'name'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTokos::route('/'),
            'create' => Pages\CreateToko::route('/create'),
            'view' => Pages\ViewToko::route('/{record}'),
            'edit' => Pages\EditToko::route('/{record}/edit'),
        ];
    }
}
