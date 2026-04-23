<?php

return [

    'models' => [
        'role' => \Hexters\HexaLite\Models\HexaRole::class,
        'user' => \App\Models\User::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Navigation Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the navigation label and group for the Role & Permissions menu item.
    | You can use translation keys (e.g., 'navigation.groups.settings') or plain strings.
    | Use closures for dynamic translation: fn() => __('navigation.groups.settings')
    |
    */

    'navigation' => [
        'label' => 'Role & Permissions',
        'group' => 'Settings',
    ],

    /*
    |--------------------------------------------------------------------------
    | Role Hierarchy
    |--------------------------------------------------------------------------
    |
    | Enable parent-child role hierarchy. When enabled, roles can have a parent
    | role, and child roles are limited to a subset of their parent's permissions.
    |
    */

    'hierarchy' => [
        'enabled' => true,
        'max_depth' => 1,
    ],

    /*
    |--------------------------------------------------------------------------
    | IAM (Cross-Panel Role Management)
    |--------------------------------------------------------------------------
    |
    | Configure the IAM resource navigation. IAM is enabled per-panel via
    | HexaLite::make()->withIAM() and allows managing roles across all panels.
    |
    */

    'iam' => [
        'label' => 'IAM Roles',
        'group' => 'Settings',
    ],

];
