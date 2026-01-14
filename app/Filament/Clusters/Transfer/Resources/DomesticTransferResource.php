<?php

namespace App\Filament\Clusters\Transfer\Resources;

use App\Filament\Clusters\Transfer;
use App\Filament\Clusters\Transfer\BaseTransferResource;
use App\Filament\Clusters\Transfer\Resources\DomesticTransferResource\Pages;
use App\Models\Transfer as TransferModel;

class DomesticTransferResource extends BaseTransferResource
{
    protected static ?string $model = TransferModel::class;
    protected static string $type = 'domestic';

    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?string $navigationLabel = 'Domestic Transfers';
    protected static ?string $cluster = Transfer::class;
    protected static ?int $navigationSort = 2;
    protected static ?string $slug = 'domestic';

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDomesticTransfers::route('/'),
            'create' => Pages\CreateDomesticTransfer::route('/create'),
            'edit' => Pages\EditDomesticTransfer::route('/{record}/edit'),
        ];
    }
}
