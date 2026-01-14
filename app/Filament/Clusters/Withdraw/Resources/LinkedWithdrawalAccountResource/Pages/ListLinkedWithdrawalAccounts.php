<?php

namespace App\Filament\Clusters\Withdraw\Resources\LinkedWithdrawalAccountResource\Pages;

use App\Filament\Clusters\Withdraw\Resources\LinkedWithdrawalAccountResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLinkedWithdrawalAccounts extends ListRecords
{
    protected static string $resource = LinkedWithdrawalAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
