<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class KycManagement extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-identification';
    protected static ?string $navigationGroup = 'Content Management';
    protected static ?string $slug = 'kyc-management';
    protected static ?string $navigationLabel = 'KYC Management';
}
