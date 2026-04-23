<?php

namespace Hexters\HexaLite\Models;

use Filament\Facades\Filament;
use Hexters\HexaLite\Helpers\UuidGenerator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HexaRole extends Model
{
    use UuidGenerator;

    protected $table = 'hexa_roles';

    protected $fillable = [
        'name',
        'created_by_name',
        'access',
        'team_id',
        'parent_id',
        'guard',
    ];

    protected $casts = [
        'access' => 'array',
        'gates' => 'array',
        'checkall' => 'array',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Filament::getTenantModel());
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(static::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(static::class, 'parent_id');
    }

    public function getFlatPermissions(): array
    {
        $access = $this->access;

        if (! is_array($access)) {
            return [];
        }

        $permissions = [];

        foreach ($access as $group) {
            if (is_array($group)) {
                foreach ($group as $permission) {
                    $permissions[] = $permission;
                }
            }
        }

        return array_unique($permissions);
    }

    public function getEffectivePermissions(): array
    {
        $own = $this->getFlatPermissions();

        if (! $this->parent_id || ! $this->relationLoaded('parent') && ! $this->parent) {
            return $own;
        }

        $parentPermissions = $this->parent->getFlatPermissions();

        return array_values(array_intersect($own, $parentPermissions));
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            config('hexa.models.user'),
            'hexa_role_user',
            'role_id',
            'user_id'
        );
    }
}
