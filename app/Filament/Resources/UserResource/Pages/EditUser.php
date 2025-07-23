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
            
            Actions\DeleteAction::make()
                ->before(function () {
                    // Prevent deletion of the last admin
                    if ($this->record->isAdmin() && \App\Models\User::where('user_type', 'admin')->count() <= 1) {
                        Notification::make()
                            ->title('Cannot delete the last administrator')
                            ->danger()
                            ->send();
                        
                        return false;
                    }
                }),
        ];
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('User updated successfully');
    }
}