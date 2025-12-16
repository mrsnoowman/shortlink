<?php

namespace App\Filament\Resources\AliasResource\Pages;

use App\Filament\Resources\AliasResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAlias extends EditRecord
{
    protected static string $resource = AliasResource::class;

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
}

