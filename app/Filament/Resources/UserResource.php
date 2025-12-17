<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Users';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $modelLabel = 'User';

    protected static ?string $pluralModelLabel = 'Users';

    public static function shouldRegisterNavigation(): bool
    {
        // Only master and organization admins can manage users
        return in_array(auth()->user()?->role ?? null, ['master', 'admin']);
    }

    public static function canViewAny(): bool
    {
        return in_array(auth()->user()?->role ?? null, ['master', 'admin']);
    }

    public static function canCreate(): bool
    {
        return in_array(auth()->user()?->role ?? null, ['master', 'admin']);
    }

    public static function canEdit($record): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        if ($user->role === 'master') {
            return true;
        }

        if ($user->role === 'admin') {
            // Admin hanya boleh edit user di bawah organisasinya sendiri
            return $record->role === 'user' && $record->role_id === $user->role_id;
        }

        // Admin hanya boleh edit dirinya sendiri (atau bisa disesuaikan)
        return $record->id === $user->id;
    }

    public static function canDelete($record): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        if ($user->role === 'master') {
            return true;
        }

        if ($user->role === 'admin') {
            // Admin hanya boleh menghapus user di bawah organisasinya sendiri
            return $record->role === 'user' && $record->role_id === $user->role_id;
        }

        return false;
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user?->role === 'admin') {
            // Admin hanya melihat user dengan role 'user' di dalam organisasinya sendiri
            $query
                ->where('role', 'user')
                ->where('role_id', $user->role_id);
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('role')
                    ->label('Access level')
                    ->options(function () {
                        $role = auth()->user()?->role;

                        if ($role === 'master') {
                            return [
                                'master' => 'Master',
                                'admin'  => 'Admin',
                                'user'   => 'User',
                            ];
                        }

                        if ($role === 'admin') {
                            // Admins may only create regular users
                            return [
                                'user' => 'User',
                            ];
                        }

                        return [];
                    })
                    ->default('user')
                    ->required()
                    ->rules(['in:master,admin,user'])
                    ->disabled(fn ($record) => $record && auth()->user()?->role !== 'master')
                    ->visible(fn () => in_array(auth()->user()?->role ?? null, ['master', 'admin'])),
                Forms\Components\Select::make('role_id')
                    ->label('Organization / Group')
                    ->relationship('roleRelation', 'label')
                    ->options(function () {
                        // Tampilkan hanya role \"toko\" yang dibuat master, 
                        // abaikan role bawaan master/admin/user jika ada.
                        return \App\Models\Role::query()
                            ->whereNotIn('name', ['master', 'admin', 'user'])
                            ->orderBy('label')
                            ->pluck('label', 'id');
                    })
                    ->searchable()
                    ->preload()
                    ->helperText('Select an organization/group to group users (e.g., Client A).')
                    ->visible(fn () => auth()->user()?->role === 'master')
                    ->nullable(),
                Forms\Components\TextInput::make('password')
                    ->label('Password')
                    ->password()
                    ->required(fn ($livewire) => $livewire instanceof \App\Filament\Resources\UserResource\Pages\CreateUser)
                    ->dehydrated(fn ($livewire) => $livewire instanceof \App\Filament\Resources\UserResource\Pages\CreateUser || filled($livewire->data['password'] ?? null))
                    ->maxLength(255)
                    ->helperText(fn ($livewire) => $livewire instanceof \App\Filament\Resources\UserResource\Pages\EditUser ? 'Leave empty if you do not want to change the password.' : ''),
                Forms\Components\TextInput::make('limit_short')
                    ->label('Short')
                    ->required()
                    ->default(fn () => auth()->user()?->role === 'admin' ? '1' : '0')
                    ->placeholder('0')
                    ->formatStateUsing(fn ($state) => $state === null ? '!' : (string) $state)
                    ->dehydrateStateUsing(function ($state) {
                        $state = is_string($state) ? trim($state) : $state;
                        if ($state === '!') {
                            return null; // Unlimited
                        }
                        return (int) $state;
                    })
                    ->rules(function () {
                        $role = auth()->user()?->role;

                        if ($role === 'admin') {
                            return [
                                function (string $attribute, $value, \Closure $fail): void {
                                    $value = is_string($value) ? trim($value) : $value;
                                    if ((string) $value !== '1') {
                                        $fail('For Admin, this value is fixed to 1.');
                                    }
                                },
                            ];
                        }

                        return [
                            function (string $attribute, $value, \Closure $fail): void {
                                $value = is_string($value) ? trim($value) : $value;
                                if ($value === '!') {
                                    return; // Unlimited
                                }
                                if ($value === '' || $value === null) {
                                    $fail('This field is required. Use "!" for Unlimited or enter a number (0+).');
                                    return;
                                }
                                if (!ctype_digit((string) $value)) {
                                    $fail('Enter a non-negative integer (0+) or "!".');
                                }
                            },
                        ];
                    })
                    ->helperText('Use "!" for Unlimited. Use 0 for zero allowed. Admin: fixed at 1.')
                    ->visible(fn () => in_array(auth()->user()?->role ?? null, ['master', 'admin'])),
                Forms\Components\TextInput::make('limit_domain')
                    ->label('Domain Target')
                    ->required()
                    ->default(fn () => auth()->user()?->role === 'admin' ? '1' : '0')
                    ->placeholder('0')
                    ->formatStateUsing(fn ($state) => $state === null ? '!' : (string) $state)
                    ->dehydrateStateUsing(function ($state) {
                        $state = is_string($state) ? trim($state) : $state;
                        if ($state === '!') {
                            return null; // Unlimited
                        }
                        return (int) $state;
                    })
                    ->rules(function () {
                        $role = auth()->user()?->role;

                        if ($role === 'admin') {
                            return [
                                function (string $attribute, $value, \Closure $fail): void {
                                    $value = is_string($value) ? trim($value) : $value;
                                    if (!ctype_digit((string) $value)) {
                                        $fail('Enter an integer between 1 and 10.');
                                        return;
                                    }
                                    $int = (int) $value;
                                    if ($int < 1 || $int > 10) {
                                        $fail('Enter an integer between 1 and 10.');
                                    }
                                },
                            ];
                        }

                        return [
                            function (string $attribute, $value, \Closure $fail): void {
                                $value = is_string($value) ? trim($value) : $value;
                                if ($value === '!') {
                                    return; // Unlimited
                                }
                                if ($value === '' || $value === null) {
                                    $fail('This field is required. Use "!" for Unlimited or enter a number (0+).');
                                    return;
                                }
                                if (!ctype_digit((string) $value)) {
                                    $fail('Enter a non-negative integer (0+) or "!".');
                                }
                            },
                        ];
                    })
                    ->helperText('Use "!" for Unlimited. Use 0 for zero allowed. Admin: 1–10.')
                    ->visible(fn () => in_array(auth()->user()?->role ?? null, ['master', 'admin'])),
                Forms\Components\TextInput::make('limit_domain_check')
                    ->label('Domain Check')
                    ->required()
                    ->default(fn () => auth()->user()?->role === 'admin' ? '1' : '0')
                    ->placeholder('0')
                    ->formatStateUsing(fn ($state) => $state === null ? '!' : (string) $state)
                    ->dehydrateStateUsing(function ($state) {
                        $state = is_string($state) ? trim($state) : $state;
                        if ($state === '!') {
                            return null; // Unlimited
                        }
                        return (int) $state;
                    })
                    ->rules(function () {
                        $role = auth()->user()?->role;

                        if ($role === 'admin') {
                            return [
                                function (string $attribute, $value, \Closure $fail): void {
                                    $value = is_string($value) ? trim($value) : $value;
                                    if (!ctype_digit((string) $value)) {
                                        $fail('Enter an integer between 1 and 10.');
                                        return;
                                    }
                                    $int = (int) $value;
                                    if ($int < 1 || $int > 10) {
                                        $fail('Enter an integer between 1 and 10.');
                                    }
                                },
                            ];
                        }

                        return [
                            function (string $attribute, $value, \Closure $fail): void {
                                $value = is_string($value) ? trim($value) : $value;
                                if ($value === '!') {
                                    return; // Unlimited
                                }
                                if ($value === '' || $value === null) {
                                    $fail('This field is required. Use "!" for Unlimited or enter a number (0+).');
                                    return;
                                }
                                if (!ctype_digit((string) $value)) {
                                    $fail('Enter a non-negative integer (0+) or "!".');
                                }
                            },
                        ];
                    })
                    ->helperText('Use "!" for Unlimited. Use 0 for zero allowed. Admin: 1–10.')
                    ->visible(fn () => in_array(auth()->user()?->role ?? null, ['master', 'admin'])),
                Forms\Components\Toggle::make('telegram_enabled')
                    ->label('Telegram Notification')
                    ->reactive()
                    ->inline(false)
                    ->helperText('Enable to receive notifications via Telegram.'),
                Forms\Components\TextInput::make('telegram_chat_id')
                    ->label('Telegram ID')
                    ->maxLength(100)
                    ->reactive()
                    ->visible(fn ($get) => $get('telegram_enabled') === true)
                    ->required(fn ($get) => $get('telegram_enabled') === true)
                    ->helperText('Required if Telegram notifications are enabled.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('User')
                    ->description(fn ($record): string => (string) ($record->email ?? ''))
                    ->searchable(['name', 'email'])
                    ->sortable()
                    ->icon('heroicon-o-user')
                    ->wrap(),
                Tables\Columns\TextColumn::make('role')
                    ->label('Role')
                    ->formatStateUsing(function ($state) {
                        return match ($state) {
                            'master' => 'Master',
                            'admin'  => 'Admin',
                            'user'   => 'User',
                            default  => ucfirst((string) $state),
                        };
                    })
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'master' => 'danger',
                        'admin'  => 'warning',
                        'user'   => 'success',
                        default  => 'gray',
                    })
                    ->icon(fn ($state) => match($state) {
                        'master' => 'heroicon-o-shield-exclamation',
                        'admin'  => 'heroicon-o-shield-check',
                        'user'   => 'heroicon-o-user',
                        default  => null,
                    })
                    ->iconPosition('before')
                    ->sortable(),
                Tables\Columns\TextColumn::make('roleRelation.label')
                    ->label('Organization / Group')
                    ->state(function ($record): string {
                        $org = $record->roleRelation;
                        return (string) ($org?->label ?: ($org?->name ?: '-'));
                    })
                    ->badge()
                    ->color(fn ($record) => $record->roleRelation ? 'info' : 'gray')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('limit_short')
                    ->label('Short')
                    ->state(fn ($record): string => $record->limit_short === null ? 'Unlimited' : number_format((int) $record->limit_short))
                    ->badge()
                    ->color(fn ($record) => $record->limit_short === null ? 'success' : (((int) $record->limit_short) === 0 ? 'danger' : 'info'))
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('limit_domain')
                    ->label('Domain Target')
                    ->state(fn ($record): string => $record->limit_domain === null ? 'Unlimited' : number_format((int) $record->limit_domain))
                    ->badge()
                    ->color(fn ($record) => $record->limit_domain === null ? 'success' : (((int) $record->limit_domain) === 0 ? 'danger' : 'info'))
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('limit_domain_check')
                    ->label('Domain Check')
                    ->state(fn ($record): string => $record->limit_domain_check === null ? 'Unlimited' : number_format((int) $record->limit_domain_check))
                    ->badge()
                    ->color(fn ($record) => $record->limit_domain_check === null ? 'success' : (((int) $record->limit_domain_check) === 0 ? 'danger' : 'info'))
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('email_verified_at')
                    ->label('Email Verified At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                Tables\Filters\SelectFilter::make('role')
                    ->label('Role')
                    ->options([
                        'user'   => 'User',
                        'admin'  => 'Admin',
                        'master' => 'Master',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->icon('heroicon-o-pencil-square')
                    ->label('Edit'),
                Tables\Actions\DeleteAction::make()
                    ->icon('heroicon-o-trash')
                    ->label('Delete')
                    ->visible(fn () => auth()->user()?->role === 'master'),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
