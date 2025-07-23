<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Filament\Notifications\Notification;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            
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
                ->requiresConfirmation(),
            
            Actions\Action::make('toggle_status')
                ->label(fn () => $this->record->is_active ? 'Deactivate' : 'Activate')
                ->icon(fn () => $this->record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                ->color(fn () => $this->record->is_active ? 'danger' : 'success')
                ->action(function () {
                    $this->record->update(['is_active' => !$this->record->is_active]);
                    
                    $status = $this->record->is_active ? 'activated' : 'deactivated';
                    Notification::make()
                        ->title("User {$status} successfully")
                        ->success()
                        ->send();
                })
                ->requiresConfirmation(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Personal Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('full_name')
                            ->label('Full Name'),
                        Infolists\Components\TextEntry::make('email')
                            ->copyable()
                            ->copyMessage('Email copied to clipboard'),
                        Infolists\Components\TextEntry::make('phone_number')
                            ->placeholder('No phone number'),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Employment Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('user_type')
                            ->label('Role')
                            ->formatStateUsing(fn (string $state): string => \App\Models\User::USER_TYPES[$state] ?? $state)
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'admin' => 'danger',
                                'supervisor' => 'warning',
                                'quality_control' => 'success',
                                'lab_technician' => 'primary',
                                default => 'secondary',
                            }),
                        Infolists\Components\TextEntry::make('date_hired')
                            ->date('M d, Y'),
                        Infolists\Components\TextEntry::make('is_active')
                            ->label('Status')
                            ->formatStateUsing(fn (bool $state): string => $state ? 'Active' : 'Inactive')
                            ->badge()
                            ->color(fn (bool $state): string => $state ? 'success' : 'danger'),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Account Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('account_is_set')
                            ->label('Account Setup')
                            ->formatStateUsing(fn (bool $state): string => $state ? 'Complete' : 'Needs Setup')
                            ->badge()
                            ->color(fn (bool $state): string => $state ? 'success' : 'warning'),
                        Infolists\Components\TextEntry::make('last_login_at')
                            ->label('Last Login')
                            ->dateTime('M d, Y H:i')
                            ->placeholder('Never logged in'),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Account Created')
                            ->dateTime('M d, Y H:i'),
                    ])
                    ->columns(3),
            ]);
    }
}