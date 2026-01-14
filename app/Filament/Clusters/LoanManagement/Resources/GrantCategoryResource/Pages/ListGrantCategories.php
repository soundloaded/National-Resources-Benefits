<?php

namespace App\Filament\Clusters\LoanManagement\Resources\GrantCategoryResource\Pages;

use App\Filament\Clusters\LoanManagement\Resources\GrantCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGrantCategories extends ListRecords
{
    protected static string $resource = GrantCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
