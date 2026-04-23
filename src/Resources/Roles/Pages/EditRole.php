<?php

namespace Hexters\HexaLite\Resources\Roles\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Hexters\HexaLite\Models\HexaRole;
use Hexters\HexaLite\Resources\Roles\RoleResource;
use Hexters\HexaLite\Resources\Roles\Widgets\RoleUsersWidget;
use Hexters\HexaLite\Traits\ValidatesChildPermissions;

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
        $data['gates'] = $data['access'];

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (config('hexa.hierarchy.enabled', false)) {
            $data = $this->validateChildPermissions($data);
        }

        $data['access'] = $data['gates'] ?? [];

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
}
