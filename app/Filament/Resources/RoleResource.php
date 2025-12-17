<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages;
use App\Models\Role;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    /**
     * NOTE:
     * - `users.role` is the ACCESS LEVEL (fixed: master/admin/user)
     * - `users.role_id` relates to this model (`roles` table) which is used as Organization/Group
     */
    public const RESERVED_ACCESS_LEVELS = ['master', 'admin', 'user'];

    protected static ?string $navigationLabel = 'Organizations / Groups';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $modelLabel = 'Organization / Group';

    protected static ?string $pluralModelLabel = 'Organizations / Groups';

    protected static function currentRole(): ?string
    {
        $role = auth()->user()?->role;
        return $role ? strtolower($role) : null;
    }

    public static function getEloquentQuery(): Builder
    {
        // Show only organization/group records (exclude reserved access-level names).
        return parent::getEloquentQuery()->whereNotIn('name', self::RESERVED_ACCESS_LEVELS);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::currentRole() === 'master';
    }

    public static function canViewAny(): bool
    {
        return static::currentRole() === 'master';
    }

    public static function canCreate(): bool
    {
        return static::currentRole() === 'master';
    }

    public static function canEdit($record): bool
    {
        return static::currentRole() === 'master';
    }

    public static function canDelete($record): bool
    {
        return static::currentRole() === 'master';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Group Code')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->rules([
                        'regex:/^[a-z0-9_-]+$/',
                        'not_in:' . implode(',', self::RESERVED_ACCESS_LEVELS),
                    ])
                    ->maxLength(50)
                    ->helperText('Unique identifier for the organization/group (lowercase, no spaces). Example: client_a, group-1.'),
                Forms\Components\TextInput::make('label')
                    ->label('Group Name')
                    ->maxLength(100)
                    ->helperText('Display name shown in the admin panel (optional).'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Code')
                    ->badge()
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('label')
                    ->label('Name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->icon('heroicon-o-pencil-square')
                    ->label('Edit'),
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
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }
}

