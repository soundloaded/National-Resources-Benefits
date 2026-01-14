<?php

namespace App\Filament\Clusters\LoanManagement\Resources\GrantCategoryResource\Pages;

use App\Filament\Clusters\LoanManagement\Resources\GrantCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGrantCategory extends EditRecord
{
    protected static string $resource = GrantCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
