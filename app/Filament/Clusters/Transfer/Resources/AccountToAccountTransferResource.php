<?php

namespace App\Filament\Clusters\Transfer\Resources;

use App\Filament\Clusters\Transfer;
use App\Filament\Clusters\Transfer\BaseTransferResource;
use App\Filament\Clusters\Transfer\Resources\AccountToAccountTransferResource\Pages;
use App\Models\Transfer as TransferModel;

class AccountToAccountTransferResource extends BaseTransferResource
{
    protected static ?string $model = TransferModel::class;
    protected static string $type = 'account-to-account';

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';
    protected static ?string $navigationLabel = 'Account to Account';
    protected static ?string $cluster = Transfer::class;
    protected static ?int $navigationSort = 4;
    protected static ?string $slug = 'account-to-account';

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAccountToAccountTransfers::route('/'),
            'create' => Pages\CreateAccountToAccountTransfer::route('/create'),
            'edit' => Pages\EditAccountToAccountTransfer::route('/{record}/edit'),
        ];
    }
}
