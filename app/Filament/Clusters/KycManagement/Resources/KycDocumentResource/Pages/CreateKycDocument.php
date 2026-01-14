<?php

namespace App\Filament\Clusters\KycManagement\Resources\KycDocumentResource\Pages;

use App\Filament\Clusters\KycManagement\Resources\KycDocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateKycDocument extends CreateRecord
{
    protected static string $resource = KycDocumentResource::class;
}
