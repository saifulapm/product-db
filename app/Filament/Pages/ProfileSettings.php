<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Forms;
use Filament\Actions\Action;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileSettings extends Page
{
    use InteractsWithFormActions;
    
    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    protected static ?string $title = 'Profile Settings';
    
    protected static string $view = 'filament.pages.profile-settings';

    protected static string $routePath = 'profile-settings';
    
    protected static bool $shouldRegisterNavigation = false;

    public ?array $data = [];

    public function mount(): void
    {
        $this->fillForm();
    }

    protected function fillForm(): void
    {
        $user = Auth::user();
        
        // Fill form with user data
        $this->form->fill([
            'name' => $user->name ?? '',
            'email' => $user->email ?? '',
            'first_name' => $user->first_name ?? '',
            'last_name' => $user->last_name ?? '',
            'phone' => $user->phone ?? '',
        ]);
    }

    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->statePath('data'),
            ),
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Personal Information')
                    ->schema([
                        Forms\Components\TextInput::make('first_name')
                            ->label('First Name')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('last_name')
                            ->label('Last Name')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('name')
                            ->label('Display Name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->label('Phone Number')
                            ->tel()
                            ->maxLength(255)
                            ->placeholder('e.g., +15551234567')
                            ->helperText('Phone number for SMS reminders (E.164 format)'),
                    ])->columns(2),
                
                Forms\Components\Section::make('Change Password')
                    ->description('Leave blank to keep your current password')
                    ->schema([
                        Forms\Components\TextInput::make('current_password')
                            ->label('Current Password')
                            ->password()
                            ->revealable()
                            ->required(fn (Forms\Get $get) => !empty($get('new_password')) || !empty($get('new_password_confirmation')))
                            ->currentPassword(),
                        Forms\Components\TextInput::make('new_password')
                            ->label('New Password')
                            ->password()
                            ->revealable()
                            ->minLength(8)
                            ->required(fn (Forms\Get $get) => !empty($get('current_password')) || !empty($get('new_password_confirmation'))),
                        Forms\Components\TextInput::make('new_password_confirmation')
                            ->label('Confirm New Password')
                            ->password()
                            ->revealable()
                            ->same('new_password')
                            ->required(fn (Forms\Get $get) => !empty($get('current_password')) || !empty($get('new_password'))),
                    ])->columns(2)
                    ->collapsible()
                    ->collapsed(false),
            ]);
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
        ];
    }

    protected function getSaveFormAction(): Action
    {
        return Action::make('save')
            ->label('Save Changes')
            ->submit('save');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $user = Auth::user();

        // Update basic info
        $updateData = [
            'name' => $data['name'],
            'email' => $data['email'],
            'first_name' => $data['first_name'] ?? null,
            'last_name' => $data['last_name'] ?? null,
            'phone' => $data['phone'] ?? null,
        ];
        
        $user->update($updateData);

        // Update password if provided
        if (!empty($data['new_password'])) {
            $user->password = Hash::make($data['new_password']);
            $user->save();
        }

        // Refresh user data and refill form
        $user->refresh();
        $this->fillForm();

        Notification::make()
            ->title('Profile updated successfully!')
            ->success()
            ->send();
    }

    public function getTitle(): string
    {
        return 'Profile Settings';
    }
}
