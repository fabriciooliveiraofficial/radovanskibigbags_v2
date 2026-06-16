<?php

namespace App\Filament\Resources\SmtpAccounts\Pages;

use App\Filament\Resources\SmtpAccounts\SmtpAccountResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSmtpAccount extends CreateRecord
{
    protected static string $resource = SmtpAccountResource::class;
}
