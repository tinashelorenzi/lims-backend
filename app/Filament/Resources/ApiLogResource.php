<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ApiLogResource\Pages;
use App\Models\ApiLog;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Database\Eloquent\Collection;
use Filament\Notifications\Notification;

class ApiLogResource extends Resource
{
    protected static ?string $model = ApiLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'System Monitoring';

    protected static ?string $navigationLabel = 'API Logs';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'endpoint';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Request Information')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('User')
                            ->relationship('user', 'email')
                            ->searchable()
                            ->preload()
                            ->disabled(),
                        
                        Forms\Components\TextInput::make('method')
                            ->disabled(),
                        
                        Forms\Components\Textarea::make('endpoint')
                            ->disabled()
                            ->rows(2),
                        
                        Forms\Components\TextInput::make('ip_address')
                            ->label('IP Address')
                            ->disabled(),
                        
                        Forms\Components\TextInput::make('response_status')
                            ->label('Status Code')
                            ->disabled(),
                        
                        Forms\Components\TextInput::make('response_time')
                            ->label('Response Time (ms)')
                            ->disabled(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Request Details')
                    ->schema([
                        Forms\Components\Textarea::make('user_agent')
                            ->label('User Agent')
                            ->disabled()
                            ->rows(2),
                        
                        Forms\Components\KeyValue::make('request_headers')
                            ->label('Request Headers')
                            ->disabled(),
                        
                        Forms\Components\KeyValue::make('request_body')
                            ->label('Request Body')
                            ->disabled(),
                    ]),

                Forms\Components\Section::make('Response Details')
                    ->schema([
                        Forms\Components\KeyValue::make('response_headers')
                            ->label('Response Headers')
                            ->disabled(),
                        
                        Forms\Components\KeyValue::make('response_body')
                            ->label('Response Body')
                            ->disabled(),
                    ]),

                Forms\Components\Section::make('Error Information')
                    ->schema([
                        Forms\Components\TextInput::make('error_type')
                            ->disabled(),
                        
                        Forms\Components\Textarea::make('error_message')
                            ->disabled()
                            ->rows(3),
                    ])
                    ->visible(fn ($record) => $record && ($record->error_type || $record->error_message)),

                Forms\Components\Section::make('Metadata')
                    ->schema([
                        Forms\Components\TextInput::make('request_id')
                            ->label('Request ID')
                            ->disabled(),
                        
                        Forms\Components\TextInput::make('session_id')
                            ->label('Session ID')
                            ->disabled(),
                        
                        Forms\Components\DateTimePicker::make('logged_at')
                            ->disabled(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('logged_at')
                    ->label('Time')
                    ->dateTime('M j, Y H:i:s')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('user.email')
                    ->label('User')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Guest'),

                Tables\Columns\BadgeColumn::make('method')
                    ->colors([
                        'success' => 'GET',
                        'primary' => 'POST',
                        'warning' => 'PUT',
                        'danger' => 'DELETE',
                        'secondary' => ['PATCH', 'OPTIONS', 'HEAD'],
                    ]),

                Tables\Columns\TextColumn::make('endpoint')
                    ->searchable()
                    ->limit(50)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 50 ? $state : null;
                    }),

                Tables\Columns\BadgeColumn::make('response_status')
                    ->label('Status')
                    ->colors([
                        'success' => fn ($state) => $state >= 200 && $state < 300,
                        'warning' => fn ($state) => $state >= 300 && $state < 400,
                        'danger' => fn ($state) => $state >= 400,
                    ])
                    ->sortable(),

                Tables\Columns\TextColumn::make('response_time')
                    ->label('Time (ms)')
                    ->formatStateUsing(fn ($state) => number_format($state, 2))
                    ->sortable()
                    ->color(fn ($state) => $state > 1000 ? 'danger' : ($state > 500 ? 'warning' : 'success')),

                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('has_error')
                    ->label('Error')
                    ->getStateUsing(fn ($record) => $record->response_status >= 400)
                    ->boolean()
                    ->color(fn ($state) => $state ? 'danger' : 'success')
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('method')
                    ->options([
                        'GET' => 'GET',
                        'POST' => 'POST',
                        'PUT' => 'PUT',
                        'PATCH' => 'PATCH',
                        'DELETE' => 'DELETE',
                    ]),

                Tables\Filters\SelectFilter::make('user')
                    ->relationship('user', 'email')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('response_status')
                    ->label('Status Code')
                    ->options([
                        '200' => '200 - OK',
                        '201' => '201 - Created',
                        '400' => '400 - Bad Request',
                        '401' => '401 - Unauthorized',
                        '403' => '403 - Forbidden',
                        '404' => '404 - Not Found',
                        '422' => '422 - Validation Error',
                        '500' => '500 - Server Error',
                    ]),

                Tables\Filters\Filter::make('errors_only')
                    ->label('Errors Only')
                    ->query(fn (Builder $query) => $query->where('response_status', '>=', 400))
                    ->toggle(),

                Tables\Filters\Filter::make('slow_requests')
                    ->label('Slow Requests (>1s)')
                    ->query(fn (Builder $query) => $query->where('response_time', '>', 1000))
                    ->toggle(),

                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('From Date'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Until Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('logged_at', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('logged_at', '<=', $date),
                            );
                    }),

                Tables\Filters\Filter::make('endpoint_regex')
                    ->label('Endpoint Pattern (Regex)')
                    ->form([
                        Forms\Components\TextInput::make('pattern')
                            ->label('Regex Pattern')
                            ->placeholder('e.g., /api/auth.*')
                            ->helperText('Enter a regex pattern to filter endpoints'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (!empty($data['pattern'])) {
                            try {
                                return $query->where('endpoint', 'REGEXP', $data['pattern']);
                            } catch (\Exception $e) {
                                // Invalid regex, ignore filter
                                return $query;
                            }
                        }
                        return $query;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Details'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    BulkAction::make('export_filtered')
                        ->label('Export Selected')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->action(function (Collection $records) {
                            return static::exportLogs($records);
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Export API Logs')
                        ->modalDescription('This will export the selected API logs as a JSON file.')
                        ->modalSubmitActionLabel('Export'),

                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('logged_at', 'desc')
            ->poll('30s'); // Auto-refresh every 30 seconds
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListApiLogs::route('/'),
            'view' => Pages\ViewApiLog::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $errorCount = static::getModel()::where('response_status', '>=', 400)
            ->where('logged_at', '>=', now()->subHour())
            ->count();

        return $errorCount > 0 ? (string) $errorCount : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getNavigationBadge() ? 'danger' : null;
    }

    protected static function exportLogs(Collection $records)
    {
        $data = $records->map(function ($record) {
            return [
                'id' => $record->id,
                'user_email' => $record->user?->email,
                'method' => $record->method,
                'endpoint' => $record->endpoint,
                'ip_address' => $record->ip_address,
                'response_status' => $record->response_status,
                'response_time' => $record->response_time,
                'request_headers' => $record->request_headers,
                'request_body' => $record->request_body,
                'response_headers' => $record->response_headers,
                'response_body' => $record->response_body,
                'error_type' => $record->error_type,
                'error_message' => $record->error_message,
                'logged_at' => $record->logged_at->toISOString(),
            ];
        });

        $filename = 'api_logs_' . now()->format('Y-m-d_H-i-s') . '.json';
        
        Notification::make()
            ->title('Export completed')
            ->body("Exported {$records->count()} log entries")
            ->success()
            ->send();

        return response()->streamDownload(
            function () use ($data) {
                echo json_encode($data, JSON_PRETTY_PRINT);
            },
            $filename,
            ['Content-Type' => 'application/json']
        );
    }

    public static function canCreate(): bool
    {
        return false; // API logs are created automatically
    }

    public static function canEdit(Model $record): bool
    {
        return false; // API logs should not be editable
    }
}