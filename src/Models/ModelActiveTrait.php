<?php

namespace SimpleCrud\Models;

use Illuminate\Database\Eloquent\Builder;

/**
 * Trait ModelActiveTrait
 * @package SimpleCrud\Models
 */
trait ModelActiveTrait
{
    /**
     * Scope a query to only include active records.
     *
     * @param Builder $query
     *
     * @return Builder
     */
    public function scopeOnlyActive(Builder $query)
    {
        return $query->where($this->getActiveKey(), 1);
    }

    /**
     * @return string
     */
    public function getActiveKey()
    {
        return 'active';
    }
}
