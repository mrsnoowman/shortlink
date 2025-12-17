<?php

namespace App\Filament\Resources\AliasResource\Pages;

use App\Filament\Resources\AliasResource;
use App\Models\Alias;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Str;

class ListAliases extends ListRecords
{
    protected static string $resource = AliasResource::class;

    public function mount(): void
    {
        parent::mount();

        // Auto-create alias if none exists yet
        $user = auth()->user();
        if ($user && $user->role === 'master') {
            // Check whether an alias already exists
            $existingAlias = Alias::where('user_id', $user->id)->first();

            if (!$existingAlias) {
                // Get domain from APP_URL
                $appUrl = config('app.url', '');
                $customDomain = null;

                if (!empty($appUrl)) {
                    $host = parse_url($appUrl, PHP_URL_HOST) ?: $appUrl;
                    $customDomain = (string) Str::of($host)->trim('/');
                }

                // If a domain was obtained, create the alias automatically
                if ($customDomain) {
                    // Ensure the domain is not already used by another user
                    $domainExists = Alias::where('custom_domain', $customDomain)->exists();

                    if (!$domainExists) {
                        Alias::create([
                            'user_id' => $user->id,
                            'custom_domain' => $customDomain,
                            'fallback_url' => null,
                        ]);
                    }
                }
            }
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->icon('heroicon-o-plus-circle')
                ->label('New Alias'),
        ];
    }
}

