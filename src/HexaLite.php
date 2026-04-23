<?php

namespace Hexters\HexaLite;

use Filament\Panel;
use Filament\Contracts\Plugin;
use Filament\Navigation\NavigationItem;
use Filament\Support\Icons\Heroicon;
use Hexters\HexaLite\Traits\GateTrait;
use Hexters\HexaLite\Resources\Roles\RoleResource;
use Hexters\HexaLite\Resources\IAM\IAMResource;
use Illuminate\Support\Facades\Config;

class HexaLite implements Plugin
{
    use GateTrait;

    protected bool $iam = false;

    public function getId(): string
    {
        return 'filament-hexa-lite';
    }

    public function withIAM(): static
    {
        $this->iam = true;

        return $this;
    }

    public function hasIAM(): bool
    {
        return $this->iam;
    }

    public function register(Panel $panel): void
    {
        $resources = [RoleResource::class];

        if ($this->iam) {
            $resources[] = IAMResource::class;
        }

        $panel->resources($resources);
    }

    public function boot(Panel $panel): void
    {
        $this->registerGates($panel);
        $this->registerGateList($panel);

        $navItems = [
            NavigationItem::make('hexa-roles')
                ->label(fn (): string => $this->getNavigationLabel())
                ->visible(fn () => hexa()->can('role.index'))
                ->url(fn (): string => RoleResource::getUrl())
                ->isActiveWhen(fn () => request()->fullUrlIs(RoleResource::getUrl() . '*'))
                ->icon(Heroicon::OutlinedLockClosed)
                ->group(fn (): string => $this->getNavigationGroup()),
        ];

        if ($this->iam) {
            $allGates = $this->discoverAllPanelGates();
            Config::set('hexa-lite-iam-roles', $allGates);

            $navItems[] = NavigationItem::make('hexa-iam')
                ->label(fn (): string => $this->getIAMNavigationLabel())
                ->visible(fn () => hexa()->can('iam.index'))
                ->url(fn (): string => IAMResource::getUrl())
                ->isActiveWhen(fn () => request()->fullUrlIs(IAMResource::getUrl() . '*'))
                ->icon(Heroicon::OutlinedShieldCheck)
                ->group(fn (): string => $this->getIAMNavigationGroup());
        }

        $panel->navigationItems($navItems);
    }

    /**
     * Get the navigation label for the Role & Permissions menu item.
     */
    protected function getNavigationLabel(): string
    {
        $label = config('hexa.navigation.label', 'Role & Permissions');

        if ($label instanceof \Closure) {
            return $label();
        }

        return __($label);
    }

    /**
     * Get the navigation group for the Role & Permissions menu item.
     */
    protected function getNavigationGroup(): string
    {
        $group = config('hexa.navigation.group', 'Settings');

        if ($group instanceof \Closure) {
            return $group();
        }

        return __($group);
    }

    protected function getIAMNavigationLabel(): string
    {
        $label = config('hexa.iam.label', 'IAM Roles');

        if ($label instanceof \Closure) {
            return $label();
        }

        return __($label);
    }

    protected function getIAMNavigationGroup(): string
    {
        $group = config('hexa.iam.group', 'Settings');

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
