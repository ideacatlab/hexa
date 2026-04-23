<?php

namespace Hexters\HexaLite\Resources\Roles\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\GridDirection;
use Illuminate\Support\Str;

class RoleForm
{
    public static function configure(Schema $schema): Schema
    {
        $plugin = filament('filament-hexa-lite');
        $scopingRole = $plugin->getScopingRole();
        $parentPermissions = $scopingRole?->getFlatPermissions();

        $permissions = collect(config('hexa-lite-roles'))
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

        return $schema
            ->columns(1)
            ->components([
                TextInput::make('name')
                    ->label(__('Role Name'))
                    ->maxLength(100)
                    ->placeholder(__('Supervisor'))
                    ->required(),
                ViewField::make('checkall')
                    ->label(__('Check / Uncheck all'))
                    ->view('hexa::role.toggle-button'),
                ...$permissions,
            ]);
    }
}
