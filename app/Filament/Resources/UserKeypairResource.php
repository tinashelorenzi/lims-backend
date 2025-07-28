<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserKeypairResource\Pages;
use App\Models\UserKeypair;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class UserKeypairResource extends Resource
{
    protected static ?string $model = UserKeypair::class;

    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationGroup = 'Security';

    protected static ?string $navigationLabel = 'User Keypairs';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Keypair Information')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('User')
                            ->relationship('user', 'email')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->disabled(fn (string $operation) => $operation === 'edit'),
                        
                        Forms\Components\Textarea::make('public_key')
                            ->label('Public Key')
                            ->rows(8)
                            ->disabled(),
                        
                        Forms\Components\Textarea::make('private_key')
                            ->label('Private Key (Original)')
                            ->rows(8)
                            ->disabled()
                            ->visible(false), // Hidden for security
                        
                        Forms\Components\Textarea::make('private_key_stretched')
                            ->label('Private Key (Stretched)')
                            ->rows(6)
                            ->disabled()
                            ->visible(false) // Hidden for security
                            ->helperText('Key stretched using PBKDF2 with environment-specific salt'),
                        
                        Forms\Components\TextInput::make('key_algorithm')
                            ->label('Algorithm')
                            ->disabled(),
                        
                        Forms\Components\DateTimePicker::make('generated_at')
                            ->label('Generated At')
                            ->disabled(),
                        
                        Forms\Components\DateTimePicker::make('expires_at')
                            ->label('Expires At')
                            ->nullable(),
                        
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->disabled(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.email')
                    ->label('User')
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('user.first_name')
                    ->label('First Name')
                    ->searchable(),
           
                TextColumn::make('user.last_name')
                    ->label('Last Name')
                    ->searchable(),
                
                TextColumn::make('key_algorithm')
                    ->label('Algorithm')
                    ->sortable(),
                
                BooleanColumn::make('is_active')
                    ->label('Active')
                    ->sortable(),
                
                IconColumn::make('has_stretching')
                    ->label('Key Stretched')
                    ->boolean()
                    ->getStateUsing(fn (UserKeypair $record): bool => !empty($record->private_key_stretched))
                    ->trueIcon('heroicon-o-shield-check')
                    ->falseIcon('heroicon-o-shield-exclamation')
                    ->trueColor('success')
                    ->falseColor('warning'),
                
                TextColumn::make('generated_at')
                    ->label('Generated')
                    ->dateTime()
                    ->sortable(),
                
                TextColumn::make('expires_at')
                    ->label('Expires')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Never'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueLabel('Active')
                    ->falseLabel('Inactive'),
                
                Tables\Filters\TernaryFilter::make('has_stretching')
                    ->label('Key Stretching')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('private_key_stretched'),
                        false: fn (Builder $query) => $query->whereNull('private_key_stretched'),
                    )
                    ->trueLabel('With stretching')
                    ->falseLabel('Without stretching'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                
                Action::make('regenerate')
                    ->label('Regenerate')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Regenerate Keypair')
                    ->modalDescription('This will generate a new keypair with key stretching and deactivate the current one. Are you sure?')
                    ->action(function (UserKeypair $record) {
                        try {
                            $newKeypair = $record->user->generateKeypair();
                            
                            Notification::make()
                                ->title('Keypair regenerated successfully')
                                ->body('New keypair generated with key stretching')
                                ->success()
                                ->send();
                                
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Failed to regenerate keypair')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                
                Action::make('verify_stretching')
                    ->label('Verify Stretching')
                    ->icon('heroicon-o-shield-check')
                    ->color('info')
                    ->visible(fn (UserKeypair $record): bool => !empty($record->private_key_stretched))
                    ->action(function (UserKeypair $record) {
                        try {
                            $isValid = $record->verifyKeyStretching();
                            
                            if ($isValid) {
                                Notification::make()
                                    ->title('Key stretching verified')
                                    ->body('The key stretching integrity is valid')
                                    ->success()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('Key stretching verification failed')
                                    ->body('The key stretching integrity check failed')
                                    ->danger()
                                    ->send();
                            }
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Verification error')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                
                Action::make('deactivate')
                    ->label('Deactivate')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (UserKeypair $record) => $record->is_active)
                    ->action(function (UserKeypair $record) {
                        $record->update(['is_active' => false]);
                        
                        Notification::make()
                            ->title('Keypair deactivated')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    
                    Tables\Actions\BulkAction::make('regenerate_with_stretching')
                        ->label('Regenerate with Stretching')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Regenerate Keypairs with Key Stretching')
                        ->modalDescription('This will regenerate all selected keypairs with key stretching enabled.')
                        ->action(function ($records) {
                            $successCount = 0;
                            $failureCount = 0;
                            
                            foreach ($records as $keypair) {
                                try {
                                    $keypair->user->generateKeypair();
                                    $successCount++;
                                } catch (\Exception $e) {
                                    $failureCount++;
                                }
                            }
                            
                            if ($successCount > 0) {
                                Notification::make()
                                    ->title('Keypairs regenerated')
                                    ->body("Successfully regenerated {$successCount} keypair(s) with key stretching." . 
                                           ($failureCount > 0 ? " Failed for {$failureCount} keypair(s)." : ""))
                                    ->success()
                                    ->send();
                            }
                            
                            if ($failureCount > 0 && $successCount === 0) {
                                Notification::make()
                                    ->title('Failed to regenerate keypairs')
                                    ->body("Failed to regenerate {$failureCount} keypair(s)")
                                    ->danger()
                                    ->send();
                            }
                        }),
                ]),
            ])
            ->defaultSort('generated_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUserKeypairs::route('/'),
            'create' => Pages\CreateUserKeypair::route('/create'),
            'view' => Pages\ViewUserKeypair::route('/{record}'),
            'edit' => Pages\EditUserKeypair::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['user']);
    }
}