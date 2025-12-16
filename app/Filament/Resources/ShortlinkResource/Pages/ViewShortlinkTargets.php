<?php

namespace App\Filament\Resources\ShortlinkResource\Pages;

use App\Filament\Resources\ShortlinkResource;
use App\Models\Shortlink;
use App\Models\TargetUrl;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Actions as TableActions;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ViewShortlinkTargets extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = ShortlinkResource::class;

    protected static string $view = 'filament.resources.shortlink-resource.pages.view-shortlink-targets';

    public Shortlink $record;

    public function mount(Shortlink $record): void
    {
        $user = Auth::user();

        // Batasi akses:
        // - master  : bisa melihat semua shortlink
        // - admin   : bisa melihat shortlink miliknya & semua user di organisasinya (role_id sama)
        // - user    : hanya bisa melihat shortlink miliknya sendiri
        if ($user?->role !== 'master') {
            $sameOwner = $record->user_id === $user?->id;
            $sameOrganization = $record->user?->role_id !== null
                && $record->user?->role_id === $user?->role_id
                && $user?->role === 'admin';

            if (! $sameOwner && ! $sameOrganization) {
                abort(403);
            }
        }

        $this->record = $record;
    }

    public function getTitle(): string
    {
        return 'Target URLs - ' . $this->record->short_code;
    }

    protected function getTableQuery(): Builder
    {
        return TargetUrl::query()
            ->where('shortlink_id', $this->record->id);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('add_target_url')
                ->label('Add Target URL')
                ->icon('heroicon-o-plus-circle')
                ->color('success')
                ->visible(fn () => Auth::user()?->role !== 'master')
                ->form([
                    Forms\Components\TextInput::make('url')
                        ->label('URL')
                        ->required()
                        ->url()
                        ->maxLength(500)
                        ->placeholder('https://www.example.com')
                        ->columnSpanFull(),
                    Forms\Components\Toggle::make('is_primary')
                        ->label('Set as Primary')
                        ->helperText('Primary target will be used for redirect. If blocked, system will automatically switch to another active target.')
                        ->default(false),
                ])
                ->action(function (array $data): void {
                    $user = $this->record->user;

                    // Check domain limit
                    if ($user) {
                        $currentDomains = TargetUrl::whereHas('shortlink', function ($query) use ($user) {
                            $query->where('user_id', $user->id);
                        })->count();

                        $domainLimit = $user->limit_domain ?? 0;
                        if ($domainLimit > 0 && ($currentDomains + 1) > $domainLimit) {
                            \Filament\Notifications\Notification::make()
                                ->title('Domain limit reached')
                                ->body('You have reached your domain limit.')
                                ->danger()
                                ->send();

                            throw ValidationException::withMessages([
                                'url' => 'Domain limit has been reached.',
                            ]);
                        }

                        // Prevent duplicate target URL for this user
                        $exists = TargetUrl::whereHas('shortlink', function ($query) use ($user) {
                            $query->where('user_id', $user->id);
                        })
                            ->where('url', $data['url'] ?? '')
                            ->exists();

                        if ($exists) {
                            \Filament\Notifications\Notification::make()
                                ->title('URL already registered')
                                ->body('This URL already exists for your account.')
                                ->danger()
                                ->send();

                            throw ValidationException::withMessages([
                                'url' => 'This URL is already registered.',
                            ]);
                        }
                    }

                    // Create target URL first
                    $targetUrl = TargetUrl::create([
                        'shortlink_id' => $this->record->id,
                        'url' => $data['url'],
                        'is_primary' => false, // Set to false first, will update after
                        'is_blocked' => false,
                    ]);

                    // If setting as primary, unset other primary targets
                    if (!empty($data['is_primary'])) {
                        TargetUrl::where('shortlink_id', $this->record->id)
                            ->where('id', '!=', $targetUrl->id)
                            ->update(['is_primary' => false]);
                        $targetUrl->update(['is_primary' => true]);
                    } else {
                        // If no primary exists, set this as primary
                        $hasPrimary = TargetUrl::where('shortlink_id', $this->record->id)
                            ->where('is_primary', true)
                            ->where('id', '!=', $targetUrl->id)
                            ->exists();
                        
                        if (!$hasPrimary) {
                            $targetUrl->update(['is_primary' => true]);
                        }
                    }

                    \Filament\Notifications\Notification::make()
                        ->title('Target URL added successfully')
                        ->success()
                        ->send();
                }),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            TableActions\DeleteAction::make()
                ->icon('heroicon-o-trash')
                ->label('Delete'),
        ];
    }

    protected function getTableBulkActions(): array
    {
        return [
            TableActions\BulkActionGroup::make([
                TableActions\DeleteBulkAction::make()
                    ->icon('heroicon-o-trash'),
            ]),
        ];
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\IconColumn::make('is_primary')
                ->label('Primary')
                ->boolean()
                ->trueIcon('heroicon-o-star')
                ->falseIcon(null)
                ->trueColor('warning')
                ->sortable(),
            Tables\Columns\TextColumn::make('url')
                ->label('URL')
                ->searchable()
                ->sortable()
                ->wrap()
                ->limit(80),
            Tables\Columns\TextColumn::make('is_blocked')
                ->label('Status')
                ->formatStateUsing(fn (bool $state) => $state ? 'Blocked' : 'Active')
                ->badge()
                ->color(fn (bool $state) => $state ? 'danger' : 'success')
                ->icon(fn (bool $state) => $state ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                ->iconPosition('before')
                ->sortable(),
            Tables\Columns\TextColumn::make('updated_at')
                ->label('Last Updated')
                ->dateTime('d M Y, H:i')
                ->sortable(),
            Tables\Columns\TextColumn::make('created_at')
                ->label('Created At')
                ->dateTime('d M Y, H:i')
                ->toggleable(isToggledHiddenByDefault: true)
                ->sortable(),
        ];
    }

    public function getBreadcrumbs(): array
    {
        return [
            static::getResource()::getUrl('index') => static::getResource()::getBreadcrumb(),
            static::getResource()::getUrl('edit', ['record' => $this->record]) => $this->record->short_code,
            $this->getTitle(),
        ];
    }
}

