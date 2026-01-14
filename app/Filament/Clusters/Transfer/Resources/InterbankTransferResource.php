<?php

namespace App\Filament\Clusters\Transfer\Resources;

use App\Filament\Clusters\Transfer;
use App\Filament\Clusters\Transfer\BaseTransferResource;
use App\Filament\Clusters\Transfer\Resources\InterbankTransferResource\Pages;
use App\Models\Transfer as TransferModel;

class InterbankTransferResource extends BaseTransferResource
{
    protected static ?string $model = TransferModel::class;
    protected static string $type = 'interbank';

    protected static ?string $navigationIcon = 'heroicon-o-building-library';
    protected static ?string $navigationLabel = 'Interbank Transfers';
    protected static ?string $cluster = Transfer::class;
    protected static ?int $navigationSort = 3;
    protected static ?string $slug = 'interbank';

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInterbankTransfers::route('/'),
            'create' => Pages\CreateInterbankTransfer::route('/create'),
            'edit' => Pages\EditInterbankTransfer::route('/{record}/edit'),
        ];
    }
}
