<?php

namespace App\Filament\Clusters\LoanManagement\Resources\GrantResource\Pages;

use App\Filament\Clusters\LoanManagement\Resources\GrantResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewGrant extends ViewRecord
{
    protected static string $resource = GrantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
