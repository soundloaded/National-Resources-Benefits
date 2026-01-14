<?php

namespace App\Filament\Clusters\SupportTicket\Resources\ClosedTicketResource\Pages;

use App\Filament\Clusters\SupportTicket\Resources\ClosedTicketResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListClosedTickets extends ListRecords
{
    protected static string $resource = ClosedTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
