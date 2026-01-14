<?php

namespace App\Filament\Clusters\KycManagement\Resources\KycTemplateResource\Pages;

use App\Filament\Clusters\KycManagement\Resources\KycTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListKycTemplates extends ListRecords
{
    protected static string $resource = KycTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Add New')
                ->modalWidth('xl'),
        ];
    }
}
