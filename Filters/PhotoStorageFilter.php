<?php

namespace App\Filters;

class PhotoStorageFilter extends QueryFilter
{
    /**
     * @param int $item
     */
    public function item(int $item)
    {
        if (!empty($item)) {
            return $this->builder->where('item', $item);
        }
        return $this->builder;
    }

    /**
     * @param string $scope
     */
    public function scope(string $scope)
    {
        return $this->builder->where('scope', $scope);
    }
}
