<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DomainCheckResource\Pages;
use App\Models\DomainCheck;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class DomainCheckResource extends Resource
{
    protected static ?string $model = DomainCheck::class;

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?string $navigationLabel = 'Domain Checker';

    protected static ?string $modelLabel = 'Domain Check';

    protected static ?string $pluralModelLabel = 'Domain Checks';

    /**
    * Normalize role to lowercase to avoid casing issues in checks.
    */
    public static function currentRole(): ?string
    {
        $role = auth()->user()?->role;
        return $role ? strtolower($role) : null;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return in_array(static::currentRole(), ['master', 'admin', 'user']);
    }

    public static function canViewAny(): bool
    {
        return in_array(static::currentRole(), ['master', 'admin', 'user']);
    }

    public static function canCreate(): bool
    {
        return in_array(static::currentRole(), ['master', 'admin', 'user']);
    }

    public static function canEdit($record): bool
    {
        // Only master can edit
        return static::currentRole() === 'master';
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
                        // Hanya master yang boleh memilih user lain
                        return User::pluck('name', 'id');
                    })
                    ->visible(fn () => static::currentRole() === 'master'),
                Forms\Components\Textarea::make('domains')
                    ->label('Domains')
                    ->required()
                    ->rows(8)
                    ->helperText('Enter domains to check (one per line, without http/https). Example: example.com')
                    ->placeholder('example.com' . PHP_EOL . 'another-domain.com' . PHP_EOL . 'third-domain.com')
                    ->columnSpanFull()
                    ->rules([
                        function () {
                            return function (string $attribute, $value, \Closure $fail) {
                                if (empty($value)) {
                                    $fail('Please enter at least one domain.');
                                    return;
                                }
                                
                                $domains = array_filter(array_map('trim', explode("\n", $value)));
                                if (empty($domains)) {
                                    $fail('Please enter at least one domain.');
                                    return;
                                }
                                
                                $invalidDomains = [];
                                foreach ($domains as $domain) {
                                    $domain = trim($domain);
                                    if (empty($domain)) {
                                        continue;
                                    }
                                    
                                    // Remove http:// or https:// if present
                                    $domain = preg_replace('#^https?://#', '', $domain);
                                    $domain = rtrim($domain, '/');
                                    
                                    if (!preg_match('/^([a-zA-Z0-9-]+\.)+[a-zA-Z]{2,}$/', $domain)) {
                                        $invalidDomains[] = $domain;
                                    }
                                }
                                
                                if (!empty($invalidDomains)) {
                                    $fail('Invalid domain format: ' . implode(', ', $invalidDomains));
                                }
                            };
                        },
                    ]),
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
                    ->visible(fn () => static::currentRole() === 'master'),
                Tables\Columns\TextColumn::make('domain')
                    ->label('Domain Name')
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
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->icon('heroicon-o-pencil-square')
                    ->label('Edit')
                    ->visible(fn () => static::currentRole() === 'master'),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDomainChecks::route('/'),
            'create' => Pages\CreateDomainCheck::route('/create'),
            'edit' => Pages\EditDomainCheck::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $user = auth()->user();

        // Non-master (admin & user) hanya melihat domain checks milik sendiri
        if ($user && $user->role !== 'master') {
            $query->where('user_id', $user->id);
        }

        return $query->with('user');
    }

    /**
     * Enforce domain check limit for the selected user.
     */
    public static function validateDomainCheckLimit(int $userId): void
    {
        $user = User::find($userId);

            if (!$user) {
                \Filament\Notifications\Notification::make()
                    ->title('User not found')
                    ->danger()
                    ->send();

                throw ValidationException::withMessages([
                    'user_id' => 'User not found.',
                ]);
        }

        $limit = $user->limit_domain_check ?? 0;
        if ($limit > 0) {
            $currentChecks = DomainCheck::where('user_id', $userId)->count();
            if ($currentChecks >= $limit) {
                \Filament\Notifications\Notification::make()
                    ->title('Domain check limit reached')
                    ->body('This user has already reached the domain check limit.')
                    ->danger()
                    ->send();

                throw ValidationException::withMessages([
                    'user_id' => 'Domain check limit for this user has been reached.',
                ]);
            }
        }
    }
}

