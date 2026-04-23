<?php

namespace Hexters\HexaLite;

use Filament\Contracts\Plugin;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\Support\Icons\Heroicon;
use Hexters\HexaLite\Models\HexaRole;
use Hexters\HexaLite\Resources\Roles\RoleResource;
use Hexters\HexaLite\Traits\GateTrait;

class HexaLite implements Plugin
{
    use GateTrait;

    protected ?\Closure $scopingRoleFn = null;

    protected ?HexaRole $scopingRoleCache = null;

    public function getId(): string
    {
        return 'filament-hexa-lite';
    }

    public function scopedToRole(\Closure $callback): static
    {
        $this->scopingRoleFn = $callback;

        return $this;
    }

    public function isScoped(): bool
    {
        return $this->scopingRoleFn !== null;
    }

    public function getScopingRole(): ?HexaRole
    {
        if ($this->scopingRoleCache !== null) {
            return $this->scopingRoleCache;
        }

        if ($this->scopingRoleFn) {
            $this->scopingRoleCache = ($this->scopingRoleFn)();
        }

        return $this->scopingRoleCache;
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            RoleResource::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
        $this->registerGates($panel);
        $this->registerGateList($panel);

        $panel->navigationItems([
            NavigationItem::make('hexa-roles')
                ->label(fn (): string => $this->getNavigationLabel())
                ->visible(fn () => hexa()->can('role.index'))
                ->url(fn (): string => RoleResource::getUrl())
                ->isActiveWhen(fn () => request()->fullUrlIs(RoleResource::getUrl() . '*'))
                ->icon(Heroicon::OutlinedLockClosed)
                ->group(fn (): string => $this->getNavigationGroup()),
        ]);
    }

    protected function getNavigationLabel(): string
    {
        $label = config('hexa.navigation.label', 'Role & Permissions');

        if ($label instanceof \Closure) {
            return $label();
        }

        return __($label);
    }

    protected function getNavigationGroup(): string
    {
        $group = config('hexa.navigation.group', 'Settings');

        if ($group instanceof \Closure) {
            return $group();
        }

        return __($group);
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }
}
