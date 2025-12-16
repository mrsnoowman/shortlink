<?php

namespace App\Filament\Resources\DomainCheckResource\Pages;

use App\Filament\Resources\DomainCheckResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDomainChecks extends ListRecords
{
    protected static string $resource = DomainCheckResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->icon('heroicon-o-plus-circle')
                ->label('New Domain Check'),
        ];
    }
}

