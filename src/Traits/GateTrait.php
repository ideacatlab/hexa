<?php

namespace Hexters\HexaLite\Traits;

use Filament\Facades\Filament;
use Filament\Panel;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;

trait GateTrait
{
    public function callGates(?Panel  $panel)
    {
        $panel = $panel ?? Filament::getCurrentPanel();
        return collect([
            ...array_values($panel->getPages()),
            ...array_values($panel->getResources()),
        ])
            ->filter(fn($item) => method_exists(app($item), 'roleName'));
    }

    public function registerGateList(Panel $panel)
    {
        $currentPanelRoles = $this->callGates($panel)
            ->map(fn ($component) => [
                'name' => app($component)->roleName(),
                'names' => app($component)->defineGates(),
            ])
            ->values()
            ->all();

        $plugin = filament('filament-hexa-lite');
        $crossPanelIds = $plugin->getCrossPanelIds();

        $crossPanelRoles = [];
        foreach ($crossPanelIds as $panelId) {
            if ($panelId === $panel->getId()) {
                continue;
            }

            $otherPanel = Filament::getPanel($panelId);
            $crossPanelRoles[$panelId] = $this->callGates($otherPanel)
                ->map(fn ($component) => [
                    'name' => app($component)->roleName(),
                    'names' => app($component)->defineGates(),
                ])
                ->values()
                ->all();
        }

        // Resources that share a roleName across panels are the same logical resource.
        // Merge their gate definitions into the current panel section (current wins on
        // label collision) and drop the duplicate from the cross-panel list — otherwise
        // the cross-panel CheckboxList shows fewer options than what the saved role
        // actually contains and Laravel's Rule::in fails on submit with
        // "The selected {tab} {resource} is invalid".
        [$currentPanelRoles, $crossPanelRoles] = static::mergeSharedRoles($currentPanelRoles, $crossPanelRoles);

        Config::set(['hexa-lite-roles' => $currentPanelRoles]);
        Config::set(['hexa-lite-cross-panel-roles' => $crossPanelRoles]);
    }

    /**
     * Merge cross-panel resources that share a roleName with a current-panel resource:
     *  - union the gate definitions into the current panel entry (current panel labels win),
     *  - remove the merged entries from the cross-panel list so they don't appear twice.
     *
     * @param  array<int, array{name: string, names: array<string, string>}>  $currentPanelRoles
     * @param  array<string, array<int, array{name: string, names: array<string, string>}>>  $crossPanelRoles
     * @return array{0: array<int, array{name: string, names: array<string, string>}>, 1: array<string, array<int, array{name: string, names: array<string, string>}>>}
     */
    protected static function mergeSharedRoles(array $currentPanelRoles, array $crossPanelRoles): array
    {
        $currentByName = [];
        foreach ($currentPanelRoles as $i => $role) {
            $currentByName[$role['name']] = $i;
        }

        $promotedAcrossPanels = [];

        foreach ($crossPanelRoles as $panelId => $roles) {
            $kept = [];

            foreach ($roles as $role) {
                $name = $role['name'];

                // Already in the current panel — merge gate options into it.
                if (isset($currentByName[$name])) {
                    $i = $currentByName[$name];
                    $currentPanelRoles[$i]['names'] = array_replace(
                        $role['names'] ?? [],
                        $currentPanelRoles[$i]['names'] ?? []
                    );

                    continue;
                }

                // Already promoted from an earlier cross panel — merge into that
                // single cross-panel entry rather than emitting a second one.
                if (isset($promotedAcrossPanels[$name])) {
                    [$prevPanel, $prevIndex] = $promotedAcrossPanels[$name];
                    $crossPanelRoles[$prevPanel][$prevIndex]['names'] = array_replace(
                        $role['names'] ?? [],
                        $crossPanelRoles[$prevPanel][$prevIndex]['names'] ?? []
                    );

                    continue;
                }

                $kept[] = $role;
                $promotedAcrossPanels[$name] = [$panelId, count($kept) - 1];
            }

            $crossPanelRoles[$panelId] = $kept;
        }

        return [
            $currentPanelRoles,
            array_filter($crossPanelRoles, fn ($r) => $r !== []),
        ];
    }

    public function gates(Panel $panel)
    {
        $collections = $this->callGates($panel)
            ->map(function ($item) {
                return collect(app($item)->gateIndexs());
            })
            ->toArray();
        $gates = [];
        foreach ($collections as $items) {
            foreach ($items as $item) {
                $gates[] = $item;
            }
        }
        return $gates;
    }

    protected function mergeAccess($access): array
    {
        $gates = [];
        foreach ($access as $accesss) {
            foreach ($accesss as $access) {
                $gates[] = $access;
            }
        }
        return $gates;
    }

    protected function registerGates(Panel $panel)
    {
        collect($this->callGates($panel))
            ->map(function ($item) {
                return collect(app($item)->gateIndexs());
            })
            ->each(function ($gates) use ($panel) {
                collect($gates)
                    ->each(function ($gate) use ($panel) {
                        Gate::define($gate, function ($user) use ($gate, $panel) {

                            if (method_exists($user, 'roles')) {

                                if ($tenant = Filament::getTenant()) {
                                    $roles = $user->roles()->whereBelongsTo($tenant)->with('parent')->get();
                                } else {
                                    $roles = $user->roles->load('parent');
                                }

                                if (count($roles) > 0) {
                                    $permissions = [];
                                    foreach ($roles as $role) {
                                        foreach ($role->getEffectivePermissions() as $permission) {
                                            $permissions[] = $permission;
                                        }
                                    }
                                    return in_array($gate, $permissions);
                                }

                                // Superadmin access
                                return true;
                            }

                            return false;
                        });
                    });
            });
    }
}
