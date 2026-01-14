<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class LoanManagement extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Content Management';
    protected static ?string $navigationLabel = 'Loan Management';
    protected static ?string $slug = 'loan-management';
}
