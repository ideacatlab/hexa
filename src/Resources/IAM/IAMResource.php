<?php

namespace Hexters\HexaLite\Resources\IAM;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Hexters\HexaLite\HasHexaLite;
use Hexters\HexaLite\Models\HexaRole;
use Hexters\HexaLite\Resources\IAM\Pages\CreateIAMRole;
use Hexters\HexaLite\Resources\IAM\Pages\EditIAMRole;
use Hexters\HexaLite\Resources\IAM\Pages\ListIAMRoles;
use Hexters\HexaLite\Resources\IAM\Schemas\IAMRoleForm;
use Hexters\HexaLite\Resources\IAM\Tables\IAMRolesTable;

class IAMResource extends Resource
{
    use HasHexaLite;

    public static function getModel(): string
    {
        return config('hexa.models.role', HexaRole::class);
    }

    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    public function roleDescription(): string
    {
        return __('Manage roles and permissions across all panels from a single interface.');
    }

    public function defineGates(): array
    {
        return [
            'iam.index' => __('Access IAM Roles'),
            'iam.create' => __('Create IAM Role'),
            'iam.update' => __('Update IAM Role'),
            'iam.delete' => __('Delete IAM Role'),
        ];
    }

    public function defineGateDescriptions(): array
    {
        return [
            'iam.index' => __('Allows administrators to access and view all roles across panels'),
            'iam.create' => __('Allows administrators to create new roles with cross-panel permissions'),
            'iam.update' => __('Allows administrators to modify existing roles and their cross-panel permissions'),
            'iam.delete' => __('Allows administrators to delete roles'),
        ];
    }

    public static function canAccess(): bool
    {
        return hexa()->can('iam.index');
    }

    public static function getModelLabel(): string
    {
        return __('IAM Role');
    }

    public static function getPluralModelLabel(): string
    {
        return __('IAM Roles');
    }

    public static function form(Schema $schema): Schema
    {
        return IAMRoleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return IAMRolesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListIAMRoles::route('/'),
            'create' => CreateIAMRole::route('/create'),
            'edit' => EditIAMRole::route('/{record}/edit'),
        ];
    }
}
