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
        $plugin = filament('filament-hexa-lite');

        if ($plugin->isScoped()) {
            $scopingRole = $plugin->getScopingRole();
            $data['parent_id'] = $scopingRole?->id;
            $data = $this->validateChildPermissions($data, $scopingRole);
        }

        $data['access'] = $this->flattenGates($data['gates'] ?? []);
        $data['guard'] = hexa()->guard();
        $data['created_by_name'] = Auth::user()->name;

        return $data;
    }
}
