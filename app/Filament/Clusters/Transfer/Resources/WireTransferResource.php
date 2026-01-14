<?php

namespace App\Filament\Clusters\Transfer\Resources;

use App\Filament\Clusters\Transfer;
use App\Filament\Clusters\Transfer\BaseTransferResource;
use App\Filament\Clusters\Transfer\Resources\WireTransferResource\Pages;
use App\Models\Transfer as TransferModel;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\ListRecords;

class WireTransferResource extends BaseTransferResource
{
    protected static ?string $model = TransferModel::class;
    protected static string $type = 'wire';

    protected static ?string $navigationIcon = 'heroicon-o-bolt';
    protected static ?string $navigationLabel = 'Wire Transfers';
    protected static ?string $cluster = Transfer::class;
    protected static ?int $navigationSort = 1;
    protected static ?string $slug = 'wire';

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWireTransfers::route('/'),
            'create' => Pages\CreateWireTransfer::route('/create'),
            'edit' => Pages\EditWireTransfer::route('/{record}/edit'),
        ];
    }
}
