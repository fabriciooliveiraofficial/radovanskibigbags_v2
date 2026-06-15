<?php

namespace App\Filament\Resources\CreditApplications\Pages;

use App\Filament\Resources\CreditApplications\CreditApplicationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCreditApplications extends ListRecords
{
    protected static string $resource = CreditApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
