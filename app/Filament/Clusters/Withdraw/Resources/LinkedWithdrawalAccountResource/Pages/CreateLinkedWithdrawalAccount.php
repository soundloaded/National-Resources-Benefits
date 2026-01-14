<?php

namespace App\Filament\Clusters\Withdraw\Resources\LinkedWithdrawalAccountResource\Pages;

use App\Filament\Clusters\Withdraw\Resources\LinkedWithdrawalAccountResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLinkedWithdrawalAccount extends CreateRecord
{
    protected static string $resource = LinkedWithdrawalAccountResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
