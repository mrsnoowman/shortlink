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

        // Auto-create alias jika belum ada
        $user = auth()->user();
        if ($user && $user->role === 'master') {
            // Cek apakah sudah ada alias
            $existingAlias = Alias::where('user_id', $user->id)->first();

            if (!$existingAlias) {
                // Ambil domain dari APP_URL
                $appUrl = config('app.url', '');
                $customDomain = null;

                if (!empty($appUrl)) {
                    $host = parse_url($appUrl, PHP_URL_HOST) ?: $appUrl;
                    $customDomain = (string) Str::of($host)->trim('/');
                }

                // Jika berhasil dapat domain, buat alias otomatis
                if ($customDomain) {
                    // Cek apakah domain sudah digunakan oleh user lain
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

