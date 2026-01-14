<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class SupportTicket extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static ?string $navigationGroup = 'Support Center';
    protected static ?string $slug = 'support-ticket';
    protected static ?int $navigationSort = 2;
}
