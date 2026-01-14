<?php

namespace App\Filament\Clusters\LoanManagement\Resources\LoanPlanResource\Pages;

use App\Filament\Clusters\LoanManagement\Resources\LoanPlanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLoanPlans extends ListRecords
{
    protected static string $resource = LoanPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->modalWidth('5xl')
                ->label('Create Loan Plan'),
        ];
    }
}
