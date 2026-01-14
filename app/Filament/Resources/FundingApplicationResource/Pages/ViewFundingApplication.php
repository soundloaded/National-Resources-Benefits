<?php

namespace App\Filament\Resources\FundingApplicationResource\Pages;

use App\Filament\Resources\FundingApplicationResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewFundingApplication extends ViewRecord
{
    protected static string $resource = FundingApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
