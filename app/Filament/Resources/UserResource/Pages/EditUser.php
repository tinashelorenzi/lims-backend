<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Filament\Notifications\Notification;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            
            Actions\Action::make('reset_password')
                ->label('Reset Password')
                ->icon('heroicon-o-key')
                ->color('warning')
                ->action(function () {
                    $tempPassword = Str::random(8);
                    $this->record->update([
                        'password' => Hash::make($tempPassword),
                        'account_is_set' => false,
                    ]);
                    
                    Notification::make()
                        ->title('Password Reset Successfully')
                        ->body("New temporary password: {$tempPassword}")
                        ->success()
                        ->persistent()
                        ->send();
                })
                ->requiresConfirmation()
                ->modalHeading('Reset User Password')
                ->modalDescription('This will generate a new temporary password and require the user to set up their account again.')
                ->modalSubmitActionLabel('Reset Password'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Only hash password if it was provided
        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            // Remove password from data if not provided to keep existing password
            unset($data['password']);
        }
        
        return $data;
    }
}