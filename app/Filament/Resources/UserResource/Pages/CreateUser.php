<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Filament\Notifications\Notification;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Generate a temporary password if none provided
        if (empty($data['password'])) {
            $tempPassword = Str::random(8);
            $data['password'] = $tempPassword;
            
            // Store temp password to show in notification
            session(['temp_password' => $tempPassword]);
        }
        
        // Hash the password
        $data['password'] = Hash::make($data['password']);
        
        // Set account as needing setup
        $data['account_is_set'] = false;
        
        return $data;
    }

    protected function afterCreate(): void
    {
        $tempPassword = session('temp_password');
        
        if ($tempPassword) {
            Notification::make()
                ->title('User Created Successfully')
                ->body("Temporary password: {$tempPassword}")
                ->success()
                ->persistent()
                ->send();
                
            // Clear the session
            session()->forget('temp_password');
        }
    }
}