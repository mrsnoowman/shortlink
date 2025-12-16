<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AliasResource\Pages;
use App\Models\Alias;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class AliasResource extends Resource
{
    protected static ?string $model = Alias::class;

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?string $navigationLabel = 'Alias';

    protected static ?string $navigationGroup = 'Settings';

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->role === 'master';
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->role === 'master';
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->role === 'master';
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->role === 'master';
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->role === 'master';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // User tidak perlu dipilih di form; otomatis set ke user yang sedang login (master)
                Forms\Components\Hidden::make('user_id')
                    ->default(fn () => auth()->id()),
                Forms\Components\TextInput::make('custom_domain')
                    ->label('Custom Domain')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255)
                    ->default(function () {
                        // Ambil domain dari APP_URL (misal: https://example.com -> example.com)
                        $appUrl = config('app.url', '');

                        if (empty($appUrl)) {
                            return null;
                        }

                        $host = parse_url($appUrl, PHP_URL_HOST) ?: $appUrl;

                        // Bersihkan trailing slash dan pastikan hanya domain
                        $host = Str::of($host)->trim('/');

                        return (string) $host;
                    })
                    ->placeholder('example.com')
                    ->helperText('Custom domain that will be used as the alias.'),
                Forms\Components\TextInput::make('fallback_url')
                    ->label('Fallback URL')
                    ->url()
                    ->maxLength(500)
                    ->placeholder('https://your-fallback-url.com')
                    ->helperText('Fallback URL to use when the target is not available.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('custom_domain')
                    ->label('Custom Domain')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('fallback_url')
                    ->label('Fallback URL')
                    ->searchable()
                    ->sortable()
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->fallback_url),
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
            'index' => Pages\ListAliases::route('/'),
            'create' => Pages\CreateAlias::route('/create'),
            'edit' => Pages\EditAlias::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with('user');
    }
}

