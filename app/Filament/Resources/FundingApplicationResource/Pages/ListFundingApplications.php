<?php

namespace App\Filament\Resources\FundingApplicationResource\Pages;

use App\Filament\Resources\FundingApplicationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListFundingApplications extends ListRecords
{
    protected static string $resource = FundingApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Applications')
                ->badge(static::getModel()::count()),
            
            'pending' => Tab::make('Pending')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending'))
                ->badge(static::getModel()::where('status', 'pending')->count())
                ->badgeColor('warning'),
            
            'under_review' => Tab::make('Under Review')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'under_review'))
                ->badge(static::getModel()::where('status', 'under_review')->count())
                ->badgeColor('info'),
            
            'approved' => Tab::make('Approved')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'approved'))
                ->badge(static::getModel()::where('status', 'approved')->count())
                ->badgeColor('success'),
            
            'disbursed' => Tab::make('Disbursed')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'disbursed'))
                ->badge(static::getModel()::where('status', 'disbursed')->count())
                ->badgeColor('success'),
            
            'rejected' => Tab::make('Rejected')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'rejected'))
                ->badge(static::getModel()::where('status', 'rejected')->count())
                ->badgeColor('danger'),
        ];
    }
}
