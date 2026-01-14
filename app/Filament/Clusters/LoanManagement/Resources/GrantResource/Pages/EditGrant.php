<?php

namespace App\Filament\Clusters\LoanManagement\Resources\GrantResource\Pages;

use App\Filament\Clusters\LoanManagement\Resources\GrantResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGrant extends EditRecord
{
    protected static string $resource = GrantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
