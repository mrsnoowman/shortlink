<?php

namespace App\Filament\Resources\ShortlinkResource\Pages;

use App\Filament\Resources\ShortlinkResource;
use App\Models\RedirectLog;
use App\Models\Shortlink;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class ShortlinkAnalytics extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = ShortlinkResource::class;

    protected static string $view = 'filament.resources.shortlink-resource.analytics';

    public Shortlink $record;

    public array $chartLabels = [];

    public array $chartData = [];

    public function getTitle(): string
    {
        // Use a simple title for the page header; the colored short code
        // is rendered in the custom heading slot in the Blade view.
        return 'Shortlink Analytics';
    }

    public function mount(Shortlink $record): void
    {
        $user = Auth::user();

        // Batasi akses: non-master hanya bisa lihat milik sendiri
        if ($user?->role !== 'master' && $record->user_id !== $user?->id) {
            abort(403);
        }

        $this->record = $record;

        // Data for the chart (last 30 days)
        $startDate = Carbon::today()->subDays(29);

        $logs = RedirectLog::query()
            ->selectRaw('DATE(created_at) as date, COUNT(*) as total')
            ->where('shortlink_id', $record->id)
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $dateMap = $logs->pluck('total', 'date')->all();

        $labels = [];
        $data = [];

        for ($i = 0; $i < 30; $i++) {
            $date = $startDate->copy()->addDays($i);
            $key = $date->toDateString();
            $labels[] = $date->format('d M');
            $data[] = $dateMap[$key] ?? 0;
        }

        $this->chartLabels = $labels;
        $this->chartData = $data;
    }

    /**
     * Tabel detail redirect menggunakan tabel bawaan Filament.
     */
    protected function getTableQuery(): Builder
    {
        return RedirectLog::query()
            ->where('shortlink_id', $this->record->id)
            ->latest();
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('created_at')
                ->label('Waktu')
                ->dateTime('d M Y H:i:s')
                ->sortable(),
            Tables\Columns\TextColumn::make('ip')
                ->label('IP')
                ->searchable()
                ->toggleable(),
            Tables\Columns\TextColumn::make('country')
                ->label('Negara')
                ->searchable()
                ->toggleable(),
            Tables\Columns\TextColumn::make('referrer')
                ->label('Referrer')
                ->limit(40)
                ->tooltip(fn ($record) => $record->referrer)
                ->wrap()
                ->toggleable(),
            Tables\Columns\TextColumn::make('browser')
                ->label('Browser')
                ->formatStateUsing(fn ($state, $record) => trim($record->browser . ' ' . $record->browser_version))
                ->toggleable(),
            Tables\Columns\TextColumn::make('platform')
                ->label('Platform')
                ->formatStateUsing(fn ($state, $record) => trim($record->platform . ' ' . $record->platform_version))
                ->toggleable(),
            Tables\Columns\TextColumn::make('device_type')
                ->label('Device')
                ->toggleable(),
        ];
    }
}


