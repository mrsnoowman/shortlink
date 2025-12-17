<?php

namespace App\Filament\Widgets;

use App\Models\DomainCheck;
use App\Models\RedirectLog;
use App\Models\Shortlink;
use App\Models\TargetUrl;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MainOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();
        $isMaster = $user?->role === 'master';

        if ($isMaster) {
            // For master: show all global stats.
            return [
                Stat::make('Total Users', User::count())
                    ->icon('heroicon-o-users'),

                Stat::make('Total Shortlinks', Shortlink::count())
                    ->icon('heroicon-o-link'),

                Stat::make('Total Target Domains', TargetUrl::count())
                    ->icon('heroicon-o-globe-alt'),

                Stat::make('Total Domain Checks', DomainCheck::count())
                    ->icon('heroicon-o-globe-alt'),

                Stat::make('Total Redirect Logs', RedirectLog::count())
                    ->icon('heroicon-o-chart-bar'),
            ];
        }

        // Admin / user scoped stats
        if ($user && $user->role === 'admin') {
            // Admin: agregasi untuk seluruh organisasi
            $orgUserIds = User::where('role_id', $user->role_id)->pluck('id');

            $shortCount = Shortlink::whereIn('user_id', $orgUserIds)->count();

            $domainCount = TargetUrl::whereHas('shortlink', function ($query) use ($orgUserIds) {
                $query->whereIn('user_id', $orgUserIds);
            })->count();

            $domainCheckCount = DomainCheck::whereIn('user_id', $orgUserIds)->count();

            return [
                Stat::make('Organization Shortlinks', $shortCount)
                    ->icon('heroicon-o-link'),

                Stat::make('Organization Domains', $domainCount)
                    ->icon('heroicon-o-globe-alt'),

                Stat::make('Organization Domain Checks', $domainCheckCount)
                    ->icon('heroicon-o-chart-bar'),
            ];
        }

        // Regular user stats (hanya miliknya sendiri)
        $shortCount = Shortlink::where('user_id', $user?->id)->count();

        $domainCount = TargetUrl::whereHas('shortlink', function ($query) use ($user) {
            $query->where('user_id', $user?->id);
        })->count();

        $domainCheckCount = DomainCheck::where('user_id', $user?->id)->count();

        return [
            Stat::make('My Shortlinks', $shortCount)
                ->description(
                    $user && $user->limit_short === null
                        ? 'Limit: Unlimited'
                        : 'Limit: ' . ($user ? number_format($user->limit_short) : '-')
                )
                ->icon('heroicon-o-link'),

            Stat::make('My Domains', $domainCount)
                ->description(
                    $user && $user->limit_domain === null
                        ? 'Limit: Unlimited'
                        : 'Limit: ' . ($user ? number_format($user->limit_domain) : '-')
                )
                ->icon('heroicon-o-globe-alt'),

            Stat::make('My Domain Checks', $domainCheckCount)
                ->description(
                    $user && $user->limit_domain_check === null
                        ? 'Limit: Unlimited'
                        : 'Limit: ' . ($user ? number_format($user->limit_domain_check) : '-')
                )
                ->icon('heroicon-o-chart-bar'),
        ];
    }
}


