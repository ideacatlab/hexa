<?php

namespace Hexters\HexaLite\Traits;

use Hexters\HexaLite\Models\HexaRole;
use Illuminate\Support\Str;

trait ValidatesChildPermissions
{
    protected function validateChildPermissions(array $data, ?HexaRole $parentRole = null): array
    {
        if (! $parentRole) {
            return $data;
        }

        $parentPermissions = $parentRole->getFlatPermissions();

        if (isset($data['gates']) && is_array($data['gates'])) {
            foreach ($data['gates'] as $key => $permissions) {
                if (is_array($permissions)) {
                    $data['gates'][$key] = array_values(
                        array_intersect($permissions, $parentPermissions)
                    );
                }
            }
        }

        return $data;
    }

    protected function flattenGates(array $gates): array
    {
        $access = [];

        foreach ($gates as $key => $permissions) {
            if (! is_array($permissions)) {
                continue;
            }

            $canonicalKey = str_contains($key, '__')
                ? Str::after($key, '__')
                : $key;

            if (isset($access[$canonicalKey])) {
                $access[$canonicalKey] = array_values(array_unique(
                    array_merge($access[$canonicalKey], $permissions)
                ));
            } else {
                $access[$canonicalKey] = $permissions;
            }
        }

        return $access;
    }

    protected function cascadeChildPermissions(HexaRole $role): void
    {
        $parentPermissions = $role->getFlatPermissions();

        foreach ($role->children as $child) {
            $access = $child->access;
            if (! is_array($access)) {
                continue;
            }

            $changed = false;
            foreach ($access as $key => $permissions) {
                if (is_array($permissions)) {
                    $filtered = array_values(array_intersect($permissions, $parentPermissions));
                    if ($filtered !== $permissions) {
                        $access[$key] = $filtered;
                        $changed = true;
                    }
                }
            }

            if ($changed) {
                $child->update(['access' => $access]);
            }
        }
    }
}
