<?php

namespace App\Filament\Clusters\Withdraw\Resources\ScheduledWithdrawResource\Pages;

use App\Filament\Clusters\Withdraw\Resources\ScheduledWithdrawResource;
use Filament\Resources\Pages\ListRecords;

class ListScheduledWithdraws extends ListRecords
{
    protected static string $resource = ScheduledWithdrawResource::class;
}
