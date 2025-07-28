<?php

namespace App\Filament\Resources\LimsSettingResource\Pages;

use App\Filament\Resources\LimsSettingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLimsSettings extends ListRecords
{
    protected static string $resource = LimsSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
