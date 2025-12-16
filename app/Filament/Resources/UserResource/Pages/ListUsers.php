<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->icon('heroicon-o-plus-circle')
                ->label('Create User')
                // Master dan admin organisasi boleh membuat user baru
                ->visible(fn () => in_array(auth()->user()?->role ?? null, ['master', 'admin'])),
        ];
    }
}
