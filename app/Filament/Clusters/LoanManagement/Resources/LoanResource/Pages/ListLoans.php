<?php

namespace App\Filament\Clusters\LoanManagement\Resources\LoanResource\Pages;

use App\Filament\Clusters\LoanManagement\Resources\LoanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLoans extends ListRecords
{
    protected static string $resource = LoanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New Loan')
                ->modalWidth('lg'),
        ];
    }
}
