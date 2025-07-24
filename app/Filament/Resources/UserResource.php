<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'User Management';

    protected static ?string $navigationLabel = 'Users';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Personal Information')
                    ->schema([
                        Forms\Components\TextInput::make('first_name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true),
                        
                        Forms\Components\TextInput::make('last_name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Contact Information')
                    ->schema([
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        
                        Forms\Components\TextInput::make('phone_number')
                            ->tel()
                            ->maxLength(255)
                            ->placeholder('(555) 123-4567'),
                        
                        Forms\Components\DatePicker::make('date_hired')
                            ->label('Date Hired')
                            ->default(now())
                            ->displayFormat('M d, Y'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Account Settings')
                    ->schema([
                        Forms\Components\Select::make('user_type')
                            ->label('User Role')
                            ->options(User::USER_TYPES)
                            ->required()
                            ->default('lab_technician')
                            ->selectablePlaceholder(false),
                        
                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create')
                            ->minLength(8)
                            ->same('password_confirmation')
                            ->revealable()
                            ->helperText('Leave blank to keep current password (when editing)'),
                   
                        Forms\Components\TextInput::make('password_confirmation')
                            ->password()
                            ->dehydrated(false)
                            ->required(fn (string $context): bool => $context === 'create')
                            ->minLength(8)
                            ->revealable(),
                        
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active Account')
                            ->default(true)
                            ->helperText('Inactive users cannot log in to the system'),
                        
                        Forms\Components\Toggle::make('account_is_set')
                            ->label('Account Setup Complete')
                            ->helperText('Whether the user has completed their initial password setup')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('System Information')
                    ->schema([
                        Forms\Components\Placeholder::make('created_at')
                            ->label('Created Date')
                            ->content(fn (User $record): ?string => $record->created_at?->diffForHumans()),
                        
                        Forms\Components\Placeholder::make('last_login_at')
                            ->label('Last Login')
                            ->content(fn (User $record): string => $record->last_login_at?->diffForHumans() ?? 'Never'),
                    ])
                    ->columns(2)
                    ->visibleOn('edit'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('session_status')
    ->label('Status')
    ->formatStateUsing(fn (string $state): string => User::SESSION_STATUSES[$state] ?? ucfirst($state))
    ->badge()
    ->color(fn (string $state): string => match ($state) {
        'online' => 'success',
        'away' => 'warning', 
        'offline' => 'gray',
        default => 'gray',
    })
    ->sortable(),

Tables\Columns\TextColumn::make('last_heartbeat_at')
    ->label('Last Seen')
    ->dateTime('M d, Y H:i')
    ->sortable()
    ->toggleable()
    ->placeholder('Never'),
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Name')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(['first_name', 'last_name'])
                    ->description(fn (User $record): string => "Hired: " . ($record->date_hired?->format('M d, Y') ?? 'N/A')),

                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Email copied to clipboard'),

                Tables\Columns\TextColumn::make('phone_number')
                    ->label('Phone')
                    ->toggleable()
                    ->placeholder('No phone'),

                Tables\Columns\TextColumn::make('user_type')
                    ->label('Role')
                    ->formatStateUsing(fn (string $state): string => User::USER_TYPES[$state] ?? ucfirst($state))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'admin' => 'danger',
                        'manager' => 'success',
                        'supervisor' => 'warning',
                        'lab_technician', 'quality_control', 'researcher' => 'info',
                        default => 'secondary',
                    }),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('account_is_set')
                    ->label('Setup')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Complete' : 'Pending')
                    ->badge()
                    ->color(fn (bool $state): string => $state ? 'success' : 'warning'),

                Tables\Columns\TextColumn::make('last_login_at')
                    ->label('Last Login')
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->toggleable()
                    ->placeholder('Never'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('user_type')
                    ->label('Role')
                    ->options(User::USER_TYPES),
                
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Account Status')
                    ->trueLabel('Active Only')
                    ->falseLabel('Inactive Only')
                    ->native(false),
                
                Tables\Filters\TernaryFilter::make('account_is_set')
                    ->label('Setup Status')
                    ->trueLabel('Setup Complete')
                    ->falseLabel('Needs Setup')
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                
                Tables\Actions\Action::make('reset_password')
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->action(function (User $record) {
                        $tempPassword = Str::random(8);
                        $record->update([
                            'password' => Hash::make($tempPassword),
                            'account_is_set' => false,
                        ]);
                        
                        // You could send this via email in a real application
                        \Filament\Notifications\Notification::make()
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
                
                Tables\Actions\Action::make('toggle_status')
                    ->icon(fn (User $record) => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn (User $record) => $record->is_active ? 'danger' : 'success')
                    ->action(fn (User $record) => $record->update(['is_active' => !$record->is_active]))
                    ->requiresConfirmation()
                    ->label(fn (User $record) => $record->is_active ? 'Deactivate' : 'Activate'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(fn ($records) => $records->each->update(['is_active' => true]))
                        ->requiresConfirmation(),
                    
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate Selected')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(fn ($records) => $records->each->update(['is_active' => false]))
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return static::getModel()::count() > 10 ? 'warning' : 'primary';
    }
}