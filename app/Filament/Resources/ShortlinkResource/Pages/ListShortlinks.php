<?php

namespace App\Filament\Resources\ShortlinkResource\Pages;

use App\Filament\Resources\ShortlinkResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListShortlinks extends ListRecords
{
    protected static string $resource = ShortlinkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->icon('heroicon-o-plus-circle')
                ->label('Create Shortlink'),
        ];
    }

    protected function getTableQuery(): Builder
    {
        return parent::getTableQuery()->with('targetUrls');
    }
}

