<?php

namespace Hexters\HexaLite\Resources\IAM\Pages;

use Filament\Resources\Pages\ListRecords;
use Hexters\HexaLite\Resources\IAM\IAMResource;

class ListIAMRoles extends ListRecords
{
    protected static string $resource = IAMResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        return hexa()->can('iam.index');
    }
}
