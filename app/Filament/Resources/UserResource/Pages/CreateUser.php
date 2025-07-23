<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Filament\Notifications\Notification;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Generate a temporary password
        $tempPassword = Str::random(8);
        
        $data['password'] = Hash::make($tempPassword);
        $data['account_is_set'] = false;
        
        // Store the temporary password to show in notification
        $this->tempPassword = $tempPassword;
        
        return $data;
    }

    protected function afterCreate(): void
    {
        // Show notification with temporary password
        Notification::make()
            ->title('User created successfully!')
            ->body("Temporary password: {$this->tempPassword}")
            ->success()
            ->persistent()
            ->send();
    }

    protected function getCreatedNotification(): ?Notification
    {
        return null; // We're handling the notification in afterCreate()
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}