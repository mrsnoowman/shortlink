<?php

namespace App\Filament\Resources\DomainCheckResource\Pages;

use App\Filament\Resources\DomainCheckResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateDomainCheck extends CreateRecord
{
    protected static string $resource = DomainCheckResource::class;

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

    protected $domainsToCreate = [];
    protected $userId = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $userId = $data['user_id'] ?? auth()->id();
        
        // Non-master must create for themselves
        if (DomainCheckResource::currentRole() !== 'master') {
            $userId = auth()->id();
            $data['user_id'] = $userId;
        }

        // Parse multiple domains from textarea
        $domainsInput = $data['domains'] ?? '';
        $domains = array_filter(array_map('trim', explode("\n", $domainsInput)));
        
        if (empty($domains)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'domains' => 'Please enter at least one domain.',
            ]);
        }

        // Remove empty domains and validate format
        $validDomains = [];
        $invalidDomains = [];
        
        foreach ($domains as $domain) {
            $domain = trim($domain);
            if (empty($domain)) {
                continue;
            }
            
            // Remove http:// or https:// if present
            $domain = preg_replace('#^https?://#', '', $domain);
            // Remove trailing slash
            $domain = rtrim($domain, '/');
            
            if (preg_match('/^([a-zA-Z0-9-]+\.)+[a-zA-Z]{2,}$/', $domain)) {
                $validDomains[] = strtolower($domain);
            } else {
                $invalidDomains[] = $domain;
            }
        }

        if (!empty($invalidDomains)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'domains' => 'Invalid domain format: ' . implode(', ', $invalidDomains),
            ]);
        }

        if (empty($validDomains)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'domains' => 'Please enter at least one valid domain.',
            ]);
        }

        // Remove duplicates
        $validDomains = array_unique($validDomains);

        // Check domain check limit
        $user = \App\Models\User::find($userId);
        if ($user) {
            $limit = $user->limit_domain_check; // null = Unlimited
            if ($limit !== null) {
                $currentChecks = \App\Models\DomainCheck::where('user_id', $userId)->count();
                $newCount = count($validDomains);
                
                if (($currentChecks + $newCount) > $limit) {
                    \Filament\Notifications\Notification::make()
                        ->title('Domain check limit would be exceeded')
                        ->body("You would exceed your domain check limit. Limit: {$limit}, Current: {$currentChecks}, Adding: {$newCount}")
                        ->danger()
                        ->send();

                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'domains' => "Domain check limit would be exceeded. Limit: {$limit}, Current: {$currentChecks}",
                    ]);
                }
            }
        }

        // Check for existing domains
        $existingDomains = \App\Models\DomainCheck::where('user_id', $userId)
            ->whereIn('domain', $validDomains)
            ->pluck('domain')
            ->toArray();

        if (!empty($existingDomains)) {
            \Filament\Notifications\Notification::make()
                ->title('Some domains are already registered')
                ->body('These domains already exist: ' . implode(', ', $existingDomains))
                ->warning()
                ->send();

            // Remove existing domains from list
            $validDomains = array_diff($validDomains, $existingDomains);
            
            if (empty($validDomains)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'domains' => 'All entered domains are already registered.',
                ]);
            }
        }

        // Store domains for bulk creation
        $this->domainsToCreate = $validDomains;
        $this->userId = $userId;

        // Return data for single record (will be overridden in handleRecordCreation)
        return [
            'user_id' => $userId,
            'domain' => $validDomains[0], // First domain as placeholder
            'is_blocked' => false,
        ];
    }

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        // Create multiple domain checks
        $created = [];
        $failed = [];

        foreach ($this->domainsToCreate as $domain) {
            try {
                $domainCheck = \App\Models\DomainCheck::create([
                    'user_id' => $this->userId,
                    'domain' => $domain,
                    'is_blocked' => false,
                ]);
                $created[] = $domain;
            } catch (\Exception $e) {
                $failed[] = $domain;
            }
        }

        // Show notification
        if (!empty($created)) {
            $count = count($created);
            \Filament\Notifications\Notification::make()
                ->title('Domains added successfully')
                ->body("{$count} domain(s) added: " . implode(', ', $created))
                ->success()
                ->send();
        }

        if (!empty($failed)) {
            \Filament\Notifications\Notification::make()
                ->title('Some domains could not be added')
                ->body('Domains: ' . implode(', ', $failed))
                ->warning()
                ->send();
        }

        // Return the first created record for redirect
        $firstRecord = \App\Models\DomainCheck::where('user_id', $this->userId)
            ->whereIn('domain', $created)
            ->first();
            
        if (!$firstRecord) {
            // Fallback: create a dummy record if all failed
            $firstRecord = \App\Models\DomainCheck::create([
                'user_id' => $this->userId,
                'domain' => $this->domainsToCreate[0] ?? 'placeholder.com',
                'is_blocked' => false,
            ]);
        }

        return $firstRecord;
    }

    protected function getRedirectUrl(): string
    {
        // Redirect to index after creating multiple records
        return $this->getResource()::getUrl('index');
    }
}

