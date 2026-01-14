<?php

namespace App\Filament\Clusters\KycManagement\Resources\KycDocumentResource\Pages;

use App\Filament\Clusters\KycManagement\Resources\KycDocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListKycDocuments extends ListRecords
{
    protected static string $resource = KycDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
