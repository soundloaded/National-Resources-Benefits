<?php

namespace App\Filament\Clusters\Withdraw\Resources\WithdrawalFormFieldResource\Pages;

use App\Filament\Clusters\Withdraw\Resources\WithdrawalFormFieldResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWithdrawalFormField extends EditRecord
{
    protected static string $resource = WithdrawalFormFieldResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
