<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;

class UserRegistrationsChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected static ?string $heading = 'User Registrations';

    protected function getData(): array
    {
        $data = \App\Models\User::selectRaw('strftime("%Y-%m", created_at) as month, count(*) as count')
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('count', 'month')
            ->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'User Registrations',
                    'data' => array_values($data),
                ],
            ],
            'labels' => array_keys($data),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
