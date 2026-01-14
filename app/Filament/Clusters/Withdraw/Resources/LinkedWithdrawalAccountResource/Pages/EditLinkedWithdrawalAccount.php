<?php

namespace App\Filament\Clusters\Withdraw\Resources\LinkedWithdrawalAccountResource\Pages;

use App\Filament\Clusters\Withdraw\Resources\LinkedWithdrawalAccountResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLinkedWithdrawalAccount extends EditRecord
{
    protected static string $resource = LinkedWithdrawalAccountResource::class;

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
