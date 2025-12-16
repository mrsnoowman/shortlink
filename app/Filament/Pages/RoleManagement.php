<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class RoleManagement extends Page
{
    // Hide page entirely (deprecated/disabled)
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationIcon = null;

    protected static string $view = 'filament.pages.role-management';

    public static function canAccess(): bool
    {
        return false;
    }
}

