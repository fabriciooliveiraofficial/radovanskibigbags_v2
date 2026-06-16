<?php

namespace App\Filament\Resources\SmtpAccounts\Pages;

use App\Filament\Resources\SmtpAccounts\SmtpAccountResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSmtpAccounts extends ListRecords
{
    protected static string $resource = SmtpAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
