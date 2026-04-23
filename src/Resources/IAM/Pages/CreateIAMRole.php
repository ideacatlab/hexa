<?php

namespace Hexters\HexaLite\Resources\IAM\Pages;

use Filament\Resources\Pages\CreateRecord;
use Hexters\HexaLite\Resources\IAM\IAMResource;
use Hexters\HexaLite\Traits\ValidatesChildPermissions;
use Illuminate\Support\Facades\Auth;

class CreateIAMRole extends CreateRecord
{
    use ValidatesChildPermissions;

    protected static string $resource = IAMResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        return hexa()->can('iam.create');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (config('hexa.hierarchy.enabled', false)) {
            $data = $this->validateChildPermissions($data);
        }

        $data['access'] = $this->mergeGatesToAccess($data['gates'] ?? []);
        $data['created_by_name'] = Auth::user()->name;

        return $data;
    }

    protected function mergeGatesToAccess(array $gates): array
    {
        $access = [];

        foreach ($gates as $key => $permissions) {
            if (is_array($permissions) && ! empty($permissions)) {
                $cleanKey = preg_replace('/^[a-z]+_/', '', $key, 1);
                if (isset($access[$cleanKey])) {
                    $access[$cleanKey] = array_values(array_unique(
                        array_merge($access[$cleanKey], $permissions)
                    ));
                } else {
                    $access[$cleanKey] = array_values($permissions);
                }
            }
        }

        return $access;
    }
}
