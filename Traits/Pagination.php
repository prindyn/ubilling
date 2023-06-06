<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait Pagination
{
    /**
     * @param Builder $builder
     */
    public function scopePaginated(Builder $builder, int $page, int $perPage)
    {
        return $builder->skip(max(0, $page - 1) * $perPage)->take($perPage);
    }
}
