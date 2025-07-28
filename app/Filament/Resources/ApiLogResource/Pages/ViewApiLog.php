<?php

namespace App\Filament\Resources\ApiLogResource\Pages;

use App\Filament\Resources\ApiLogResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Split;
use Filament\Infolists\Components\Grid;

class ViewApiLog extends ViewRecord
{
    protected static string $resource = ApiLogResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Split::make([
                    Grid::make(2)
                        ->schema([
                            Section::make('Request Information')
                                ->schema([
                                    TextEntry::make('id')
                                        ->label('Log ID')
                                        ->badge()
                                        ->color('primary'),

                                    TextEntry::make('method')
                                        ->label('HTTP Method')
                                        ->badge()
                                        ->color(fn (?string $state): string => match ($state) {
                                            'GET' => 'success',
                                            'POST' => 'primary',
                                            'PUT', 'PATCH' => 'warning',
                                            'DELETE' => 'danger',
                                            default => 'gray',
                                        }),

                                    TextEntry::make('endpoint')
                                        ->label('Endpoint')
                                        ->copyable()
                                        ->fontFamily('mono'),

                                    TextEntry::make('response_status')
                                        ->label('Status Code')
                                        ->badge()
                                        ->color(fn (?int $state): string => match (true) {
                                            !$state => 'gray',
                                            $state >= 200 && $state < 300 => 'success',
                                            $state >= 300 && $state < 400 => 'warning',
                                            $state >= 400 && $state < 500 => 'danger',
                                            $state >= 500 => 'gray',
                                            default => 'primary',
                                        }),

                                    TextEntry::make('response_time')
                                        ->label('Response Time')
                                        ->suffix(' ms')
                                        ->color(fn (?float $state): string => match (true) {
                                            !$state => 'gray',
                                            $state < 500 => 'success',
                                            $state < 1000 => 'warning',
                                            default => 'danger',
                                        })
                                        ->formatStateUsing(fn (?float $state): string => 
                                            $state ? number_format($state, 2) : '0.00'
                                        ),

                                    TextEntry::make('logged_at')
                                        ->label('Logged At')
                                        ->dateTime(),
                                ])
                                ->columns(2),

                            Section::make('Client Information')
                                ->schema([
                                    TextEntry::make('user.email')
                                        ->label('User')
                                        ->default('Guest'),

                                    TextEntry::make('ip_address')
                                        ->label('IP Address')
                                        ->copyable()
                                        ->fontFamily('mono'),

                                    TextEntry::make('user_agent')
                                        ->label('User Agent')
                                        ->limit(100)
                                        ->tooltip(fn ($record) => $record->user_agent),

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
                        ]),
                ]),

                Section::make('Request Data')
                    ->schema([
                        TextEntry::make('request_headers')
                            ->label('Request Headers')
                            ->formatStateUsing(function ($state, $record) {
                                // Access the raw JSON from database to avoid array casting issues
                                $rawHeaders = $record->getAttributes()['request_headers'] ?? null;
                                
                                if (!$rawHeaders) {
                                    return 'No request headers';
                                }
                                
                                // If it's already a string (JSON), format it
                                if (is_string($rawHeaders)) {
                                    $decoded = json_decode($rawHeaders, true);
                                    if (json_last_error() === JSON_ERROR_NONE) {
                                        return json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                                    }
                                    return $rawHeaders;
                                }
                                
                                // If it's an array (from casting), encode it
                                if (is_array($rawHeaders)) {
                                    return json_encode($rawHeaders, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                                }
                                
                                return 'Invalid header data';
                            })
                            ->fontFamily('mono')
                            ->copyable(),

                        TextEntry::make('request_body')
                            ->label('Request Body')
                            ->formatStateUsing(function ($state, $record) {
                                // Access the raw JSON from database to avoid array casting issues
                                $rawBody = $record->getAttributes()['request_body'] ?? null;
                                
                                if (!$rawBody) {
                                    return 'No request body';
                                }
                                
                                // If it's already a string (JSON), format it
                                if (is_string($rawBody)) {
                                    $decoded = json_decode($rawBody, true);
                                    if (json_last_error() === JSON_ERROR_NONE) {
                                        return json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                                    }
                                    return $rawBody;
                                }
                                
                                // If it's an array (from casting), encode it
                                if (is_array($rawBody)) {
                                    return json_encode($rawBody, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                                }
                                
                                return 'Invalid body data';
                            })
                            ->fontFamily('mono')
                            ->copyable(),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Section::make('Response Data')
                    ->schema([
                        TextEntry::make('response_headers')
                            ->label('Response Headers')
                            ->formatStateUsing(function ($state, $record) {
                                // Access the raw JSON from database to avoid array casting issues
                                $rawHeaders = $record->getAttributes()['response_headers'] ?? null;
                                
                                if (!$rawHeaders) {
                                    return 'No response headers';
                                }
                                
                                // If it's already a string (JSON), format it
                                if (is_string($rawHeaders)) {
                                    $decoded = json_decode($rawHeaders, true);
                                    if (json_last_error() === JSON_ERROR_NONE) {
                                        return json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                                    }
                                    return $rawHeaders;
                                }
                                
                                // If it's an array (from casting), encode it
                                if (is_array($rawHeaders)) {
                                    return json_encode($rawHeaders, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                                }
                                
                                return 'Invalid header data';
                            })
                            ->fontFamily('mono')
                            ->copyable(),

                        TextEntry::make('response_body')
                            ->label('Response Body')
                            ->formatStateUsing(function ($state, $record) {
                                // Access the raw JSON from database to avoid array casting issues
                                $rawBody = $record->getAttributes()['response_body'] ?? null;
                                
                                if (!$rawBody) {
                                    return 'No response body';
                                }
                                
                                // If it's already a string (JSON), format it
                                if (is_string($rawBody)) {
                                    $decoded = json_decode($rawBody, true);
                                    if (json_last_error() === JSON_ERROR_NONE) {
                                        return json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                                    }
                                    return $rawBody;
                                }
                                
                                // If it's an array (from casting), encode it
                                if (is_array($rawBody)) {
                                    return json_encode($rawBody, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                                }
                                
                                return 'Invalid body data';
                            })
                            ->fontFamily('mono')
                            ->copyable(),
                    ])
                    ->collapsible()
                    ->collapsed(),

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
                    ->visible(fn ($record): bool => 
                        !empty($record->error_type) || !empty($record->error_message)
                    ),
            ]);
    }
}