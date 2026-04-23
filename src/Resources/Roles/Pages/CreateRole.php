<?php

namespace Hexters\HexaLite\Resources\Roles\Pages;

use Filament\Resources\Pages\CreateRecord;
use Hexters\HexaLite\Resources\Roles\RoleResource;
use Hexters\HexaLite\Traits\ValidatesChildPermissions;
use Illuminate\Support\Facades\Auth;

class CreateRole extends CreateRecord
{
    use ValidatesChildPermissions;

    protected static string $resource = RoleResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        return hexa()->can('role.create');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (config('hexa.hierarchy.enabled', false)) {
            $data = $this->validateChildPermissions($data);
        }

        $data['access'] = $data['gates'] ?? [];
        $data['guard'] = hexa()->guard();
        $data['created_by_name'] = Auth::user()->name;

        return $data;
    }
}
