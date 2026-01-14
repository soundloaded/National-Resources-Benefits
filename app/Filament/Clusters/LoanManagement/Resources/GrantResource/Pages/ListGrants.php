<?php

namespace App\Filament\Clusters\LoanManagement\Resources\GrantResource\Pages;

use App\Filament\Clusters\LoanManagement\Resources\GrantResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGrants extends ListRecords
{
    protected static string $resource = GrantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
