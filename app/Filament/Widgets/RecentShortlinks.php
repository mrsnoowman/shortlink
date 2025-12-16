<?php

namespace App\Filament\Widgets;

use App\Models\Shortlink;
use Filament\Tables;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentShortlinks extends BaseWidget
{
    protected static ?string $heading = 'Shortlink Terbaru';

    protected int|string|array $columnSpan = [
        'md' => 1,
    ];

    protected function getTableQuery(): Builder
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();
        $isMaster = $user?->role === 'master';

        $query = Shortlink::query()->latest();

        if ($user && $user->role === 'admin') {
            // Admin: shortlink milik dia dan semua user di organisasinya
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
            Tables\Columns\TextColumn::make('short_code')
                ->label('Kode')
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('target_url')
                ->label('Target')
                ->limit(40)
                ->tooltip(fn ($record) => $record->target_url)
                ->wrap(),
            Tables\Columns\TextColumn::make('user.name')
                ->label('User')
                // Always show user name so admins can see which user owns each shortlink
                ->sortable(),
            Tables\Columns\TextColumn::make('created_at')
                ->label('Dibuat')
                ->since(),
        ];
    }
}


