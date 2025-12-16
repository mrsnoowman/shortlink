<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class WelcomeInfo extends StatsOverviewWidget
{
    // Follow parent signature (non-static) for heading
    protected ?string $heading = 'Account Status & Limits';

    // Buat lebar card sama seperti card Welcome (full width grid)
    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();

        $role = ucfirst($user?->role ?? 'user');

        return [
            Stat::make('Active Role', $role)
                ->description('Your current access level.')
                ->icon('heroicon-o-user-circle'),

            Stat::make('Shortlink Limit', $user && $user->limit_short == 0 ? 'Unlimited' : number_format($user->limit_short ?? 0))
                ->description('Maximum number of shortlinks.')
                ->icon('heroicon-o-link')
                ->color('success'),

            Stat::make('Domain Check Limit', $user && $user->limit_domain_check == 0 ? 'Unlimited' : number_format($user->limit_domain_check ?? 0))
                ->description('Maximum number of domain checks.')
                ->icon('heroicon-o-globe-alt')
                ->color('info'),
        ];
    }
}


