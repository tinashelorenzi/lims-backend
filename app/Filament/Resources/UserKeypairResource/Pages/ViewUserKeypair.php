<?php

namespace App\Filament\Resources\UserKeypairResource\Pages;

use App\Filament\Resources\UserKeypairResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewUserKeypair extends ViewRecord
{
    protected static string $resource = UserKeypairResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
} 