<?php

namespace App\Models\Concerns;

use App\Models\Role;
use Illuminate\Database\Eloquent\Collection;

trait SimulatesRole
{
    private bool $simulatingRole = false;

    private ?Role $simulatedRole = null;

    /** Only used on the request-scoped clone; never changes role assignments. */
    public function simulateRole(?Role $role): void
    {
        $this->simulatingRole = true;
        $this->simulatedRole = $role;
    }

    public function isRoleSimulationActive(): bool
    {
        return $this->simulatingRole;
    }

    public function getRelationValue($key)
    {
        if ($this->simulatingRole && $key === 'roles') {
            return new Collection($this->simulatedRole ? [$this->simulatedRole] : []);
        }

        return parent::getRelationValue($key);
    }

    public function relationsToArray()
    {
        $relations = parent::relationsToArray();
        if ($this->simulatingRole) {
            $relations['roles'] = $this->roles->toArray();
        }

        return $relations;
    }
}
