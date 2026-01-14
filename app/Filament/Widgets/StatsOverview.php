<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

use App\Models\User;
use App\Models\Account;
use App\Models\Transaction;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        return [
            Stat::make('Total Users', User::count())
                ->description('New users joined')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
            Stat::make('Total Accounts', Account::count())
                ->description('Active bank accounts')
                ->color('primary'),
            Stat::make('Transactions (Today)', Transaction::whereDate('created_at', today())->count())
                ->description('Volume today')
                ->color('warning'),
        ];
    }
}
