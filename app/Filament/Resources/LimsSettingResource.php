<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LimsSettingResource\Pages;
use App\Models\LimsSetting;
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

class LimsSettingResource extends Resource
{
    protected static ?string $model = LimsSetting::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'System Configuration';

    protected static ?string $navigationLabel = 'LIMS Settings';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Setting Information')
                    ->schema([
                        Forms\Components\TextInput::make('key')
                            ->label('Setting Key')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->disabled(fn (string $operation) => $operation === 'edit'),
                        
                        Forms\Components\Select::make('type')
                            ->label('Data Type')
                            ->options([
                                'string' => 'String',
                                'integer' => 'Integer',
                                'boolean' => 'Boolean',
                                'json' => 'JSON',
                            ])
                            ->required()
                            ->reactive(),
                        
                        Forms\Components\Textarea::make('value')
                            ->label('Value')
                            ->rows(3)
                            ->required()
                            ->visible(fn (callable $get) => in_array($get('type'), ['string', 'json']))
                            ->helperText(fn (callable $get) => $get('type') === 'json' ? 'Enter valid JSON' : null),
                        
                        Forms\Components\TextInput::make('value')
                            ->label('Value')
                            ->numeric()
                            ->required()
                            ->visible(fn (callable $get) => $get('type') === 'integer'),
                        
                        Forms\Components\Toggle::make('value')
                            ->label('Value')
                            ->required()
                            ->visible(fn (callable $get) => $get('type') === 'boolean'),
                        
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(2)
                            ->nullable(),
                        
                        Forms\Components\Toggle::make('is_encrypted')
                            ->label('Encrypted')
                            ->helperText('Enable for sensitive data'),
                        
                        Forms\Components\Toggle::make('is_system')
                            ->label('System Setting')
                            ->helperText('System settings cannot be deleted')
                            ->disabled(fn (string $operation) => $operation === 'edit'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('key')
                    ->label('Key')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                
                TextColumn::make('type')
                    ->label('Type')
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'string' => 'gray',
                        'integer' => 'blue',
                        'boolean' => 'green',
                        'json' => 'purple',
                        default => 'gray',
                    }),
                
                TextColumn::make('value')
                    ->label('Value')
                    ->limit(50)
                    ->tooltip(function (LimsSetting $record): string {
                        if ($record->is_encrypted) {
                            return 'Encrypted value';
                        }
                        return $record->value ?? '';
                    })
                    ->formatStateUsing(function (LimsSetting $record): string {
                        if ($record->is_encrypted) {
                            return '••••••••';
                        }
                        return $record->value ?? '';
                    }),
                
                BooleanColumn::make('is_encrypted')
                    ->label('Encrypted')
                    ->sortable(),
                
                BooleanColumn::make('is_system')
                    ->label('System')
                    ->sortable(),
                
                TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'string' => 'String',
                        'integer' => 'Integer',
                        'boolean' => 'Boolean',
                        'json' => 'JSON',
                    ]),
                
                Tables\Filters\TernaryFilter::make('is_encrypted')
                    ->label('Encrypted')
                    ->boolean(),
                
                Tables\Filters\TernaryFilter::make('is_system')
                    ->label('System Setting')
                    ->boolean(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                
                Action::make('regenerate_group_keypair')
                    ->label('Regenerate Group Keypair')
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Regenerate Group Keypair')
                    ->modalDescription('This will generate a new group keypair. All existing encrypted data may become inaccessible. Are you sure?')
                    ->visible(fn (LimsSetting $record) => in_array($record->key, ['group_private_key', 'group_public_key']))
                    ->action(function () {
                        try {
                            // Delete existing group keys
                            LimsSetting::whereIn('key', ['group_private_key', 'group_public_key'])->delete();
                            
                            // Generate new ones
                            LimsSetting::getGroupKeypair();
                            
                            Notification::make()
                                ->title('Group keypair regenerated successfully')
                                ->warning()
                                ->send();
                                
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Failed to regenerate group keypair')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (LimsSetting $record) => !$record->is_system),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()->user_type === 'admin'),
                ]),
            ])
            ->defaultSort('key');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLimsSettings::route('/'),
            'create' => Pages\CreateLimsSetting::route('/create'),
            'view' => Pages\ViewLimsSetting::route('/{record}'),
            'edit' => Pages\EditLimsSetting::route('/{record}/edit'),
        ];
    }
}