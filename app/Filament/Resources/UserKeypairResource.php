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
                            ->label('Private Key')
                            ->rows(8)
                            ->disabled()
                            ->visible(false), // Hidden for security
                        
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
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                
                Action::make('regenerate')
                    ->label('Regenerate')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Regenerate Keypair')
                    ->modalDescription('This will generate a new keypair and deactivate the current one. Are you sure?')
                    ->action(function (UserKeypair $record) {
                        try {
                            $newKeypair = $record->user->generateKeypair();
                            
                            Notification::make()
                                ->title('Keypair regenerated successfully')
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