<?php

namespace Hexters\HexaLite\Traits;

use Hexters\HexaLite\Models\HexaRole;

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
