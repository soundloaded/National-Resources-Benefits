<?php

namespace App\Filament\Resources\UserResource\Widgets;

use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class UserStatsOverview extends BaseWidget
{
    public ?User $record = null;

    protected function getStats(): array
    {
        $kycStatus = $this->record?->kycdocuments()->latest()->first()?->status ?? 'Not Submitted';
        $kycColor = match($kycStatus) {
            'approved' => 'success',
            'rejected' => 'danger',
            'pending' => 'warning',
            default => 'gray',
        };

        return [
            Stat::make('Account Balance', $this->record ? '$' . number_format($this->record->accounts()->sum('balance'), 2) : '$0.00')
                ->description('Total funds')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
            Stat::make('KYC Status', ucfirst($kycStatus))
                ->color($kycColor),
            Stat::make('Total Transactions', $this->record ? $this->record->accounts->flatMap->transactions->count() : 0)
                ->chart([7, 2, 10, 3, 15, 4, 17])
                ->color('info'),
        ];
    }
}
