<?php

namespace App\Filament\Resources\LimsSettingResource\Pages;

use App\Filament\Resources\LimsSettingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLimsSetting extends EditRecord
{
    protected static string $resource = LimsSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
