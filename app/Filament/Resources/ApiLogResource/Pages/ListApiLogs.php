<?php

namespace App\Filament\Resources\ApiLogResource\Pages;

use App\Filament\Resources\ApiLogResource;
use App\Models\ApiLog;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListApiLogs extends ListRecords
{
    protected static string $resource = ApiLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('export_all')
                ->label('Export All')
                ->icon('heroicon-o-arrow-down-tray')
                ->action('exportAllLogs')
                ->requiresConfirmation()
                ->modalHeading('Export All API Logs')
                ->modalDescription('This will export all API logs matching the current filters as a JSON file.')
                ->modalSubmitActionLabel('Export'),

            Actions\Action::make('clear_old_logs')
                ->label('Clear Old Logs')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->action('clearOldLogs')
                ->requiresConfirmation()
                ->modalHeading('Clear Old Logs')
                ->modalDescription('This will delete API logs older than 30 days. This action cannot be undone.')
                ->modalSubmitActionLabel('Clear'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Logs'),

            'recent' => Tab::make('Recent')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('logged_at', '>=', now()->subHours(24)))
                ->badge(fn () => ApiLog::where('logged_at', '>=', now()->subHours(24))->count()),

            'errors' => Tab::make('Errors')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('response_status', '>=', 400))
                ->badge(fn () => ApiLog::where('response_status', '>=', 400)->count())
                ->badgeColor('danger'),

            'slow' => Tab::make('Slow Requests')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('response_time', '>', 1000))
                ->badge(fn () => ApiLog::where('response_time', '>', 1000)->count())
                ->badgeColor('warning'),

            'auth' => Tab::make('Authentication')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('endpoint', 'LIKE', '%/auth/%'))
                ->badge(fn () => ApiLog::where('endpoint', 'LIKE', '%/auth/%')->count())
                ->badgeColor('primary'),
        ];
    }

    public function exportAllLogs()
    {
        $query = $this->getFilteredTableQuery();
        $records = $query->get();

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

        $filename = 'api_logs_export_' . now()->format('Y-m-d_H-i-s') . '.json';

        return response()->streamDownload(
            function () use ($data) {
                echo json_encode($data, JSON_PRETTY_PRINT);
            },
            $filename,
            ['Content-Type' => 'application/json']
        );
    }

    public function clearOldLogs()
    {
        $deletedCount = ApiLog::where('logged_at', '<', now()->subDays(30))->delete();

        $this->notify('success', "Deleted {$deletedCount} old log entries");
    }
}