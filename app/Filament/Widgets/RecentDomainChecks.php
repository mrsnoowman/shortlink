<?php

namespace App\Filament\Widgets;

use App\Models\DomainCheck;
use Filament\Tables;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentDomainChecks extends BaseWidget
{
    protected static ?string $heading = 'Recent Domain Checks';

    protected int|string|array $columnSpan = [
        'md' => 1,
    ];

    protected function getTableQuery(): Builder
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();
        $isMaster = $user?->role === 'master';

        $query = DomainCheck::query()->latest();

        if ($user && $user->role === 'admin') {
            // Admin: domain check miliknya dan semua user di organisasinya
            $query->whereHas('user', function (Builder $q) use ($user) {
                $q->where('role_id', $user->role_id);
            });
        } elseif (! $isMaster && $user) {
            // User biasa: hanya miliknya sendiri
            $query->where('user_id', $user->id);
        }

        return $query->limit(5);
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('domain')
                ->label('Domain')
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('is_blocked')
                ->label('Status')
                ->formatStateUsing(fn ($state) => $state ? 'Blocked' : 'Active')
                ->badge()
                ->color(fn ($state) => $state ? 'danger' : 'success')
                ->icon(fn ($state) => $state ? 'heroicon-o-no-symbol' : 'heroicon-o-check-circle')
                ->iconPosition('before')
                ->sortable(),
            Tables\Columns\TextColumn::make('user.name')
                ->label('User')
                ->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\TextColumn::make('created_at')
                ->label('Created')
                ->since(),
        ];
    }
}


