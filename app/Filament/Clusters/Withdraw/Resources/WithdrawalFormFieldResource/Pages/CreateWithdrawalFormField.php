<?php

namespace App\Filament\Clusters\Withdraw\Resources\WithdrawalFormFieldResource\Pages;

use App\Filament\Clusters\Withdraw\Resources\WithdrawalFormFieldResource;
use Filament\Resources\Pages\CreateRecord;

class CreateWithdrawalFormField extends CreateRecord
{
    protected static string $resource = WithdrawalFormFieldResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
