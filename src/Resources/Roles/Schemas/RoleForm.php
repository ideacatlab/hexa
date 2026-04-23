<?php

namespace Hexters\HexaLite\Resources\Roles\Schemas;

use Filament\Facades\Filament;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Filament\Support\Enums\GridDirection;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class RoleForm
{
    public static function configure(Schema $schema): Schema
    {
        $plugin = filament('filament-hexa-lite');
        $scopingRole = $plugin->getScopingRole();
        $parentPermissions = $scopingRole?->getFlatPermissions();

        $crossPanelRoles = config('hexa-lite-cross-panel-roles', []);
        $hasCrossPanel = ! empty($crossPanelRoles);

        $currentPermissions = static::buildPermissionSections(
            collect(config('hexa-lite-roles')),
            $parentPermissions
        );

        $components = [
            TextInput::make('name')
                ->label(__('Role Name'))
                ->maxLength(100)
                ->placeholder(__('Supervisor'))
                ->required(),
            ViewField::make('checkall')
                ->label(__('Check / Uncheck all'))
                ->view('hexa::role.toggle-button'),
        ];

        if ($hasCrossPanel) {
            $currentPanelId = Filament::getCurrentPanel()->getId();
            $currentResourceNames = collect(config('hexa-lite-roles'))->pluck('name')->all();

            $tabs = [
                Tabs\Tab::make(Str::headline($currentPanelId))
                    ->schema($currentPermissions->all()),
            ];

            foreach ($crossPanelRoles as $panelId => $roles) {
                $uniqueRoles = collect($roles)->filter(function ($role) use ($currentResourceNames) {
                    return ! in_array($role['name'], $currentResourceNames);
                });

                $crossPermissions = static::buildPermissionSections($uniqueRoles, $parentPermissions);

                if ($crossPermissions->isNotEmpty()) {
                    $tabs[] = Tabs\Tab::make(Str::headline($panelId))
                        ->schema($crossPermissions->all());
                }
            }

            $components[] = Tabs::make('permissions')->tabs($tabs);
        } else {
            array_push($components, ...$currentPermissions);
        }

        return $schema->columns(1)->components($components);
    }

    protected static function buildPermissionSections(Collection $roles, ?array $parentPermissions): Collection
    {
        return $roles
            ->map(function ($role) use ($parentPermissions) {
                $key = Str::slug($role['name'], '_');
                $options = $role['names'];

                if ($parentPermissions !== null) {
                    $options = array_filter(
                        $options,
                        fn ($label, $permKey) => in_array($permKey, $parentPermissions),
                        ARRAY_FILTER_USE_BOTH
                    );
                }

                if (empty($options)) {
                    return null;
                }

                return Section::make($role['name'])
                    ->collapsed(false)
                    ->schema([
                        CheckboxList::make("gates.{$key}")
                            ->searchable()
                            ->columns(2)
                            ->gridDirection(GridDirection::Row)
                            ->hiddenLabel()
                            ->bulkToggleable()
                            ->options($options),
                    ]);
            })
            ->filter();
    }
}
