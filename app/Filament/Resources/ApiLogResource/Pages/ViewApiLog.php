<?php

namespace App\Filament\Resources\ApiLogResource\Pages;

use App\Filament\Resources\ApiLogResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Support\Colors\Color;

class ViewApiLog extends ViewRecord
{
    protected static string $resource = ApiLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('export_single')
                ->label('Export Log')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(function () {
                    $record = $this->record;
                    $data = [
                        'id' => $record->id,
                        'user_email' => $record->user?->email,
                        'method' => $record->method,
                        'endpoint' => $record->endpoint,
                        'ip_address' => $record->ip_address,
                        'user_agent' => $record->user_agent,
                        'response_status' => $record->response_status,
                        'response_time' => $record->response_time,
                        'request_headers' => $record->request_headers,
                        'request_body' => $record->request_body,
                        'response_headers' => $record->response_headers,
                        'response_body' => $record->response_body,
                        'error_type' => $record->error_type,
                        'error_message' => $record->error_message,
                        'request_id' => $record->request_id,
                        'session_id' => $record->session_id,
                        'logged_at' => $record->logged_at->toISOString(),
                    ];

                    $filename = 'api_log_' . $record->id . '_' . now()->format('Y-m-d_H-i-s') . '.json';

                    return response()->streamDownload(
                        function () use ($data) {
                            echo json_encode($data, JSON_PRETTY_PRINT);
                        },
                        $filename,
                        ['Content-Type' => 'application/json']
                    );
                }),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Request Overview')
                    ->schema([
                        TextEntry::make('logged_at')
                            ->label('Timestamp')
                            ->dateTime('M j, Y H:i:s T'),

                        TextEntry::make('user.email')
                            ->label('User')
                            ->placeholder('Guest')
                            ->color('primary'),

                        TextEntry::make('method')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'GET' => Color::Green,
                                'POST' => Color::Blue,
                                'PUT' => Color::Yellow,
                                'DELETE' => Color::Red,
                                default => Color::Gray,
                            }),

                        TextEntry::make('endpoint')
                            ->copyable()
                            ->copyMessage('Endpoint copied to clipboard'),

                        TextEntry::make('response_status')
                            ->label('Status Code')
                            ->badge()
                            ->color(fn (int $state): string => match (true) {
                                $state >= 200 && $state < 300 => Color::Green,
                                $state >= 300 && $state < 400 => Color::Yellow,
                                $state >= 400 => Color::Red,
                                default => Color::Gray,
                            }),

                        TextEntry::make('response_time')
                            ->label('Response Time')
                            ->formatStateUsing(fn (float $state): string => 
                                $state > 1000 
                                    ? number_format($state / 1000, 2) . ' s'
                                    : number_format($state, 2) . ' ms'
                            )
                            ->color(fn (float $state): string => match (true) {
                                $state > 1000 => Color::Red,
                                $state > 500 => Color::Yellow,
                                default => Color::Green,
                            }),
                    ])
                    ->columns(3),

                Section::make('Network Information')
                    ->schema([
                        TextEntry::make('ip_address')
                            ->label('IP Address')
                            ->copyable(),

                        TextEntry::make('user_agent')
                            ->label('User Agent')
                            ->limit(100)
                            ->tooltip(fn (?string $state): ?string => $state),

                        TextEntry::make('request_id')
                            ->label('Request ID')
                            ->copyable()
                            ->fontFamily('mono'),

                        TextEntry::make('session_id')
                            ->label('Session ID')
                            ->copyable()
                            ->fontFamily('mono'),
                    ])
                    ->columns(2),

                Section::make('Request Data')
                    ->schema([
                        KeyValueEntry::make('request_headers')
                            ->label('Request Headers')
                            ->keyLabel('Header')
                            ->valueLabel('Value'),

                        KeyValueEntry::make('request_body')
                            ->label('Request Body')
                            ->keyLabel('Field')
                            ->valueLabel('Value'),
                    ]),

                Section::make('Response Data')
                    ->schema([
                        KeyValueEntry::make('response_headers')
                            ->label('Response Headers')
                            ->keyLabel('Header')
                            ->valueLabel('Value'),

                        KeyValueEntry::make('response_body')
                            ->label('Response Body')
                            ->keyLabel('Field')
                            ->valueLabel('Value'),
                    ]),

                Section::make('Error Information')
                    ->schema([
                        TextEntry::make('error_type')
                            ->label('Error Type')
                            ->badge()
                            ->color('danger'),

                        TextEntry::make('error_message')
                            ->label('Error Message')
                            ->color('danger'),
                    ])
                    ->visible(fn ($record): bool => !empty($record->error_type) || !empty($record->error_message)),
            ]);
    }
}