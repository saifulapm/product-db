<?php

namespace App\Filament\Resources\LoginInfoResource\Pages;

use App\Filament\Resources\LoginInfoResource;
use App\Models\LoginInfo;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageLoginInfos extends ManageRecords
{
    protected static string $resource = LoginInfoResource::class;
    protected static string $view = 'filament.resources.login-info-resource.pages.manage-login-infos';

    public function getLoginInfos()
    {
        return LoginInfo::active()->ordered()->get();
    }
}










