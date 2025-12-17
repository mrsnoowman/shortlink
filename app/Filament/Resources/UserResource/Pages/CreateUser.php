<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

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

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Rules based on the current logged in user role:
        // - master: can create master/admin/user and choose any organization (role_id).
        // - admin: can only create users with role \"user\" in their own organization.
        $currentUser = auth()->user();
        $currentRole = $currentUser?->role;

        $targetRole = $data['role'] ?? 'user';
        $allowedRoles = ['master', 'admin', 'user'];

        if (! in_array($targetRole, $allowedRoles, true)) {
            throw ValidationException::new()->errors([
                'role' => ['Access level must be one of: Master, Admin, User.'],
            ]);
        }

        if ($currentRole === 'admin' && $targetRole !== 'user') {
            throw ValidationException::new()->errors([
                'role' => ['Admins are only allowed to create users with the \"user\" role.'],
            ]);
        }

        if ($currentRole === 'admin') {
            // Force role & organization for admin-created users
            $data['role'] = 'user';
            $data['role_id'] = $currentUser?->role_id;
        }

        // Hash password before saving
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        return $data;
    }
}
