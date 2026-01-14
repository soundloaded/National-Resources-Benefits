<?php

namespace App\Filament\Clusters\SupportTicket\Resources\InProgressTicketResource\Pages;

use App\Filament\Clusters\SupportTicket\Resources\InProgressTicketResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInProgressTickets extends ListRecords
{
    protected static string $resource = InProgressTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
