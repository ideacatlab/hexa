<?php

namespace Hexters\HexaLite\Resources\Roles\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Hexters\HexaLite\Models\HexaRole;
use Hexters\HexaLite\Resources\Roles\RoleResource;
use Hexters\HexaLite\Resources\Roles\Widgets\RoleUsersWidget;
use Hexters\HexaLite\Traits\ValidatesChildPermissions;
use Illuminate\Support\Str;

class EditRole extends EditRecord
{
    use ValidatesChildPermissions;

    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn () => hexa()->can('role.delete')),
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            RoleUsersWidget::class,
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return hexa()->can('role.update');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['gates'] = $data['access'] ?? [];

        $crossPanelRoles = config('hexa-lite-cross-panel-roles', []);

        foreach ($crossPanelRoles as $panelId => $roles) {
            foreach ($roles as $role) {
                $key = Str::slug($role['name'], '_');

                if (isset($data['access'][$key])) {
                    $data['gates']["{$panelId}__{$key}"] = $data['access'][$key];
                }
            }
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $plugin = filament('filament-hexa-lite');

        if ($plugin->isScoped()) {
            $data = $this->validateChildPermissions($data, $plugin->getScopingRole());
        }

        $data['access'] = $this->flattenGates($data['gates'] ?? []);

        return $data;
    }

    protected function afterSave(): void
    {
        $role = $this->getRecord();

        if ($role instanceof HexaRole && $role->children()->exists()) {
            $role->load('children');
            $this->cascadeChildPermissions($role);
        }
    }
}
