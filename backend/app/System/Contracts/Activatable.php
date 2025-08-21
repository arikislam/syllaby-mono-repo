<?php

namespace App\System\Contracts;

interface Activatable
{
    /**
     * Determine if the entity is active.
     */
    public function isActive(): bool;

    /**
     * Set the entity as active.
     */
    public function setActive(): bool;

    /**
     * Set the entity as inactive.
     */
    public function setInactive(): bool;
}