<?php

namespace Hexters\HexaLite\Traits;

use Hexters\HexaLite\Models\HexaRole;

trait ValidatesChildPermissions
{
    protected function validateChildPermissions(array $data): array
    {
        if (empty($data['parent_id'])) {
            return $data;
        }

        $parent = HexaRole::find($data['parent_id']);

        if (! $parent) {
            $data['parent_id'] = null;

            return $data;
        }

        $maxDepth = config('hexa.hierarchy.max_depth', 1);
        if ($maxDepth > 0 && $parent->parent_id !== null) {
            $data['parent_id'] = null;

            return $data;
        }

        $parentPermissions = $parent->getFlatPermissions();

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
