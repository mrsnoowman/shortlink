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
                    ->disabled(fn ($record) => $record && auth()->user()?->role !== 'master')
                    ->visible(fn () => in_array(auth()->user()?->role ?? null, ['master', 'admin'])),
                Forms\Components\Select::make('role_id')
                    ->label('Role / Organisasi')
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
                    ->helperText('Pilih organisasi / group untuk mengelompokkan user (misal: Client A).')
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
                    ->label('Limit Short')
                    ->numeric()
                    ->default(fn () => auth()->user()?->role === 'admin' ? 1 : 0)
                    ->rules(function () {
                        $role = auth()->user()?->role;

                        if ($role === 'admin') {
                            // Admin: hanya boleh 1
                            return ['integer', 'in:1'];
                        }

                        // Master: boleh 0 (unlimited) atau nilai lain
                        return ['integer', 'min:0'];
                    })
                    ->helperText('Shortlink limit: Master can set any value (0 = unlimited), Admin: fixed at 1.')
                    ->visible(fn () => in_array(auth()->user()?->role ?? null, ['master', 'admin'])),
                Forms\Components\TextInput::make('limit_domain')
                    ->label('Limit Domain')
                    ->numeric()
                    ->default(fn () => auth()->user()?->role === 'admin' ? 1 : 0)
                    ->rules(function () {
                        $role = auth()->user()?->role;

                        if ($role === 'admin') {
                            // Admin: min 1, max 10
                            return ['integer', 'min:1', 'max:10'];
                        }

                        // Master: boleh 0 (unlimited) atau nilai lain
                        return ['integer', 'min:0'];
                    })
                    ->helperText('Domain limit: Master can set any value (0 = unlimited), Admin: 1–10.')
                    ->visible(fn () => in_array(auth()->user()?->role ?? null, ['master', 'admin'])),
                Forms\Components\TextInput::make('limit_domain_check')
                    ->label('Limit Domain Check')
                    ->numeric()
                    ->default(fn () => auth()->user()?->role === 'admin' ? 1 : 0)
                    ->rules(function () {
                        $role = auth()->user()?->role;

                        if ($role === 'admin') {
                            // Admin: min 1, max 10
                            return ['integer', 'min:1', 'max:10'];
                        }

                        // Master: boleh 0 (unlimited) atau nilai lain
                        return ['integer', 'min:0'];
                    })
                    ->helperText('Domain check limit: Master can set any value (0 = unlimited), Admin: 1–10.')
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
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-user'),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-envelope'),
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
                Tables\Columns\TextColumn::make('limit_short')
                    ->label('Limit Short')
                    ->formatStateUsing(fn ($state) => $state == 0 ? 'Unlimited' : number_format($state))
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('limit_domain')
                    ->label('Limit Domain')
                    ->formatStateUsing(fn ($state) => $state == 0 ? 'Unlimited' : number_format($state))
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('limit_domain_check')
                    ->label('Limit Domain Check')
                    ->formatStateUsing(fn ($state) => $state == 0 ? 'Unlimited' : number_format($state))
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
