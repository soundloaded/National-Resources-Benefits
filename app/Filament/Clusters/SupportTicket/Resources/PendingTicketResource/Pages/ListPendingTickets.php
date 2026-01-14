<?php

namespace App\Filament\Clusters\SupportTicket\Resources\PendingTicketResource\Pages;

use App\Filament\Clusters\SupportTicket\Resources\PendingTicketResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPendingTickets extends ListRecords
{
    protected static string $resource = PendingTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
