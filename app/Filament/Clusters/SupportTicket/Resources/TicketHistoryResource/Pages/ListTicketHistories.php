<?php

namespace App\Filament\Clusters\SupportTicket\Resources\TicketHistoryResource\Pages;

use App\Filament\Clusters\SupportTicket\Resources\TicketHistoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTicketHistories extends ListRecords
{
    protected static string $resource = TicketHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
