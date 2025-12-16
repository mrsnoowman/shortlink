<?php

namespace App\Filament\Widgets;

use App\Models\DomainCheck;
use App\Models\RedirectLog;
use App\Models\Shortlink;
use App\Models\TargetUrl;
use App\Models\User;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class TrafficChart extends ChartWidget
{
    protected static ?string $heading = 'Activity in the Last 7 Days';

    protected int|string|array $columnSpan = [
        'md' => 2,
    ];

    protected function getData(): array
    {
        /** @var \ App\Models\User|null $user */
        $user = auth()->user();
        $isMaster = $user?->role === 'master';

        // Last 7 days
        $dates = collect(range(6, 0))->map(fn ($i) => Carbon::today()->subDays($i));

        if ($isMaster) {
            // Master: totals for all users
            $userCounts = $dates->map(function (Carbon $date) {
                return User::whereDate('created_at', $date)->count();
            })->all();

            $shortCounts = $dates->map(function (Carbon $date) {
                return Shortlink::whereDate('created_at', $date)->count();
            })->all();

            $targetDomainCounts = $dates->map(function (Carbon $date) {
                return TargetUrl::whereDate('created_at', $date)->count();
            })->all();

            $domainCheckCounts = $dates->map(function (Carbon $date) {
                return DomainCheck::whereDate('created_at', $date)->count();
            })->all();

            $redirectLogCounts = $dates->map(function (Carbon $date) {
                return RedirectLog::whereDate('created_at', $date)->count();
            })->all();

            // Master/admin: show all datasets including Users
            $datasets = [
                [
                    'label' => 'Users',
                    'data' => $userCounts,
                    'borderColor' => '#8b5cf6',
                    'backgroundColor' => 'rgba(139, 92, 246, 0.15)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Shortlinks',
                    'data' => $shortCounts,
                    'borderColor' => '#22c55e',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.15)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Target Domains',
                    'data' => $targetDomainCounts,
                    'borderColor' => '#eab308',
                    'backgroundColor' => 'rgba(234, 179, 8, 0.15)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Domain Checks',
                    'data' => $domainCheckCounts,
                    'borderColor' => '#38bdf8',
                    'backgroundColor' => 'rgba(56, 189, 248, 0.15)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Redirect Logs',
                    'data' => $redirectLogCounts,
                    'borderColor' => '#f59e0b',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.15)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ];
        } else {
            // Admin / user: only Redirect Logs data (scoped)
            $redirectLogCounts = $dates->map(function (Carbon $date) use ($user) {
                if (! $user) {
                    return 0;
                }

                $query = RedirectLog::whereDate('created_at', $date)
                    ->whereHas('shortlink', function ($q) use ($user) {
                        if ($user->role === 'admin') {
                            // Admin: semua redirect dari shortlink user di organisasinya
                            $q->whereHas('user', function ($uq) use ($user) {
                                $uq->where('role_id', $user->role_id);
                            });
                        } else {
                            // User biasa: hanya shortlink miliknya sendiri
                            $q->where('user_id', $user->id);
                        }
                    });

                return $query->count();
            })->all();

            // Scoped redirect logs line
            $datasets = [
                [
                    'label' => 'Redirect Logs',
                    'data' => $redirectLogCounts,
                    'borderColor' => '#f59e0b',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.15)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ];
        }

        return [
            'datasets' => $datasets,
            'labels' => $dates->map(fn (Carbon $date) => $date->format('d M'))->all(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}


