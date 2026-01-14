<?php

namespace App\Filament\Clusters\LoanManagement\Resources\GrantResource\Pages;

use App\Filament\Clusters\LoanManagement\Resources\GrantResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateGrant extends CreateRecord
{
    protected static string $resource = GrantResource::class;
}
