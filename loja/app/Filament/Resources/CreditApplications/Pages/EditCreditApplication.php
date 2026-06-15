<?php

namespace App\Filament\Resources\CreditApplications\Pages;

use App\Filament\Resources\CreditApplications\CreditApplicationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCreditApplication extends EditRecord
{
    protected static string $resource = CreditApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
