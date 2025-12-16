<?php

namespace App\Filament\Resources\AliasResource\Pages;

use App\Filament\Resources\AliasResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateAlias extends CreateRecord
{
    protected static string $resource = AliasResource::class;

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
}

