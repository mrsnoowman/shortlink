<?php

namespace App\Filament\Resources\DomainCheckResource\Pages;

use App\Filament\Resources\DomainCheckResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDomainCheck extends EditRecord
{
    protected static string $resource = DomainCheckResource::class;

    public function mount(int | string $record): void
    {
        parent::mount($record);

        // Only master can access edit page
        if (DomainCheckResource::currentRole() !== 'master') {
            abort(403, 'You do not have permission to edit domain checks.');
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

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // If user is changed, re-validate limit for the target user
        if (!empty($data['user_id'])) {
            DomainCheckResource::validateDomainCheckLimit($data['user_id']);
        }

        // Prevent duplicate domain per user (excluding current record)
        $userId = $data['user_id'] ?? $this->record->user_id;
        $exists = \App\Models\DomainCheck::where('user_id', $userId)
            ->where('domain', $data['domain'] ?? $this->record->domain)
            ->where('id', '!=', $this->record->id)
            ->exists();

        if ($exists) {
            \Filament\Notifications\Notification::make()
                ->title('Domain sudah terdaftar')
                ->body('Domain tersebut sudah ada untuk user ini.')
                ->danger()
                ->send();

            throw \Illuminate\Validation\ValidationException::withMessages([
                'domain' => 'Domain ini sudah terdaftar untuk user.',
            ]);
        }

        return $data;
    }
}

