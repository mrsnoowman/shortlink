<?php

namespace App\Filament\Resources\ShortlinkResource\RelationManagers;

use App\Models\TargetUrl;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Validation\ValidationException;

class TargetUrlsRelationManager extends RelationManager
{
    protected static string $relationship = 'targetUrls';

    protected static ?string $title = 'Target URLs';

    protected static ?string $modelLabel = 'Target URL';

    protected static ?string $pluralModelLabel = 'Target URLs';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('url')
                    ->label('URL')
                    ->required()
                    ->url()
                    ->maxLength(500)
                    ->placeholder('https://www.google.com/?q=example')
                    ->columnSpanFull(),
                Forms\Components\Toggle::make('is_primary')
                    ->label('Primary Target')
                    ->helperText('Primary target will be used for redirect. If blocked, system will automatically switch to another active target.')
                    ->default(false)
                    ->afterStateUpdated(function ($state, $set, $get, $record) {
                        // If setting as primary, unset other primary targets for this shortlink
                        if ($state && $record) {
                            TargetUrl::where('shortlink_id', $record->shortlink_id)
                                ->where('id', '!=', $record->id)
                                ->update(['is_primary' => false]);
                        }
                    }),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('url')
            ->columns([
                Tables\Columns\TextColumn::make('url')
                    ->label('URL')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->limit(80),
                Tables\Columns\IconColumn::make('is_primary')
                    ->label('Primary')
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon(null)
                    ->trueColor('warning')
                    ->sortable(),
                Tables\Columns\TextColumn::make('is_blocked')
                    ->label('Status')
                    ->formatStateUsing(function ($state) {
                        if ($state) {
                            return 'Blocked';
                        }
                        return 'Active';
                    })
                    ->badge()
                    ->color(fn ($state) => $state ? 'danger' : 'success')
                    ->icon(fn ($state) => $state ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->iconPosition('before')
                    ->sortable(),
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
                Tables\Filters\TernaryFilter::make('is_blocked')
                    ->label('Status')
                    ->placeholder('All')
                    ->trueLabel('Blocked')
                    ->falseLabel('Active')
                    ->queries(
                        true: fn (Builder $query) => $query->where('is_blocked', true),
                        false: fn (Builder $query) => $query->where('is_blocked', false),
                        blank: fn (Builder $query) => $query,
                    ),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->icon('heroicon-o-plus-circle')
                    ->label('Add Target URL')
                    ->mutateFormDataUsing(function (array $data): array {
                        $user = $this->ownerRecord->user;

                        if ($user) {
                            $currentDomains = TargetUrl::whereHas('shortlink', function ($query) use ($user) {
                                $query->where('user_id', $user->id);
                            })->count();

                            $domainLimit = $user->limit_domain ?? 0;
                            if ($domainLimit > 0 && ($currentDomains + 1) > $domainLimit) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Domain limit reached')
                                    ->body('This user has already reached the domain limit.')
                                    ->danger()
                                    ->send();

                                throw ValidationException::withMessages([
                                    'url' => 'Domain limit for this user has been reached.',
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
                                    ->body('This URL already exists for this user.')
                                    ->danger()
                                    ->send();

                                throw ValidationException::withMessages([
                                    'url' => 'This URL is already registered for this user.',
                                ]);
                            }
                        }

                        // Don't set is_primary here, do it in afterCreate to ensure record exists
                        return $data;
                    })
                    ->after(function (TargetUrl $record) {
                        // If setting as primary, unset other primary targets
                        if ($record->is_primary) {
                            TargetUrl::where('shortlink_id', $record->shortlink_id)
                                ->where('id', '!=', $record->id)
                                ->update(['is_primary' => false]);
                        } else {
                            // If no primary exists, set this as primary
                            $hasPrimary = TargetUrl::where('shortlink_id', $record->shortlink_id)
                                ->where('is_primary', true)
                                ->where('id', '!=', $record->id)
                                ->exists();
                            
                            if (!$hasPrimary) {
                                $record->update(['is_primary' => true]);
                            }
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('set_primary')
                    ->label('Set as Primary')
                    ->icon('heroicon-o-star')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function (TargetUrl $record) {
                        // Unset all other primary targets for this shortlink
                        TargetUrl::where('shortlink_id', $record->shortlink_id)
                            ->where('id', '!=', $record->id)
                            ->update(['is_primary' => false]);
                        
                        // Set this as primary
                        $record->update(['is_primary' => true]);
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Primary target updated')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (TargetUrl $record) => !$record->is_primary),
                Tables\Actions\DeleteAction::make()
                    ->icon('heroicon-o-trash')
                    ->label('Delete')
                    ->before(function (TargetUrl $record) {
                        // If deleting primary, set first remaining target as primary
                        if ($record->is_primary) {
                            $newPrimary = TargetUrl::where('shortlink_id', $record->shortlink_id)
                                ->where('id', '!=', $record->id)
                                ->orderBy('id', 'asc')
                                ->first();
                            
                            if ($newPrimary) {
                                $newPrimary->update(['is_primary' => true]);
                            }
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->icon('heroicon-o-trash'),
                ]),
            ])
            ->defaultSort('id', 'asc');
    }
}
