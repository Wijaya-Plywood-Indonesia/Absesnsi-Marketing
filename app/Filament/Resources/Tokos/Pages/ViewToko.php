<?php

namespace App\Filament\Resources\Tokos\Pages;

use App\Filament\Resources\Tokos\TokoResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewToko extends ViewRecord
{
    protected static string $resource = TokoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
