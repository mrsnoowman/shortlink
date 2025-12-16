<?php

namespace App\Filament\Resources\ShortlinkResource\Pages;

use App\Filament\Resources\ShortlinkResource;
use App\Models\Shortlink;
use App\Models\TargetUrl;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditShortlink extends EditRecord
{
    protected static string $resource = ShortlinkResource::class;

    public function mount(int | string $record): void
    {
        parent::mount($record);

        // Only master can access edit page
        if (auth()->user()?->role !== 'master') {
            abort(403, 'You do not have permission to edit shortlinks.');
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->icon('heroicon-o-trash')
                ->label('Delete'),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction()
                ->icon('heroicon-o-check')
                ->label('Save'),
            $this->getCancelFormAction()
                ->icon('heroicon-o-x-mark')
                ->label('Cancel'),
        ];
    }

    protected function afterSave(): void
    {
        // Set first target URL as target_url for backward compatibility
        $firstTargetUrl = $this->record->targetUrls()->first();
        if ($firstTargetUrl) {
            $this->record->update(['target_url' => $firstTargetUrl->url]);
        }
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $isMaster = auth()->user()?->role === 'master';

        // Non-master: lock owner and ensure alias belongs to them
        if (!$isMaster) {
            $data['user_id'] = $this->record->user_id;

            if (!empty($data['alias_id'])) {
                $alias = \App\Models\Alias::find($data['alias_id']);
                if (!$alias || $alias->user_id !== $this->record->user_id) {
                    throw ValidationException::withMessages([
                        'alias_id' => 'This alias does not belong to you.',
                    ]);
                }
            }

            return $data;
        }

        $newUserId = $data['user_id'] ?? null;
        $currentUserId = $this->record->user_id;

        // Master: validate limits on reassignment
        if ($newUserId && $newUserId !== $currentUserId) {
            $user = User::find($newUserId);

            if ($user) {
                $shortLimit = $user->limit_short ?? 0;
                if ($shortLimit > 0) {
                    $currentShorts = Shortlink::where('user_id', $user->id)->count();

                    if (($currentShorts + 1) > $shortLimit) {
                        throw ValidationException::withMessages([
                            'user_id' => 'Shortlink limit for this user has been reached.',
                        ]);
                    }
                }

                $ownedTargetUrls = $this->record->targetUrls()->count();
                $currentDomains = TargetUrl::whereHas('shortlink', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })->count();

                $domainLimit = $user->limit_domain ?? 0;
                if ($domainLimit > 0 && ($currentDomains + $ownedTargetUrls) > $domainLimit) {
                    throw ValidationException::withMessages([
                        'user_id' => 'Domain limit for this user has been reached.',
                    ]);
                }
            }
        }

        return $data;
    }
}

