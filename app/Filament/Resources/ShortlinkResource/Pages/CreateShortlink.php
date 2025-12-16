<?php

namespace App\Filament\Resources\ShortlinkResource\Pages;

use App\Filament\Resources\ShortlinkResource;
use App\Models\Alias;
use App\Models\Shortlink;
use App\Models\TargetUrl;
use App\Models\User;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateShortlink extends CreateRecord
{
    protected static string $resource = ShortlinkResource::class;

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()
                ->icon('heroicon-o-check')
                ->label('Create'),
            $this->getCancelFormAction()
                ->icon('heroicon-o-x-mark')
                ->label('Cancel'),
        ];
    }

    protected $targetUrlsToCreate = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $urls = [];

        // Non-master must create shortlink for themselves
        if (auth()->user()?->role !== 'master') {
            $data['user_id'] = auth()->id();
        }

        // Validate alias ownership and existence
        if (!empty($data['alias_id'])) {
            $alias = Alias::find($data['alias_id']);

            if (!$alias) {
                Notification::make()
                    ->title('Alias not found')
                    ->danger()
                    ->send();

                throw ValidationException::withMessages([
                    'alias_id' => 'Alias not found.',
                ]);
            }

            if (auth()->user()?->role !== 'master' && $alias->user_id !== $data['user_id']) {
                Notification::make()
                    ->title('Alias not owned')
                    ->body('You can only select an alias that belongs to you.')
                    ->danger()
                    ->send();

                throw ValidationException::withMessages([
                    'alias_id' => 'This alias does not belong to you.',
                ]);
            }
        }

        // Pre-parse target URLs so we can validate against user limits
        if (isset($data['target_urls_text']) && !empty($data['target_urls_text'])) {
            $urls = collect(explode("\n", $data['target_urls_text']))
                ->map(fn ($url) => trim($url))
                ->filter()
                ->unique()
                ->values()
                ->toArray();
        }
        // Validate URLs presence and format
        if (empty($urls)) {
            Notification::make()
                ->title('At least one URL is required')
                ->danger()
                ->send();

            throw ValidationException::withMessages([
                'target_urls_text' => 'Please enter at least one URL.',
            ]);
        }
        foreach ($urls as $url) {
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                Notification::make()
                    ->title('Invalid URL')
                    ->body("Invalid URL: {$url}")
                    ->danger()
                    ->send();

                throw ValidationException::withMessages([
                    'target_urls_text' => "Invalid URL: {$url}",
                ]);
            }
        }

        // Prevent duplicate short_code (pre-check for nicer message)
        if (!empty($data['short_code'])) {
            $existsCode = Shortlink::where('short_code', $data['short_code'])->exists();
            if ($existsCode) {
                Notification::make()
                    ->title('Short code already taken')
                    ->body('Please use a different, unique short code.')
                    ->danger()
                    ->send();

                throw ValidationException::withMessages([
                    'short_code' => 'This short code is already in use.',
                ]);
            }
        }

        // Enforce per-user limits for shortlinks and domains
        if (!empty($data['user_id'])) {
            $user = User::find($data['user_id']);

            if ($user) {
                // Shortlink limit check
                $shortLimit = $user->limit_short ?? 0;
                        if ($shortLimit > 0) {
                    $currentShorts = Shortlink::where('user_id', $user->id)->count();

                    if ($currentShorts >= $shortLimit) {
                        Notification::make()
                            ->title('Shortlink limit reached')
                            ->body('This user has already reached the shortlink limit.')
                            ->danger()
                            ->send();

                        throw ValidationException::withMessages([
                            'user_id' => 'Shortlink limit for this user has been reached.',
                        ]);
                    }
                }

                // Domain (target URL) limits
                $domainsToAdd = count($urls);
                if ($domainsToAdd > 0) {
                    $currentDomains = TargetUrl::whereHas('shortlink', function ($query) use ($user) {
                        $query->where('user_id', $user->id);
                    })->count();

                    $domainLimit = $user->limit_domain ?? 0;
                    if ($domainLimit > 0 && ($currentDomains + $domainsToAdd) > $domainLimit) {
                        Notification::make()
                            ->title('Domain limit reached')
                            ->body('This user has already reached the domain limit.')
                            ->danger()
                            ->send();

                        throw ValidationException::withMessages([
                            'target_urls_text' => 'Domain limit for this user has been reached.',
                        ]);
                    }

                    // Prevent duplicate target URL for the same user
                    $existingUrls = TargetUrl::whereHas('shortlink', function ($query) use ($user) {
                        $query->where('user_id', $user->id);
                    })->pluck('url')->toArray();

                    $duplicates = array_intersect($existingUrls, $urls);
                    if (!empty($duplicates)) {
                        Notification::make()
                            ->title('URL already registered')
                            ->body('Some target URLs have already been added: ' . implode(', ', $duplicates))
                            ->danger()
                            ->send();

                        throw ValidationException::withMessages([
                            'target_urls_text' => 'Some URLs have already been added for this user.',
                        ]);
                    }
                }
            } else {
                Notification::make()
                    ->title('User not found')
                    ->danger()
                    ->send();

                $this->halt();
            }
        }

        // Process target_urls_text from textarea
        if (!empty($urls)) {
            // Store URLs for afterCreate to process
            $this->targetUrlsToCreate = $urls;
        }
        
        // Remove the textarea field from data (it's not a database field)
        unset($data['target_urls_text']);
        
        return $data;
    }

    protected function afterCreate(): void
    {
        // Create target URLs from textarea input
        if (isset($this->targetUrlsToCreate) && !empty($this->targetUrlsToCreate)) {
            $isFirst = true;
            foreach ($this->targetUrlsToCreate as $url) {
                $this->record->targetUrls()->create([
                    'url' => $url,
                    'is_blocked' => false,
                    'is_primary' => $isFirst, // Set first target URL as primary
                ]);
                $isFirst = false; // Only first one is primary
            }
        }
        
        // Set first target URL as target_url for backward compatibility
        $firstTargetUrl = $this->record->targetUrls()->first();
        if ($firstTargetUrl) {
            $this->record->update(['target_url' => $firstTargetUrl->url]);
        }
    }
}

