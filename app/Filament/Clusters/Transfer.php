<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class Transfer extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';
    protected static ?string $navigationGroup = 'Financial Management';
    protected static ?string $navigationLabel = 'Transfers';
    protected static ?string $slug = 'transfer';
}
