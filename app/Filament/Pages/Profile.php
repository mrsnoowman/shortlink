<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Forms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Actions;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Hash;

class Profile extends Page implements HasForms
{
    use InteractsWithForms;
    use InteractsWithFormActions;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    protected static ?string $navigationLabel = 'Profile';

    protected static ?string $title = 'Profile';

    protected static ?string $navigationGroup = 'Account';

    protected static string $view = 'filament.pages.profile';

    public array $data = [];

    public function mount(): void
    {
        $user = auth()->user();
        abort_unless($user, 403);

        $this->form->fill([
            'name' => $user->name,
            'email' => $user->email,
            'telegram_enabled' => (bool) $user->telegram_enabled,
            'telegram_chat_id' => $user->telegram_chat_id,
            'telegram_interval_minutes' => $user->telegram_interval_minutes ?? 5,
        ]);
    }

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\Grid::make(2)->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required()
                    ->maxLength(255),
            ]),
            Forms\Components\Grid::make(2)->schema([
                Forms\Components\TextInput::make('password')
                    ->label('New Password')
                    ->password()
                    ->maxLength(255)
                    ->revealable()
                    ->helperText('Leave empty if you do not want to change the password.'),
                Forms\Components\TextInput::make('password_confirmation')
                    ->label('Confirm Password')
                    ->password()
                    ->maxLength(255)
                    ->revealable()
                    ->same('password')
                    ->helperText('Must be the same as the password.'),
            ]),
            Forms\Components\Section::make('Telegram Notification')
                ->schema([
                Forms\Components\Toggle::make('telegram_enabled')
                        ->label('Enable Telegram Notification')
                    ->reactive()
                        ->inline(false)
                        ->helperText('Receive notifications about domain status changes via Telegram'),
                    Forms\Components\Grid::make(2)->schema([
                Forms\Components\TextInput::make('telegram_chat_id')
                            ->label('Telegram Chat ID')
                    ->maxLength(100)
                    ->reactive()
                    ->visible(fn ($get) => $get('telegram_enabled') === true)
                            ->required(fn ($get) => $get('telegram_enabled') === true)
                            ->helperText('Your Telegram Chat ID (get it from @userinfobot)'),
                        Forms\Components\TextInput::make('telegram_interval_minutes')
                            ->label('Notification Interval (minutes)')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(1440)
                            ->default(5)
                            ->reactive()
                            ->visible(fn ($get) => $get('telegram_enabled') === true)
                            ->required(fn ($get) => $get('telegram_enabled') === true)
                            ->helperText('How often to receive notifications (e.g., 1 = every minute, 5 = every 5 minutes)'),
                    ]),
            ]),
        ];
    }

    protected function getFormModel(): \Illuminate\Database\Eloquent\Model|string|null
    {
        return auth()->user();
    }

    protected function getFormStatePath(): string
    {
        return 'data';
    }

    protected function getFormActions(): array
    {
        return [
            Actions\Action::make('submit')
                ->label('Save')
                ->icon('heroicon-o-check')
                ->submit('submit'),
        ];
    }

    public function submit(): void
    {
        $user = auth()->user();
        abort_unless($user, 403);

        $state = $this->form->getState();

        $user->name = $state['name'];
        $user->email = $state['email'];
        $user->telegram_enabled = (bool) ($state['telegram_enabled'] ?? false);
        $user->telegram_chat_id = $user->telegram_enabled ? ($state['telegram_chat_id'] ?? null) : null;
        $user->telegram_interval_minutes = $user->telegram_enabled ? ($state['telegram_interval_minutes'] ?? 5) : 5;

        if (!empty($state['password'])) {
            $user->password = Hash::make($state['password']);
        }

        $user->save();

        Notification::make()
            ->title('Profile updated')
            ->success()
            ->send();
    }
}

