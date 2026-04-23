<?php

namespace Hexters\HexaLite\Resources\IAM\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Hexters\HexaLite\Models\HexaRole;
use Hexters\HexaLite\Resources\IAM\IAMResource;
use Hexters\HexaLite\Traits\ValidatesChildPermissions;
use Illuminate\Support\Str;

class EditIAMRole extends EditRecord
{
    use ValidatesChildPermissions;

    protected static string $resource = IAMResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn () => hexa()->can('iam.delete')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return hexa()->can('iam.update');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['gates'] = $this->splitAccessToGates($data['access'] ?? []);

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (config('hexa.hierarchy.enabled', false)) {
            $data = $this->validateChildPermissions($data);
        }

        $data['access'] = $this->mergeGatesToAccess($data['gates'] ?? []);

        return $data;
    }

    protected function afterSave(): void
    {
        if (! config('hexa.hierarchy.enabled', false)) {
            return;
        }

        $role = $this->getRecord();

        if ($role instanceof HexaRole && $role->children()->exists()) {
            $role->load('children');
            $this->cascadeChildPermissions($role);
        }
    }

    protected function splitAccessToGates(array $access): array
    {
        $allPanelGates = config('hexa-lite-iam-roles', []);
        $gates = [];

        foreach ($allPanelGates as $panelId => $resources) {
            foreach ($resources as $resource) {
                $key = $panelId . '_' . Str::slug($resource['name'], '_');
                $availablePermissions = array_keys($resource['names']);

                $matched = [];
                foreach ($access as $permissions) {
                    if (is_array($permissions)) {
                        $matched = array_merge($matched, array_intersect($permissions, $availablePermissions));
                    }
                }

                $gates[$key] = array_values(array_unique($matched));
            }
        }

        return $gates;
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
