<?php

namespace App\Filament\Resources\SmtpAccounts\Pages;

use App\Filament\Resources\SmtpAccounts\SmtpAccountResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSmtpAccount extends EditRecord
{
    protected static string $resource = SmtpAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
