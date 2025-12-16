<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ShortlinkResource\Pages;
use App\Filament\Resources\ShortlinkResource\RelationManagers;
use App\Models\Shortlink;
use App\Models\Alias;
use App\Models\RedirectLog;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ShortlinkResource extends Resource
{
    protected static ?string $model = Shortlink::class;

    protected static ?string $navigationIcon = 'heroicon-o-link';

    protected static ?string $navigationLabel = 'Shortlinks';

    protected static ?string $modelLabel = 'Shortlink';

    protected static ?string $pluralModelLabel = 'Shortlinks';

    public static function shouldRegisterNavigation(): bool
    {
        return in_array(auth()->user()?->role ?? null, ['master', 'admin', 'user']);
    }

    public static function canViewAny(): bool
    {
        return in_array(auth()->user()?->role ?? null, ['master', 'admin', 'user']);
    }

    public static function canCreate(): bool
    {
        return in_array(auth()->user()?->role ?? null, ['master', 'admin', 'user']);
    }

    public static function canEdit($record): bool
    {
        // Only master can edit
        return auth()->user()?->role === 'master';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->label('User')
                    ->default(fn () => auth()->id())
                    ->options(function () {
                        // Master can pick any user; non-master will not see this field
                        return \App\Models\User::pluck('name', 'id');
                    })
                    ->visible(fn () => auth()->user()?->role === 'master'),
                Forms\Components\Select::make('alias_id')
                    ->label('Alias (Custom Domain)')
                    ->searchable()
                    ->preload()
                    ->options(function () {
                        // Alias sekarang bersifat global (dipakai semua user)
                        return Alias::pluck('custom_domain', 'id');
                    })
                    ->default(function () {
                        // Auto-select alias pertama yang ada (jika ada)
                        $firstAlias = Alias::orderBy('id', 'asc')->first();
                        return $firstAlias ? $firstAlias->id : null;
                    })
                    ->helperText('Optional. Select the custom domain to use for this shortlink.')
                    ->nullable(),
                Forms\Components\TextInput::make('short_code')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255)
                    ->label('Short Code')
                    ->helperText('Short code for the URL (example: abc123).')
                    ->suffixAction(
                        Forms\Components\Actions\Action::make('generate')
                            ->icon('heroicon-o-arrow-path')
                            ->action(function ($set, $livewire) {
                                $maxAttempts = 20;
                                $attempts = 0;
                                $code = '';
                                
                                do {
                                    $code = \Illuminate\Support\Str::random(8);
                                    
                                    // Check if code exists
                                    $query = \App\Models\Shortlink::where('short_code', $code);
                                    
                                    // If editing, ignore current record
                                    if (isset($livewire->record)) {
                                        $query->where('id', '!=', $livewire->record->id);
                                    }
                                    
                                    $exists = $query->exists();
                                    $attempts++;
                                } while ($exists && $attempts < $maxAttempts);
                                
                                if ($attempts >= $maxAttempts) {
                                    \Filament\Notifications\Notification::make()
                                        ->title('Gagal generate code')
                                        ->body('Silakan coba lagi')
                                        ->danger()
                                        ->send();
                                    return;
                                }
                                
                                $set('short_code', $code);
                                
                                \Filament\Notifications\Notification::make()
                                    ->title('Short code berhasil di-generate')
                                    ->success()
                                    ->send();
                            })
                            ->tooltip('Generate random short code')
                    ),
                Forms\Components\Textarea::make('target_urls_text')
                    ->label('Target URLs')
                    ->required()
                    ->rows(10)
                    ->placeholder('https://www.google.com/?q=example' . PHP_EOL . 'https://www.example.com' . PHP_EOL . 'https://www.another-domain.com')
                    ->helperText('Enter full URLs, one per line. After creation, manage target URLs via the "Target URLs" tab on the edit page.')
                    ->columnSpanFull()
                    ->visibleOn('create'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable()
                    // Tampilkan untuk master dan admin agar admin bisa melihat
                    // pemilik masing-masing shortlink dalam organisasinya.
                    ->visible(fn () => in_array(auth()->user()?->role ?? null, ['master', 'admin'])),
                Tables\Columns\TextColumn::make('short_code')
                    ->label('Short URL')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Short URL copied!')
                    ->copyableState(function ($record) {
                        $base = $record->alias?->custom_domain;
                        if ($base) {
                            // Remove scheme for copy text; user wants plain domain/path
                            $host = preg_replace('#^https?://#', '', $base);
                            return rtrim($host, '/') . '/' . $record->short_code;
                        }
                        return url('/' . $record->short_code);
                    })
                    ->url(function ($record) {
                        $base = $record->alias?->custom_domain;
                        if ($base) {
                            $hasScheme = preg_match('#^https?://#', $base);
                            $host = $hasScheme ? $base : 'http://' . $base;
                            return rtrim($host, '/') . '/' . $record->short_code;
                        }
                        return url('/' . $record->short_code);
                    })
                    ->openUrlInNewTab()
                    ->icon('heroicon-o-link')
                    ->iconColor('primary'),
                Tables\Columns\TextColumn::make('open')
                    ->label('Open')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->iconColor('primary')
                    ->url(function ($record) {
                        $base = $record->alias?->custom_domain;
                        if ($base) {
                            $hasScheme = preg_match('#^https?://#', $base);
                            $host = $hasScheme ? $base : 'http://' . $base;
                            return rtrim($host, '/') . '/' . $record->short_code;
                        }
                        return url('/' . $record->short_code);
                    })
                    ->openUrlInNewTab()
                    ->tooltip('Open short URL')
                    ->state(fn () => 'Open'),
                Tables\Columns\TextColumn::make('targetUrls')
                    ->label('Target URLs')
                    ->formatStateUsing(function ($record) {
                        $targetUrls = $record->targetUrls;
                        if ($targetUrls->isEmpty()) {
                            return 'No URLs';
                        }
                        
                        // Priority: 1. Primary yang tidak blocked, 2. Active pertama, 3. Blocked pertama (jika semua blocked)
                        $displayUrl = null;
                        
                        // 1. Cari primary yang tidak blocked
                        $primaryActive = $targetUrls->where('is_primary', true)
                            ->where('is_blocked', false)
                            ->first();
                        
                        if ($primaryActive) {
                            $displayUrl = $primaryActive;
                        } else {
                            // 2. Cari yang aktif (tidak blocked) pertama
                            $activeFirst = $targetUrls->where('is_blocked', false)->first();
                            
                            if ($activeFirst) {
                                $displayUrl = $activeFirst;
                            } else {
                                // 3. Jika semua blocked, tampilkan yang blocked pertama
                                $displayUrl = $targetUrls->first();
                            }
                        }
                        
                        if (!$displayUrl) {
                            return 'No URLs';
                        }
                        
                        $preview = $displayUrl->url;
                        if ($targetUrls->count() > 1) {
                            $preview .= ' (+' . ($targetUrls->count() - 1) . ' more)';
                        }
                        return $preview;
                    })
                    ->icon(function ($record) {
                        $targetUrls = $record->targetUrls;
                        if ($targetUrls->isEmpty()) {
                            return null;
                        }
                        
                        // Priority: 1. Primary yang tidak blocked, 2. Active pertama, 3. Blocked pertama
                        $primaryActive = $targetUrls->where('is_primary', true)
                            ->where('is_blocked', false)
                            ->first();
                        
                        if ($primaryActive) {
                            return 'heroicon-o-check-circle';
                        }
                        
                        $activeFirst = $targetUrls->where('is_blocked', false)->first();
                        if ($activeFirst) {
                            return 'heroicon-o-check-circle';
                        }
                        
                        return 'heroicon-o-no-symbol';
                    })
                    ->iconColor(function ($record) {
                        $targetUrls = $record->targetUrls;
                        if ($targetUrls->isEmpty()) {
                            return null;
                        }
                        
                        // Priority: 1. Primary yang tidak blocked, 2. Active pertama, 3. Blocked pertama
                        $primaryActive = $targetUrls->where('is_primary', true)
                            ->where('is_blocked', false)
                            ->first();
                        
                        if ($primaryActive) {
                            return 'success';
                        }
                        
                        $activeFirst = $targetUrls->where('is_blocked', false)->first();
                        if ($activeFirst) {
                            return 'success';
                        }
                        
                        return 'danger';
                    })
                    ->iconPosition('before')
                    ->tooltip(function ($record) {
                        $targetUrls = $record->targetUrls;
                        if ($targetUrls->isEmpty()) {
                            return 'No target URLs';
                        }
                        $lines = [];
                        foreach ($targetUrls as $targetUrl) {
                            $status = $targetUrl->is_blocked ? 'Blocked' : 'Active';
                            $primary = $targetUrl->is_primary ? ' [Primary]' : '';
                            $lines[] = $targetUrl->url . ' [' . $status . $primary . ']';
                        }
                        return implode("\n", $lines);
                    })
                    ->wrap()
                    ->limit(50)
                    ->searchable(false)
                    ->sortable(false),
                Tables\Columns\TextColumn::make('redirect_logs_count')
                    ->label('Total Clicks')
                    ->sortable()
                    ->badge()
                    ->color('primary')
                    ->url(fn ($record) => static::getUrl('analytics', ['record' => $record]))
                    ->openUrlInNewTab()
                    ->tooltip('Lihat analitik shortlink ini'),
                Tables\Columns\TextColumn::make('active_urls_count')
                    ->label('Active URLs')
                    ->state(function ($record) {
                        return $record->targetUrls->where('is_blocked', false)->count();
                    })
                    ->badge()
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->iconPosition('before')
                    ->sortable(false),
                Tables\Columns\TextColumn::make('blocked_urls_count')
                    ->label('Blocked URLs')
                    ->state(function ($record) {
                        return $record->targetUrls->where('is_blocked', true)->count();
                    })
                    ->badge()
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->iconPosition('before')
                    ->default(0)
                    ->sortable(false),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('view_target_urls')
                    ->label('Detail')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->tooltip('View Target URLs Detail')
                    ->url(fn ($record) => static::getUrl('targets', ['record' => $record]))
                    ->openUrlInNewTab(false),
                Tables\Actions\EditAction::make()
                    ->icon('heroicon-o-pencil-square')
                    ->label('Edit')
                    ->visible(fn () => auth()->user()?->role === 'master'),
                Tables\Actions\DeleteAction::make()
                    ->icon('heroicon-o-trash')
                    ->label('Delete'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->icon('heroicon-o-trash'),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\TargetUrlsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListShortlinks::route('/'),
            'create' => Pages\CreateShortlink::route('/create'),
            'edit' => Pages\EditShortlink::route('/{record}/edit'),
            'analytics' => Pages\ShortlinkAnalytics::route('/{record}/analytics'),
            'targets' => Pages\ViewShortlinkTargets::route('/{record}/targets'),
        ];
    }

    /**
     * Eager load relations to reduce N+1 queries on listing/actions.
     */
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['user', 'targetUrls'])
            ->withCount('redirectLogs');

        $user = auth()->user();

        if ($user && $user->role === 'admin') {
            // Admin: bisa melihat shortlink miliknya dan semua user di organisasinya
            $query->whereHas('user', function (Builder $q) use ($user) {
                $q->where('role_id', $user->role_id);
            });
        } elseif ($user && $user->role === 'user') {
            // User biasa: hanya melihat shortlink miliknya sendiri
            $query->where('user_id', $user->id);
        }

        return $query;
    }
}

