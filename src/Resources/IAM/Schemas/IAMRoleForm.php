<?php

namespace Hexters\HexaLite\Resources\IAM\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Enums\GridDirection;
use Hexters\HexaLite\Models\HexaRole;
use Illuminate\Support\Str;

class IAMRoleForm
{
    public static function configure(Schema $schema): Schema
    {
        $allPanelGates = config('hexa-lite-iam-roles', []);
        $hierarchyEnabled = config('hexa.hierarchy.enabled', false);

        $guardOptions = [];
        foreach ($allPanelGates as $panelId => $resources) {
            $guardOptions[$panelId] = ucfirst($panelId);
        }

        $permissionSections = [];
        foreach ($allPanelGates as $panelId => $resources) {
            $panelLabel = ucfirst($panelId) . ' Panel';
            $resourceSections = [];

            foreach ($resources as $resource) {
                $key = $panelId . '_' . Str::slug($resource['name'], '_');

                $checkboxList = CheckboxList::make("gates.{$key}")
                    ->searchable()
                    ->columns(2)
                    ->gridDirection(GridDirection::Row)
                    ->hiddenLabel()
                    ->bulkToggleable()
                    ->options($resource['names']);

                if ($hierarchyEnabled) {
                    $checkboxList->disableOptionWhen(function (string $value, Get $get) {
                        $parentId = $get('parent_id');
                        if (! $parentId) {
                            return false;
                        }
                        $parent = HexaRole::find($parentId);
                        if (! $parent) {
                            return false;
                        }

                        return ! in_array($value, $parent->getFlatPermissions());
                    });
                }

                $resourceSections[] = Section::make($resource['name'])
                    ->collapsed(false)
                    ->schema([$checkboxList]);
            }

            $permissionSections[] = Section::make($panelLabel)
                ->collapsed(count($allPanelGates) > 1)
                ->schema($resourceSections);
        }

        $components = [
            TextInput::make('name')
                ->label(__('Role Name'))
                ->maxLength(100)
                ->placeholder(__('Supervisor'))
                ->required(),
            Select::make('guard')
                ->label(__('Guard'))
                ->options($guardOptions)
                ->default('web')
                ->required(),
        ];

        if ($hierarchyEnabled) {
            $components[] = Select::make('parent_id')
                ->label(__('Parent Role'))
                ->placeholder(__('None (top-level role)'))
                ->options(function (?HexaRole $record, Get $get) {
                    $query = HexaRole::query()->whereNull('parent_id');

                    if ($record) {
                        $query->where('id', '!=', $record->id);
                    }

                    return $query->pluck('name', 'id');
                })
                ->helperText(__('Child roles inherit permissions from their parent. Only permissions the parent has can be assigned.'))
                ->live()
                ->nullable();
        }

        $components[] = ViewField::make('checkall')
            ->label(__('Check / Uncheck all'))
            ->view('hexa::role.toggle-button');

        return $schema
            ->columns(1)
            ->components([
                ...$components,
                ...$permissionSections,
            ]);
    }
}
