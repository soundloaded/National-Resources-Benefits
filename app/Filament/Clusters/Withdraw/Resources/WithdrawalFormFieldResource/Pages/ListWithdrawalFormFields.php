<?php

namespace App\Filament\Clusters\Withdraw\Resources\WithdrawalFormFieldResource\Pages;

use App\Filament\Clusters\Withdraw\Resources\WithdrawalFormFieldResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWithdrawalFormFields extends ListRecords
{
    protected static string $resource = WithdrawalFormFieldResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
