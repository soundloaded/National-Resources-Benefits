<?php

namespace App\Filament\Resources\VoucherResource\Widgets;

use App\Models\Voucher;
use App\Models\VoucherRedemption;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class VoucherStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalVouchers = Voucher::count();
        $activeVouchers = Voucher::where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expiration_date')
                    ->orWhere('expiration_date', '>', now());
            })
            ->count();
        
        $totalRedemptions = VoucherRedemption::where('status', 'completed')->count();
        $totalValueRedeemed = VoucherRedemption::where('status', 'completed')->sum('amount_redeemed');

        $totalVoucherValue = Voucher::where('is_active', true)->sum('value');

        return [
            Stat::make('Total Vouchers', $totalVouchers)
                ->description($activeVouchers . ' active')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('primary'),

            Stat::make('Total Redemptions', $totalRedemptions)
                ->description('Completed redemptions')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Value Redeemed', '$' . number_format($totalValueRedeemed, 2))
                ->description('Total amount redeemed')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('warning'),

            Stat::make('Active Voucher Value', '$' . number_format($totalVoucherValue, 2))
                ->description('Total value of active vouchers')
                ->descriptionIcon('heroicon-m-ticket')
                ->color('info'),
        ];
    }
}
