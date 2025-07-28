<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use App\Models\UserKeypair;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
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
                            ->maxLength(255)
                            ->unique(User::class, 'email', ignoreRecord: true),
                        
                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Account Information')
                    ->schema([
                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->maxLength(255)
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->dehydrated(fn ($state) => filled($state))
                            ->visible(fn (string $operation): bool => $operation === 'create'),
                        
                        Forms\Components\TextInput::make('password_confirmation')
                            ->password()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->maxLength(255)
                            ->dehydrated(false)
                            ->visible(fn (string $operation): bool => $operation === 'create'),
                        
                        Forms\Components\Select::make('role')
                            ->options([
                                'admin' => 'Administrator',
                                'user' => 'User',
                                'manager' => 'Manager',
                            ])
                            ->default('user')
                            ->required(),
                        
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('first_name')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('last_name')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('role')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'admin' => 'danger',
                        'manager' => 'warning',
                        'user' => 'success',
                        default => 'gray',
                    }),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                
                Tables\Columns\TextColumn::make('activeKeypair.generated_at')
                    ->label('Keypair Generated')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('No keypair'),
                
                Tables\Columns\IconColumn::make('has_keypair')
                    ->label('Has Keypair')
                    ->boolean()
                    ->getStateUsing(fn (User $record): bool => $record->hasActiveKeypair()),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->options([
                        'admin' => 'Administrator',
                        'user' => 'User',
                        'manager' => 'Manager',
                    ]),
                
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueLabel('Active users')
                    ->falseLabel('Inactive users'),
                
                Tables\Filters\TernaryFilter::make('has_keypair')
                    ->label('Keypair Status')
                    ->queries(
                        true: fn (Builder $query) => $query->whereHas('keypairs', fn ($q) => $q->where('is_active', true)),
                        false: fn (Builder $query) => $query->whereDoesntHave('keypairs', fn ($q) => $q->where('is_active', true)),
                    )
                    ->trueLabel('Has active keypair')
                    ->falseLabel('No active keypair'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                
                Action::make('generate_keypair')
                    ->label('Generate Keypair')
                    ->icon('heroicon-o-key')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Generate New Keypair')
                    ->modalDescription(fn (User $record) => 
                        $record->hasActiveKeypair() 
                            ? 'This will generate a new keypair and deactivate the current one. The user will need to update any systems using the old keypair.'
                            : 'This will generate a new keypair for this user.'
                    )
                    ->modalSubmitActionLabel('Generate Keypair')
                    ->action(function (User $record) {
                        try {
                            $keypair = $record->generateKeypair();
                            
                            Notification::make()
                                ->title('Keypair generated successfully')
                                ->body("Generated {$keypair->key_algorithm} keypair for {$record->first_name} {$record->last_name}")
                                ->success()
                                ->duration(5000)
                                ->send();
                                
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Failed to generate keypair')
                                ->body($e->getMessage())
                                ->danger()
                                ->duration(8000)
                                ->send();
                        }
                    }),
                
                Action::make('view_keypair')
                    ->label('View Keypair')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->visible(fn (User $record): bool => $record->hasActiveKeypair())
                    ->url(fn (User $record): string => 
                        route('filament.admin.resources.user-keypairs.view', [
                            'record' => $record->activeKeypair->first()?->id
                        ])
                    ),
                
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    
                    Tables\Actions\BulkAction::make('generate_keypairs')
                        ->label('Generate Keypairs')
                        ->icon('heroicon-o-key')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Generate Keypairs for Selected Users')
                        ->modalDescription('This will generate new keypairs for all selected users. Any existing active keypairs will be deactivated.')
                        ->action(function ($records) {
                            $successCount = 0;
                            $failureCount = 0;
                            
                            foreach ($records as $user) {
                                try {
                                    $user->generateKeypair();
                                    $successCount++;
                                } catch (\Exception $e) {
                                    $failureCount++;
                                }
                            }
                            
                            if ($successCount > 0) {
                                Notification::make()
                                    ->title('Keypairs generated')
                                    ->body("Successfully generated keypairs for {$successCount} user(s)." . 
                                           ($failureCount > 0 ? " Failed for {$failureCount} user(s)." : ""))
                                    ->success()
                                    ->send();
                            }
                            
                            if ($failureCount > 0 && $successCount === 0) {
                                Notification::make()
                                    ->title('Failed to generate keypairs')
                                    ->body("Failed to generate keypairs for {$failureCount} user(s)")
                                    ->danger()
                                    ->send();
                            }
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['activeKeypair']);
    }
}